<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Lesson;
use App\Services\LMS\CompletionService;
use Illuminate\Http\Request;

class ActivityCompletionController extends Controller
{
    public function __construct(
        private CompletionService $completionService
    ) {}

    /**
     * Mark an activity as completed via unified CompletionService.
     */
    public function complete(Request $request, $activityId)
    {
        $user = $request->user();
        $activity = Activity::with('lesson.course')->findOrFail($activityId);

        $validated = $request->validate([
            'score' => 'nullable|numeric|min:0',
            'max_score' => 'nullable|numeric|min:1',
            'time_spent_seconds' => 'nullable|integer|min:0',
            'answers_payload' => 'nullable|array',
            'started_at' => 'nullable|date',
        ]);

        $result = $this->completionService->completeActivity($user, $activity, $validated);

        if ($request->expectsJson()) {
            $statusCode = $result['success'] ? 200 : ($result['trial_mode'] ? 200 : 422);
            return response()->json($result, $statusCode);
        }

        if (!$result['success']) {
            if (!empty($result['trial_mode'])) {
                return redirect()->back()->with('info', $result['message']);
            }
            return redirect()->back()->with('error', $result['message']);
        }

        return redirect()->back()->with('success', $result['message']);
    }

    /**
     * Get completion status for all activities in a lesson (JSON API).
     */
    public function status(Request $request, $lessonId)
    {
        $user = $request->user();
        $lesson = Lesson::with('activities')->findOrFail($lessonId);

        $statusData = $this->completionService->getLessonCompletionStatus($user, $lesson);

        return response()->json($statusData);
    }
}
