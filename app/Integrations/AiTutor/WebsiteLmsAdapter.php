<?php

namespace App\Integrations\AiTutor;

use App\Models\Lesson;
use App\Models\QuestionBank;
use App\Models\User;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\LessonContext;
use TDSoft\AiTutor\Core\QuestionContext;

final class WebsiteLmsAdapter implements LmsContextAdapter
{
    public function canAccessLesson(string $userId, string $lessonId): bool
    {
        $user = User::find($userId);
        $lesson = Lesson::find($lessonId);
        if (! $user || ! $lesson || $user->isBlocked() || $user->isTrialExpired()) {
            return false;
        }
        if ($user->isAdmin() || $user->isTeacher()) {
            return true;
        }
        if (! $lesson->is_visible) {
            return false;
        }
        $enrollment = $user->getEnrollment($lesson->course_id);
        if ($enrollment && ! $enrollment->hasValidAccess()) {
            return false;
        }

        return $enrollment ? $lesson->isUnlockedFor($user) : ($lesson->is_free_trial || $lesson->hasTrialActivities());
    }

    public function getLessonContext(string $userId, string $lessonId): LessonContext
    {
        if (! $this->canAccessLesson($userId, $lessonId)) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        $lesson = Lesson::with('course')->findOrFail($lessonId);
        $user = User::findOrFail($userId);
        $enrollment = $user->getEnrollment($lesson->course_id);
        $fullAccess = $user->isAdmin() || $user->isTeacher() || ($enrollment && $enrollment->hasValidAccess());
        // Never serialize activity content or question-bank records containing answer keys.
        $content = (string) $lesson->title;
        if ($fullAccess || $lesson->is_free_trial) {
            $content .= "\n".(string) $lesson->summary;
        }

        return new LessonContext((string) $lesson->course_id, (string) $lesson->id, $content,
            level: (string) ($lesson->course->level ?? ''), answerPolicy: 'hints_only');
    }

    public function getQuestionContext(string $userId, string $questionId, string $lessonId): QuestionContext
    {
        $context = $this->getLessonContext($userId, $lessonId);
        $question = QuestionBank::find($questionId);
        if (! $question || ($question->course_id !== null && (string) $question->course_id !== $context->courseId)) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        $user = User::findOrFail($userId);
        $enrollment = $user->getEnrollment((int) $context->courseId);
        $fullAccess = $user->isAdmin() || $user->isTeacher() || ($enrollment && $enrollment->hasValidAccess());
        $activities = Lesson::findOrFail($lessonId)->activities()->where('is_visible', true)->where('type', 'quiz')->get();
        $belongs = $activities->contains(function ($activity) use ($questionId, $fullAccess) {
            $content = $activity->content;

            return ($fullAccess || $activity->is_free_trial) && is_array($content)
                && ($content['source_mode'] ?? '') === 'bank_manual'
                && in_array($questionId, array_map('strval', $content['question_ids'] ?? []), true);
        });
        // Random-bank/inline questions require an attempt-bound context in Phase 3.
        if (! $belongs) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        $options = [];
        foreach ($question->options as $option) {
            $text = is_array($option) ? ($option['text'] ?? null) : $option;
            if (is_string($text)) {
                $options[] = $text;
            }
        }

        return new QuestionContext((string) $question->id, $context, (string) $question->question_text, $options);
    }
}
