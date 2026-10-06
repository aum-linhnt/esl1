<?php

namespace App\Http\Controllers;

use App\Services\Learning\LearnerOverview;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardV2Controller extends Controller
{
    public function index(Request $request, LearnerOverview $overview, \App\Services\Learning\LearningGoals $goals, \App\Services\Learning\StudyTime $time, \App\Services\Learning\AiCreditOverview $credits)
    {
        $user = $request->user();
        $skills = $overview->skills($user);
        $enrollments = $user->enrollments()->whereIn('status', ['active', 'completed'])
            ->whereHas('course', fn ($query) => $query->where('is_published', true))
            ->with('course.lessons')->get()->filter(fn ($enrollment) => $enrollment->hasValidAccess());
        $completedLessons = $user->progress()->where('completed', true)->pluck('lesson_id');
        $nextLesson = $enrollments->flatMap(fn ($enrollment) => $enrollment->course->lessons)
            ->first(fn ($lesson) => $lesson->is_visible && ! $completedLessons->contains($lesson->id)
                && $lesson->isUnlockedFor($user));

        $today = Carbon::today();
        $studyDays = $time->week($user);
        $lastActive = $user->last_active_date;
        $streak = $lastActive && ($lastActive->isSameDay($today) || $lastActive->isSameDay($today->copy()->subDay()))
            ? max(0, (int) $user->streak_count) : 0;
        $weekDays = collect(range(0, 6))->map(function ($offset) use ($today, $lastActive, $streak) {
            $date = $today->copy()->startOfWeek()->addDays($offset);
            return ['label' => $offset === 6 ? 'CN' : 'T'.($offset + 2), 'today' => $date->isSameDay($today),
                'active' => $streak > 0 && $date->lte($lastActive) && $date->gte($lastActive->copy()->subDays($streak - 1))];
        });

        $creditAccount = $credits->account($user);
        $goal = $goals->forUser($user);
        $goalReady = $goals->ready();
        $recommendations = $overview->recommendations($user, $skills, $nextLesson, $goal);

        return response()->view('dashboard-v2', compact('user', 'skills', 'enrollments', 'nextLesson', 'studyDays', 'streak', 'weekDays', 'recommendations', 'goal', 'goalReady', 'creditAccount'))->header('Cache-Control', 'no-store');
    }
}
