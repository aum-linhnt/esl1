<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\LMS\CompletionService;
use App\Services\LMS\QuizAttemptContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'attempt_id' => 'nullable|string|max:191',
        ]);

        $result = DB::transaction(function () use ($user, $activity, $validated) {
            if (! empty($validated['attempt_id'])) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                // Submission may arrive just after the timer expires; still settle the
                // owned in-progress attempt, while AI access stops exactly at expiry.
                $contexts = app(QuizAttemptContextService::class);
                $contexts->assertAccess($user, $activity);
                $attempt = QuizAttempt::whereKey($validated['attempt_id'])
                    ->where('user_id', $user->id)->where('activity_id', $activity->id)->lockForUpdate()->first();
                abort_unless($attempt && $attempt->status === QuizAttempt::STATUS_IN_PROGRESS, 403);
            }
            $result = $this->completionService->completeActivity($user, $activity, $validated);
            if (isset($attempt) && ! empty($result['trial_mode'])) {
                $attempt->update(['status' => QuizAttempt::STATUS_ABANDONED]);
            }
            if ($activity->type === Activity::TYPE_QUIZ && ($result['success'] || ! empty($result['trial_mode']))) {
                QuizAttempt::where('activity_id', $activity->id)->where('user_id', $user->id)
                    ->where('status', QuizAttempt::STATUS_IN_PROGRESS)->update(['status' => QuizAttempt::STATUS_ABANDONED]);
            }

            return $result;
        });

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
