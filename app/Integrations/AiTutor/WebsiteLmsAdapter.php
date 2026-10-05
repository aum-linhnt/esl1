<?php

namespace App\Integrations\AiTutor;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\QuestionBank;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\LMS\QuizAttemptContextService;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TDSoft\AiTutor\Contracts\AttemptQuestionContextAdapter;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\LessonContext;
use TDSoft\AiTutor\Core\QuestionContext;

final class WebsiteLmsAdapter implements AttemptQuestionContextAdapter, LmsContextAdapter
{
    public function canAccessLesson(string $userId, string $lessonId): bool
    {
        $user = User::find($userId);
        $lesson = Lesson::find($lessonId);
        if (! $user || ! $lesson || $user->isBlocked() || $user->isTrialExpired()) {
            return false;
        }
        $enrollment = $user->getEnrollment($lesson->course_id);
        if ($lesson->course->canPreviewFor($user, $enrollment)) {
            return true;
        }
        if (! $lesson->course->is_published || ! $lesson->is_visible) {
            return false;
        }
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
        $fullAccess = $lesson->course->canPreviewFor($user, $enrollment) || ($enrollment && $enrollment->hasValidAccess());
        // Never serialize activity content or question-bank records containing answer keys.
        $content = (string) $lesson->title;
        if ($fullAccess || $lesson->is_free_trial) {
            $content .= "\n".(string) $lesson->summary;
        }

        return new LessonContext((string) $lesson->course_id, (string) $lesson->id, $content,
            level: (string) ($lesson->course->level ?? ''),
            answerPolicy: (string) ($lesson->ai_answer_policy ?? 'hints_only'),
            teacherAllowsSolution: (bool) $lesson->ai_teacher_solution_allowed,
            isExam: (bool) $lesson->ai_exam_mode);
    }

    public function getAttemptQuestionContext(string $userId, string $questionId, string $lessonId, string $attemptId): QuestionContext
    {
        $context = $this->getLessonContext($userId, $lessonId);
        $attempt = QuizAttempt::with('activity.lesson.course')->find($attemptId);
        if (! $attempt || (string) $attempt->user_id !== $userId || (string) $attempt->activity->lesson_id !== $lessonId) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        try {
            app(QuizAttemptContextService::class)->active(User::findOrFail($userId), $attempt->activity, $attemptId);
        } catch (HttpException) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        $question = collect($attempt->question_snapshot)->first(fn ($question) => (string) ($question['id'] ?? '') === $questionId);
        if (! $question) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        // Whitelist learner-visible text. Never derive options from correct_answer,
        // answers_payload, explanation, grading metadata or client-supplied content.
        $options = $question['options'] ?? [];
        if (is_string($options)) {
            $options = json_decode($options, true) ?: [];
        }
        $safeOptions = [];
        $append = function ($values) use (&$safeOptions) {
            foreach ((array) $values as $option) {
                $text = is_array($option) ? ($option['text'] ?? null) : $option;
                if (is_string($text)) {
                    $safeOptions[] = $text;
                }
            }
        };
        if (($question['question_type'] ?? '') === 'matching') {
            // Keep the two lists separate and unordered to avoid conveying pairings.
            $append($options['left'] ?? []);
            $leftCount = count($safeOptions);
            $append($options['right'] ?? []);
            $right = array_slice($safeOptions, $leftCount);
            sort($right, SORT_STRING);
            $safeOptions = array_merge(array_slice($safeOptions, 0, $leftCount), $right);
        } else {
            $append($options);
        }

        return new QuestionContext($questionId, $context, (string) ($question['question'] ?? $question['question_text'] ?? ''), $safeOptions);
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
        $fullAccess = Course::findOrFail($context->courseId)->canPreviewFor($user, $enrollment)
            || ($enrollment && $enrollment->hasValidAccess());
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
