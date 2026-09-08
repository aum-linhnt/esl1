<?php

namespace App\Http\Controllers;

use App\Services\Assessment\AssessmentService;
use App\Services\Storage\ActivityTrackerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuizApiController extends Controller
{
    protected AssessmentService $assessmentService;
    protected ActivityTrackerService $activityTrackerService;

    public function __construct(
        AssessmentService $assessmentService,
        ActivityTrackerService $activityTrackerService
    ) {
        $this->assessmentService = $assessmentService;
        $this->activityTrackerService = $activityTrackerService;
    }

    /**
     * Single practice question AJAX check.
     */
    public function checkAnswer(Request $request): JsonResponse
    {
        $request->validate([
            'question_id' => 'required|exists:question_banks,id',
            'answer' => 'required|string',
        ]);

        $result = $this->assessmentService->gradeSubmission(
            $request->user()->id,
            null,
            [$request->question_id => $request->answer],
            'quick_practice'
        );

        $detail = $result['details'][0] ?? null;

        return response()->json([
            'correct' => $detail ? $detail['is_correct'] : false,
            'correct_answer' => $detail ? $detail['correct_answer'] : '',
            'explanation' => $detail ? $detail['explanation'] : '',
            'coins' => $result['current_coins'],
        ]);
    }

    /**
     * JSON API: Log real activity telemetry step.
     */
    public function logActivityStep(Request $request): JsonResponse
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id',
            'duration' => 'nullable|integer|min:0',
            'step_data' => 'nullable|array',
        ]);

        $log = $this->activityTrackerService->logStep(
            $request->user()->id,
            $request->activity_id,
            $request->input('duration', 5),
            $request->input('step_data', [])
        );

        return response()->json([
            'success' => true,
            'log_id' => $log->id,
            'duration_total' => $log->duration_seconds,
            'status' => $log->status,
        ]);
    }
}
