<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreActivityRequest;
use App\Models\Activity;
use App\Models\Lesson;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;

class CourseActivityController extends Controller
{
    /**
     * Store a new learning activity for a lesson.
     */
    public function storeActivity(StoreActivityRequest $request, $lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $type = $request->input('type');
        $content = [];
        $fileId = null;

        // Handle file upload with dedup service
        $fileService = app(FileStorageService::class);

        if ($request->hasFile('direct_file')) {
            $uploaded = $request->file('direct_file');
            $errors = $fileService->validate($uploaded);
            if (!empty($errors)) {
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'errors' => $errors], 422);
                }
                return back()->withErrors($errors)->withInput();
            }
            $fileRecord = $fileService->store($uploaded, $request->user()?->id);
            $fileId = $fileRecord->id;
        } elseif ($request->filled('file_id')) {
            // File was pre-uploaded via uploadActivityFile endpoint
            $fileId = $request->input('file_id');
        }

        // Build structured JSON based on modern activity type
        $content = $this->buildActivityContent($request, $type, $fileId);

        $activity = Activity::create([
            'lesson_id' => $lessonId,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $type,
            'content' => $content,
            'order' => $request->order,
            'estimated_minutes' => $request->input('estimated_minutes', 5) ?: 5,
            'is_visible' => $request->has('is_visible') ? $request->boolean('is_visible') : true,
            'is_free_trial' => $request->has('is_free_trial') ? $request->boolean('is_free_trial') : false,
            'available_from' => $request->filled('available_from') ? $request->available_from : null,
            'available_until' => $request->filled('available_until') ? $request->available_until : null,
            'completion_type' => (function() use ($request, $type) {
                $comp = $request->input('completion_type', 'manual');
                if ($comp === 'auto_grade' && !in_array($type, ['quiz', 'assignment', 'ai_speaking', 'ai_writing'])) {
                    return 'manual';
                }
                if ($comp === 'auto_submit' && !in_array($type, ['assignment', 'ai_writing'])) {
                    return 'manual';
                }
                return $comp;
            })(),
            'passing_grade' => $request->filled('passing_grade') ? $request->passing_grade : null,
            'max_attempts' => $request->filled('max_attempts') ? $request->max_attempts : null,
            'grading_method' => $request->input('grading_method', 'highest'),
            'time_limit_minutes' => $request->filled('time_limit_minutes') ? $request->time_limit_minutes : null,
            'file_id' => $fileId,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Đã tạo học liệu '{$activity->title}' thành công!",
                'activity' => $activity->fresh(),
            ]);
        }

        return redirect()->route('admin.courses.show', $lesson->course_id)
            ->with('success', "Đã thêm học liệu '{$request->title}' ({$type}) vào bài học!");
    }

    /**
     * Delete a learning activity.
     */
    public function destroyActivity($activityId)
    {
        $activity = Activity::with('lesson')->findOrFail($activityId);
        $courseId = $activity->lesson->course_id;
        $title = $activity->title;

        // Decrement file reference count if activity has a file
        if ($activity->file_id) {
            $fileService = app(FileStorageService::class);
            $fileService->decrementReference($activity->file);
        }

        $activity->delete();

        return redirect()->route('admin.courses.show', $courseId)
            ->with('success', "Đã xóa học liệu '{$title}'.");
    }

    /**
     * Reorder activities within a lesson via drag-drop (JSON API).
     * Expects: { items: [{id: 1, order: 1}, ...] }
     */
    public function reorderActivities(Request $request, $lessonId)
    {
        $request->validate(['items' => 'required|array', 'items.*.id' => 'required|integer', 'items.*.order' => 'required|integer']);

        foreach ($request->items as $item) {
            Activity::where('id', $item['id'])->where('lesson_id', $lessonId)->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Đã cập nhật thứ tự hoạt động.']);
    }

    /**
     * Move an activity to a different lesson (cross-lesson drag-drop).
     * Expects: { target_lesson_id: 5, order: 3 }
     */
    public function moveActivity(Request $request, $activityId)
    {
        $request->validate([
            'target_lesson_id' => 'required|integer|exists:lessons,id',
            'order' => 'required|integer|min:1',
        ]);

        $activity = Activity::findOrFail($activityId);
        $activity->update([
            'lesson_id' => $request->target_lesson_id,
            'order' => $request->order,
        ]);

        return response()->json(['success' => true, 'message' => 'Đã di chuyển hoạt động sang bài học khác.']);
    }

    /**
     * Update activity configuration (title, description, visibility, completion, timing).
     */
    public function updateActivity(Request $request, $activityId)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string|max:50',
            'estimated_minutes' => 'sometimes|integer|min:1',
            'is_visible' => 'sometimes|boolean',
            'is_free_trial' => 'sometimes|boolean',
            'available_from' => 'nullable|date',
            'available_until' => 'nullable|date',
            'completion_type' => 'sometimes|string|in:manual,auto_view,auto_grade,auto_submit',
            'passing_grade' => 'nullable|numeric|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:1|max:99',
            'grading_method' => 'nullable|string|in:highest,last,average,first',
            'time_limit_minutes' => 'nullable|integer|min:1|max:600',
        ]);

        $activity = Activity::findOrFail($activityId);

        $updateData = $request->only([
            'title', 'description', 'type', 'estimated_minutes',
            'is_visible', 'is_free_trial', 'available_from', 'available_until',
            'completion_type', 'passing_grade', 'max_attempts', 'grading_method', 'time_limit_minutes',
        ]);

        if (isset($updateData['completion_type'])) {
            $actType = $updateData['type'] ?? $activity->type;
            if ($updateData['completion_type'] === 'auto_grade' && !in_array($actType, ['quiz', 'assignment', 'ai_speaking', 'ai_writing'])) {
                $updateData['completion_type'] = 'manual';
            }
            if ($updateData['completion_type'] === 'auto_submit' && !in_array($actType, ['assignment', 'ai_writing'])) {
                $updateData['completion_type'] = 'manual';
            }
        }

        // Handle content field if provided
        if ($request->has('content')) {
            $updateData['content'] = $request->input('content');
        }

        $activity->update($updateData);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã cập nhật hoạt động.', 'activity' => $activity->fresh()]);
        }

        return back()->with('success', "Đã cập nhật hoạt động '{$activity->title}'.");
    }

    /**
     * 1-click toggle activity visibility.
     */
    public function toggleActivityVisibility($activityId)
    {
        $activity = Activity::findOrFail($activityId);
        $activity->update(['is_visible' => !$activity->is_visible]);

        $status = $activity->is_visible ? 'hiển thị' : 'ẩn';
        return response()->json(['success' => true, 'is_visible' => $activity->is_visible, 'message' => "Hoạt động đã được {$status}."]);
    }

    /**
     * 1-click toggle activity free trial status.
     */
    public function toggleActivityTrial($activityId)
    {
        $activity = Activity::findOrFail($activityId);
        $activity->update(['is_free_trial' => !$activity->is_free_trial]);

        $status = $activity->is_free_trial ? 'học thử (Free Trial)' : 'học chính thức (Cần ghi danh)';
        return response()->json(['success' => true, 'is_free_trial' => $activity->is_free_trial, 'message' => "Hoạt động đã chuyển sang chế độ {$status}."]);
    }

    /**
     * Duplicate an activity within the same lesson.
     */
    public function duplicateActivity($activityId)
    {
        $original = Activity::findOrFail($activityId);

        $maxOrder = Activity::where('lesson_id', $original->lesson_id)->max('order') ?? 0;

        $clone = $original->replicate();
        $clone->title = $original->title . ' (Bản sao)';
        $clone->order = $maxOrder + 1;
        $clone->save();

        return response()->json([
            'success' => true,
            'message' => "Đã nhân bản hoạt động '{$original->title}'.",
            'activity' => $clone,
        ]);
    }

    /**
     * Upload a file for a file-type activity (with CAS deduplication).
     */
    public function uploadActivityFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $fileService = app(FileStorageService::class);
        $uploaded = $request->file('file');

        $errors = $fileService->validate($uploaded);
        if (!empty($errors)) {
            return response()->json(['success' => false, 'errors' => $errors], 422);
        }

        $fileRecord = $fileService->store($uploaded, $request->user()?->id);

        return response()->json([
            'success' => true,
            'file_id' => $fileRecord->id,
            'file_path' => $fileRecord->path,
            'file_size' => $fileRecord->size,
            'file_original_name' => $fileRecord->original_name,
            'file_url' => $fileRecord->getUrl(),
            'is_duplicate' => $fileRecord->reference_count > 1,
        ]);
    }

    /**
     * Build structured JSON content based on activity type.
     */
    private function buildActivityContent(Request $request, string $type, ?int $fileId): array
    {
        switch ($type) {
            case 'vocabulary':
                $content = [];
                $wordsRaw = $request->input('vocab_words', []);
                if (is_array($wordsRaw)) {
                    foreach ($wordsRaw as $w) {
                        if (!empty($w['word'])) {
                            $content[] = [
                                'word' => trim($w['word']),
                                'meaning' => trim($w['meaning'] ?? ''),
                                'phonetic' => trim($w['phonetic'] ?? ''),
                                'example' => trim($w['example'] ?? ''),
                            ];
                        }
                    }
                }
                return $content;

            case 'grammar':
                return [
                    'title' => $request->input('grammar_title', $request->title),
                    'explanation' => $request->input('grammar_explanation', ''),
                    'rules' => array_values(array_filter(array_map('trim', explode("\n", $request->input('grammar_rules', ''))))),
                    'examples' => array_values(array_filter(array_map('trim', explode("\n", $request->input('grammar_examples', ''))))),
                ];

            case 'video':
                return [
                    'video_url' => $request->input('video_url', ''),
                    'source' => $fileId ? 'upload' : 'url',
                    'title' => $request->title,
                    'description' => $request->input('video_description', ''),
                    'duration' => $request->input('video_duration', '10:00'),
                ];

            case 'audio_listening':
                return [
                    'audio_url' => $request->input('audio_url', ''),
                    'source' => $fileId ? 'upload' : 'url',
                    'transcript' => $request->input('audio_transcript', ''),
                    'key_vocabulary' => $request->input('audio_vocab', ''),
                ];

            case 'pdf_document':
                return [
                    'document_url' => $request->input('pdf_url', ''),
                    'source' => $fileId ? 'upload' : 'url',
                    'summary' => $request->input('pdf_summary', ''),
                    'notes' => $request->input('pdf_notes', ''),
                ];

            case 'ai_speaking':
                return [
                    'target_sentence' => $request->input('speaking_sentence', ''),
                    'phonetic_guide' => $request->input('speaking_ipa', ''),
                    'tip' => $request->input('speaking_tip', 'Đọc rõ ràng từng âm tiết và ngữ điệu tự nhiên'),
                ];

            case 'ai_writing':
                return [
                    'prompt' => $request->input('writing_prompt', ''),
                    'min_words' => (int) $request->input('writing_min_words', 50),
                    'sample_outline' => $request->input('writing_outline', ''),
                ];

            case 'quiz':
                $questionsRaw = $request->input('quiz_questions', []);
                $questions = [];
                if (is_array($questionsRaw)) {
                    foreach ($questionsRaw as $q) {
                        if (!empty($q['question'])) {
                            $options = is_array($q['options']) 
                                ? $q['options'] 
                                : array_values(array_filter(array_map('trim', explode(',', $q['options'] ?? ''))));
                            $questions[] = [
                                'question' => $q['question'],
                                'options' => $options,
                                'answer' => (int) ($q['answer'] ?? 0),
                            ];
                        }
                    }
                }
                return ['questions' => $questions];

            case 'file':
                return ['notes' => $request->input('file_notes', '')];

            case 'url':
                return [
                    'url' => $request->input('external_url', ''),
                    'open_in_new_tab' => $request->boolean('open_in_new_tab', true),
                    'instructions' => $request->input('url_instructions', ''),
                ];

            case 'text_page':
                return ['body' => $request->input('page_body', '')];

            case 'assignment':
                return [
                    'instructions' => $request->input('assignment_instructions', ''),
                    'allowed_extensions' => $request->input('allowed_extensions', 'pdf,docx,zip'),
                    'max_file_size_mb' => (int) $request->input('max_file_size_mb', 20),
                ];

            case 'forum':
                return [
                    'topic' => $request->input('forum_topic', $request->title),
                    'guidelines' => $request->input('forum_guidelines', ''),
                ];

            case 'label':
                return ['text' => $request->input('label_text', $request->title)];

            default:
                return ['raw_text' => $request->input('raw_text', '')];
        }
    }
}
