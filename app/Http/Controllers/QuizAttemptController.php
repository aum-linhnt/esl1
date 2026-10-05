<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Services\LMS\QuizAttemptContextService;
use Illuminate\Http\Request;

final class QuizAttemptController extends Controller
{
    public function store(Request $request, string $activityId, QuizAttemptContextService $contexts)
    {
        $activity = Activity::with('lesson.course')->findOrFail($activityId);
        $attempt = $contexts->start($request->user(), $activity);

        return response()->json(['attempt_id' => (string) $attempt->id, 'started_at' => $attempt->started_at->toISOString(),
            'questions' => $attempt->question_snapshot], 201);
    }
}
