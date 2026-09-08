<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AssignmentSubmission;
use App\Services\LMS\CompletionService;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;

class AssignmentSubmissionController extends Controller
{
    public function __construct(
        private CompletionService $completionService,
        private FileStorageService $fileStorageService
    ) {}

    /**
     * Submit an assignment (student uploads file or text).
     */
    public function submit(Request $request, $activityId)
    {
        $activity = Activity::with('lesson.course')->findOrFail($activityId);
        $user = $request->user();

        // Trial / Security Check: Must be enrolled to submit assignments
        $enrollment = $user->getEnrollment($activity->lesson->course_id);
        if (!$enrollment || !$enrollment->hasValidAccess()) {
            return back()->with('error', '🔒 Bạn cần ghi danh vào khóa học để nộp bài tập chính thức.');
        }

        // Ensure this is an assignment-type activity
        if ($activity->type !== Activity::TYPE_ASSIGNMENT) {
            return back()->with('error', 'Hoạt động này không phải dạng bài tập nộp bài.');
        }

        // Check availability
        if (!$activity->isAvailable()) {
            return back()->with('error', 'Bài tập này hiện không nhận bài nộp (đã hết hạn hoặc chưa mở).');
        }

        // Check max attempts
        $userId = $request->user()->id;
        $currentAttempts = AssignmentSubmission::where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->count();

        if ($activity->max_attempts && $currentAttempts >= $activity->max_attempts) {
            return back()->with('error', "Bạn đã hết số lần nộp bài cho phép ({$activity->max_attempts} lần).");
        }

        $request->validate([
            'submission_file' => 'nullable|file|max:51200', // 50MB max
            'text_content' => 'nullable|string|max:50000',
        ]);

        // Must have either file or text
        if (!$request->hasFile('submission_file') && !$request->filled('text_content')) {
            return back()->with('error', 'Vui lòng upload file hoặc nhập nội dung bài nộp.');
        }

        $fileId = null;

        if ($request->hasFile('submission_file')) {
            $uploaded = $request->file('submission_file');

            // Validate file type/size
            $errors = $this->fileStorageService->validate($uploaded);
            if (!empty($errors)) {
                return back()->withErrors($errors)->withInput();
            }

            // Check allowed extensions from activity config
            $activityContent = $activity->content;
            $allowedExtensions = $activityContent['allowed_extensions'] ?? 'pdf,docx,zip';
            $allowedList = array_map('trim', explode(',', $allowedExtensions));
            $fileExt = strtolower($uploaded->getClientOriginalExtension());

            if (!in_array($fileExt, $allowedList)) {
                return back()->with('error', "Loại file '.{$fileExt}' không được chấp nhận. Cho phép: {$allowedExtensions}");
            }

            // Check max file size from activity config
            $maxSizeMb = (int) ($activityContent['max_file_size_mb'] ?? 20);
            if ($uploaded->getSize() > $maxSizeMb * 1024 * 1024) {
                return back()->with('error', "File vượt quá giới hạn {$maxSizeMb} MB.");
            }

            $fileRecord = $this->fileStorageService->store($uploaded, $userId);
            $fileId = $fileRecord->id;
        }

        $submission = AssignmentSubmission::create([
            'activity_id' => $activityId,
            'user_id' => $userId,
            'file_id' => $fileId,
            'text_content' => $request->input('text_content'),
            'attempt_number' => $currentAttempts + 1,
            'status' => AssignmentSubmission::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        // Auto-complete activity if completion_type is auto_submit via CompletionService
        if ($activity->completion_type === Activity::COMPLETION_AUTO_SUBMIT) {
            $this->completionService->completeActivity($user, $activity);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã nộp bài thành công!',
                'submission' => $submission->load('file'),
            ]);
        }

        return back()->with('success', 'Đã nộp bài thành công! Hoạt động đã được ghi nhận.');
    }

    /**
     * View the student's own submissions for an activity.
     */
    public function mySubmissions(Request $request, $activityId)
    {
        $activity = Activity::with('lesson.course')->findOrFail($activityId);
        $userId = $request->user()->id;

        $submissions = AssignmentSubmission::where('activity_id', $activityId)
            ->where('user_id', $userId)
            ->with('file')
            ->orderBy('attempt_number', 'desc')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'activity' => $activity,
                'submissions' => $submissions,
            ]);
        }

        return back()->with('submissions', $submissions);
    }

    /**
     * Teacher/Admin grades a submission.
     */
    public function grade(Request $request, $submissionId)
    {
        $request->validate([
            'grade' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string|max:5000',
            'status' => 'nullable|in:graded,returned',
        ]);

        $submission = AssignmentSubmission::with('activity.lesson.course', 'user')->findOrFail($submissionId);

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'status' => $request->input('status', AssignmentSubmission::STATUS_GRADED),
            'graded_by' => $request->user()->id,
            'graded_at' => now(),
        ]);

        // Auto-complete activity if completion_type is auto_grade and score meets passing grade via CompletionService
        $activity = $submission->activity;
        if ($activity && $activity->completion_type === Activity::COMPLETION_AUTO_GRADE) {
            $passing = (float) ($activity->passing_grade ?? 0);
            if ((float) $submission->grade >= $passing) {
                $this->completionService->completeActivity(
                    $submission->user,
                    $activity,
                    ['score' => (int) $submission->grade, 'max_score' => 100]
                );
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã chấm điểm thành công!',
                'submission' => $submission->fresh()->load(['user', 'file', 'grader']),
            ]);
        }

        $studentName = $submission->user->name ?? 'Học viên';
        return back()->with('success', "✅ Đã chấm điểm bài nộp của '{$studentName}': {$submission->grade} điểm.");
    }

    /**
     * List all submissions for an activity (teacher/admin view).
     */
    public function activitySubmissions(Request $request, $activityId)
    {
        $activity = Activity::with('lesson.course')->findOrFail($activityId);

        $submissions = AssignmentSubmission::where('activity_id', $activityId)
            ->with(['user', 'file', 'grader'])
            ->orderBy('submitted_at', 'desc')
            ->get();

        if ($request->expectsJson()) {
            return response()->json([
                'activity' => $activity,
                'submissions' => $submissions,
                'stats' => [
                    'total' => $submissions->count(),
                    'graded' => $submissions->where('status', 'graded')->count(),
                    'pending' => $submissions->where('status', 'submitted')->count(),
                    'avg_grade' => $submissions->where('status', 'graded')->avg('grade'),
                ],
            ]);
        }

        return back();
    }

    /**
     * Download a submission file.
     */
    public function downloadFile($submissionId)
    {
        $submission = AssignmentSubmission::with('file')->findOrFail($submissionId);

        if (!$submission->file) {
            return back()->with('error', 'Bài nộp này không có file đính kèm.');
        }

        $file = $submission->file;
        $storagePath = storage_path('app/public/' . $file->path);

        if (!file_exists($storagePath)) {
            return back()->with('error', 'File không tồn tại trên hệ thống.');
        }

        return response()->download($storagePath, $file->original_name);
    }
}
