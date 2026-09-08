<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\QuestionTypes\QuestionTypeManager;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;


class AdminQuestionBankController extends Controller
{
    /**
     * Display the question bank listing with advanced filters & testlet groups.
     */
    public function index(Request $request)
    {
        $currentTab = $request->get('tab', 'questions'); // 'questions' or 'testlets'
        $search = $request->get('search', '');
        $skill = $request->get('skill', 'all');
        $difficulty = $request->get('difficulty', 'all');
        $questionType = $request->get('question_type', 'all');
        $testletOnly = $request->boolean('testlet_only');

        // Base Query for Questions (Core 4 skills only)
        $query = QuestionBank::whereIn('skill', ['reading', 'listening', 'writing', 'speaking']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('question_text', 'like', "%{$search}%")
                  ->orWhere('correct_answer', 'like', "%{$search}%")
                  ->orWhere('explanation', 'like', "%{$search}%")
                  ->orWhere('meta_data->passage_title', 'like', "%{$search}%")
                  ->orWhere('meta_data->passage_content', 'like', "%{$search}%");
            });
        }

        if ($skill !== 'all' && in_array($skill, ['reading', 'listening', 'writing', 'speaking'])) {
            $query->where('skill', $skill);
        }

        if ($difficulty !== 'all') {
            $query->where('difficulty', $difficulty);
        }

        if ($questionType !== 'all') {
            $query->where('question_type', $questionType);
        }

        if ($testletOnly) {
            $query->where(function ($q) {
                $q->whereNotNull('meta_data->passage_title')
                  ->orWhereNotNull('meta_data->passage_content');
            });
        }

        $questions = $query->orderBy('id', 'desc')->paginate(25)->withQueryString();

        // Compute Statistics for 4 Core Skills
        $totalQuestions = QuestionBank::whereIn('skill', ['listening', 'reading', 'writing', 'speaking'])->count();
        $listeningCount = QuestionBank::where('skill', 'listening')->count();
        $readingCount = QuestionBank::where('skill', 'reading')->count();
        $writingCount = QuestionBank::where('skill', 'writing')->count();
        $speakingCount = QuestionBank::where('skill', 'speaking')->count();

        // Group Testlets / Passages
        $allTestletQuestions = QuestionBank::whereNotNull('meta_data->passage_title')
            ->orWhereNotNull('meta_data->passage_content')
            ->get();

        $testletGroups = $allTestletQuestions->groupBy(function ($item) {
            $meta = $item->meta_data ?? [];
            return $meta['passage_title'] ?? ('Passage ID #' . $item->id);
        })->map(function ($group, $title) {
            $first = $group->first();
            $meta = $first->meta_data ?? [];
            return (object)[
                'title' => $title,
                'skill' => $first->skill,
                'difficulty' => $group->pluck('difficulty')->unique()->implode(', '),
                'content' => $meta['passage_content'] ?? $meta['passage'] ?? '',
                'audio_url' => $first->audio_url,
                'question_count' => $group->count(),
                'question_ids' => $group->pluck('id')->toArray(),
                'sample_question' => $first->question_text,
                'created_at' => $first->created_at,
            ];
        })->values();

        $totalTestletsCount = $testletGroups->count();

        return view('admin.questions.index', [
            'questions' => $questions,
            'testletGroups' => $testletGroups,
            'currentTab' => $currentTab,
            'search' => $search,
            'skill' => $skill,
            'difficulty' => $difficulty,
            'questionType' => $questionType,
            'testletOnly' => $testletOnly,
            'stats' => [
                'total' => $totalQuestions,
                'listening' => $listeningCount,
                'reading' => $readingCount,
                'writing' => $writingCount,
                'speaking' => $speakingCount,
                'testlets' => $totalTestletsCount,
            ],
        ]);
    }

    /**
     * Store a single question into the question bank.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'skill' => 'required|in:reading,listening,writing,speaking',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,Mixed',
            'question_type' => 'required|string|max:50',
            'question_text' => 'required|string',
            'correct_answer' => 'nullable|string',
            'explanation' => 'nullable|string',
            'audio_url' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,webm,aac|max:25600',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:10240',
            'passage_title' => 'nullable|string|max:255',
            'passage_content' => 'nullable|string',
            'min_words' => 'nullable|integer|min:10|max:1000',
        ]);

        $skill = $validated['skill'];
        $qType = $validated['question_type'];

        // Delegate options and answer formatting to dedicated Question Type Handler
        $handler = QuestionTypeManager::get($qType);
        $typeData = $handler->formatForStorage($request);

        $options = $typeData['options'];
        $correctAnswer = !empty($typeData['correct_answer']) ? $typeData['correct_answer'] : ($validated['correct_answer'] ?? '');

        // Construct metadata
        $metaData = $typeData['meta_data'] ?? [];
        if ($request->filled('passage_title') || $request->filled('passage_content')) {
            $metaData['passage_title'] = $request->input('passage_title');
            $metaData['passage_content'] = $request->input('passage_content');
        }
        if ($request->filled('min_words')) {
            $metaData['min_words'] = (int) $request->input('min_words');
        }
        if ($request->filled('task_type')) {
            $metaData['task_type'] = $request->input('task_type');
        }

        // Handle Audio File upload or fallback to URL
        $audioUrl = $validated['audio_url'] ?? null;
        if ($request->hasFile('audio_file')) {
            $audioRecord = app(FileStorageService::class)->store($request->file('audio_file'), 'questions/audio');
            $audioUrl = $audioRecord->getUrl();
        }

        // Handle Image File upload
        if ($request->hasFile('image_file')) {
            $imageRecord = app(FileStorageService::class)->store($request->file('image_file'), 'questions/images');
            $metaData['image_url'] = $imageRecord->getUrl();
        }

        QuestionBank::create([
            'skill' => $skill,
            'difficulty' => $validated['difficulty'],
            'question_type' => $qType,
            'question_text' => $validated['question_text'],
            'options' => $options,
            'audio_url' => $audioUrl,
            'correct_answer' => $correctAnswer,
            'explanation' => $validated['explanation'] ?? null,
            'meta_data' => !empty($metaData) ? $metaData : null,
        ]);

        return redirect()->route('admin.questions.index')
            ->with('success', "Đã thêm câu hỏi dạng '{$qType}' vào ngân hàng thành công!");
    }


    /**
     * Store an entire Reading / Listening Testlet (Passage + Multiple Sub-questions).
     */
    public function storeTestlet(Request $request)
    {
        $validated = $request->validate([
            'skill' => 'required|in:reading,listening',
            'difficulty' => 'required|in:A1,A2,B1,B2,C1',
            'passage_title' => 'required|string|max:255',
            'passage_content' => 'required|string',
            'audio_url' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,webm,aac|max:25600',
            'part' => 'nullable|integer|min:1|max:10',
            'questions' => 'required|array|min:1',
            'questions.*.question_text' => 'required|string',
            'questions.*.opt_a' => 'required|string',
            'questions.*.opt_b' => 'required|string',
            'questions.*.opt_c' => 'required|string',
            'questions.*.opt_d' => 'required|string',
            'questions.*.correct_answer' => 'required|in:A,B,C,D',
            'questions.*.explanation' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $passageTitle = trim($validated['passage_title']);
            $passageContent = trim($validated['passage_content']);
            $audioUrl = $validated['audio_url'] ?? null;
            if ($request->hasFile('audio_file')) {
                $audioRecord = app(FileStorageService::class)->store($request->file('audio_file'), 'questions/audio');
                $audioUrl = $audioRecord->getUrl();
            }
            $difficulty = $validated['difficulty'];
            $skill = $validated['skill'];
            $part = $validated['part'] ?? 1;


            $createdCount = 0;
            foreach ($validated['questions'] as $idx => $qData) {
                $options = [
                    'A. ' . trim($qData['opt_a']),
                    'B. ' . trim($qData['opt_b']),
                    'C. ' . trim($qData['opt_c']),
                    'D. ' . trim($qData['opt_d']),
                ];

                // Formulate correct answer
                $correctLetter = strtoupper(trim($qData['correct_answer']));
                $correctIndex = match($correctLetter) {
                    'B' => 1,
                    'C' => 2,
                    'D' => 3,
                    default => 0,
                };
                $correctAnswerText = $options[$correctIndex];

                QuestionBank::create([
                    'skill' => $skill,
                    'difficulty' => $difficulty,
                    'question_type' => ($skill === 'listening') ? 'audio_listening' : 'mcq',
                    'question_text' => trim($qData['question_text']),
                    'options' => $options,
                    'audio_url' => $audioUrl,
                    'correct_answer' => $correctAnswerText,
                    'explanation' => $qData['explanation'] ?? null,
                    'meta_data' => [
                        'part' => $part,
                        'part_name' => "Phần {$part}: {$passageTitle}",
                        'passage_title' => $passageTitle,
                        'passage_content' => $passageContent,
                        'q_num' => $idx + 1,
                    ],
                ]);
                $createdCount++;
            }

            DB::commit();

            return redirect()->route('admin.questions.index', ['tab' => 'testlets'])
                ->with('success', "Đã tạo thành công Cụm bài đọc '{$passageTitle}' với {$createdCount} câu hỏi con!");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Lỗi khi tạo cụm bài đọc: ' . $e->getMessage());
        }
    }

    /**
     * Update an individual question.
     */
    public function update(Request $request, int $id)
    {
        $question = QuestionBank::findOrFail($id);

        $validated = $request->validate([
            'difficulty' => 'required|in:A1,A2,B1,B2,C1,Mixed',
            'question_text' => 'required|string',
            'correct_answer' => 'required|string',
            'explanation' => 'nullable|string',
            'options' => 'nullable|string',
            'audio_url' => 'nullable|string',
            'audio_file' => 'nullable|file|mimes:mp3,wav,ogg,m4a,webm,aac|max:25600',
            'image_file' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:10240',
            'passage_title' => 'nullable|string|max:255',
            'passage_content' => 'nullable|string',
            'sync_passage_to_cluster' => 'nullable|boolean',
        ]);

        $options = $question->options;
        if ($request->filled('options')) {
            $options = array_values(array_filter(array_map('trim', explode("\n", $request->input('options')))));
        }

        $meta = $question->meta_data ?? [];
        $oldPassageTitle = $meta['passage_title'] ?? null;

        if ($request->has('passage_title')) {
            $meta['passage_title'] = $request->input('passage_title');
        }
        if ($request->has('passage_content')) {
            $meta['passage_content'] = $request->input('passage_content');
        }

        $audioUrl = $request->filled('audio_url') ? $request->input('audio_url') : $question->audio_url;
        if ($request->hasFile('audio_file')) {
            $audioRecord = app(FileStorageService::class)->store($request->file('audio_file'), 'questions/audio');
            $audioUrl = $audioRecord->getUrl();
        }

        if ($request->hasFile('image_file')) {
            $imageRecord = app(FileStorageService::class)->store($request->file('image_file'), 'questions/images');
            $meta['image_url'] = $imageRecord->getUrl();
        }

        $question->update([
            'difficulty' => $validated['difficulty'],
            'question_text' => $validated['question_text'],
            'correct_answer' => $validated['correct_answer'],
            'explanation' => $validated['explanation'] ?? null,
            'options' => $options,
            'audio_url' => $audioUrl,
            'meta_data' => !empty($meta) ? $meta : null,
        ]);


        // If requested, synchronize the updated passage across all other questions in the cluster
        if ($request->boolean('sync_passage_to_cluster') && !empty($oldPassageTitle)) {
            $clusterQuestions = QuestionBank::where('meta_data->passage_title', $oldPassageTitle)->get();
            foreach ($clusterQuestions as $cq) {
                $cMeta = $cq->meta_data ?? [];
                $cMeta['passage_title'] = $meta['passage_title'];
                $cMeta['passage_content'] = $meta['passage_content'];
                $cq->update(['meta_data' => $cMeta]);
            }
        }

        return redirect()->back()->with('success', "Đã cập nhật câu hỏi #{$id} thành công!");
    }

    /**
     * Update a whole testlet / passage cluster (Title & Content for all its child questions).
     */
    public function updateTestlet(Request $request)
    {
        $validated = $request->validate([
            'old_passage_title' => 'required|string',
            'passage_title' => 'required|string|max:255',
            'passage_content' => 'required|string',
            'difficulty' => 'nullable|in:A1,A2,B1,B2,C1',
        ]);

        $questions = QuestionBank::where('meta_data->passage_title', $validated['old_passage_title'])->get();

        if ($questions->isEmpty()) {
            return redirect()->back()->with('error', 'Không tìm thấy cụm bài đọc cần cập nhật.');
        }

        foreach ($questions as $q) {
            $meta = $q->meta_data ?? [];
            $meta['passage_title'] = $validated['passage_title'];
            $meta['passage_content'] = $validated['passage_content'];
            $meta['part_name'] = "Phần " . ($meta['part'] ?? 1) . ": " . $validated['passage_title'];

            $updateData = ['meta_data' => $meta];
            if (!empty($validated['difficulty'])) {
                $updateData['difficulty'] = $validated['difficulty'];
            }
            $q->update($updateData);
        }

        return redirect()->route('admin.questions.index', ['tab' => 'testlets'])
            ->with('success', "Đã cập nhật văn bản và thông tin cho toàn bộ " . $questions->count() . " câu hỏi trong cụm!");
    }

    /**
     * Delete an individual question.
     */
    public function destroy(int $id)
    {
        $question = QuestionBank::findOrFail($id);
        $question->delete();

        return redirect()->back()->with('success', "Đã xóa câu hỏi #{$id} khỏi ngân hàng dữ liệu.");
    }

    /**
     * Delete multiple selected questions at once.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:question_banks,id',
        ]);

        $count = QuestionBank::whereIn('id', $validated['ids'])->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "Đã xóa thành công {$count} câu hỏi khỏi ngân hàng dữ liệu!",
            ]);
        }

        return redirect()->back()->with('success', "Đã xóa thành công {$count} câu hỏi đã chọn khỏi ngân hàng dữ liệu!");
    }

    /**
     * Delete an entire Testlet (Passage + all child questions).
     */
    public function destroyTestlet(Request $request)
    {
        $validated = $request->validate([
            'passage_title' => 'required|string',
        ]);

        $deleted = QuestionBank::where('meta_data->passage_title', $validated['passage_title'])->delete();

        return redirect()->route('admin.questions.index', ['tab' => 'testlets'])
            ->with('success', "Đã xóa toàn bộ {$deleted} câu hỏi thuộc Cụm bài đọc '{$validated['passage_title']}'!");
    }

    /**
     * Fetch JSON details of a question for Modal Preview.
     */
    public function showJson(int $id): JsonResponse
    {
        $question = QuestionBank::findOrFail($id);
        $meta = $question->meta_data ?? [];

        return response()->json([
            'id' => $question->id,
            'skill' => $question->skill,
            'difficulty' => $question->difficulty,
            'question_type' => $question->question_type,
            'question_text' => $question->question_text,
            'options' => $question->options,
            'correct_answer' => $question->correct_answer,
            'explanation' => $question->explanation,
            'audio_url' => $question->audio_url,
            'image_url' => $meta['image_url'] ?? null,
            'passage_title' => $meta['passage_title'] ?? null,

            'passage_content' => $meta['passage_content'] ?? $meta['passage'] ?? null,
            'part' => $meta['part'] ?? null,
            'created_at' => $question->created_at?->format('d/m/Y H:i'),
        ]);
    }
}
