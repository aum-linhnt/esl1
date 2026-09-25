<?php

namespace App\Integrations\AiTutor;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use TDSoft\AiTutor\Contracts\KnowledgeSourceAdapter;
use TDSoft\AiTutor\Core\AiException;

final class WebsiteKnowledgeSourceAdapter implements KnowledgeSourceAdapter
{
    public function canReadLesson(string $actorId, string $lessonId): bool
    {
        if (! (new WebsiteLmsAdapter)->canAccessLesson($actorId, $lessonId)) {
            return false;
        }
        $user = User::find($actorId);
        $lesson = Lesson::find($lessonId);
        if (! $user || ! $lesson || ! $lesson->is_visible) {
            return false;
        }
        $enrollment = $user->getEnrollment($lesson->course_id);

        return $user->isAdmin() || $user->isTeacher() || $lesson->is_free_trial
            || ($enrollment && $enrollment->hasValidAccess());
    }

    private function authorize(string $actorId): void
    {
        $user = User::find($actorId);
        if (! $user || ! $user->isAdmin() || ! $user->isActive()) {
            throw new AiException('AI_KNOWLEDGE_FORBIDDEN');
        }
    }

    public function courses(string $actorId): array
    {
        $this->authorize($actorId);

        return Course::orderBy('title')->limit(500)->get(['id', 'title'])->map(fn ($course) => [
            'id' => (string) $course->id, 'title' => $course->title,
        ])->all();
    }

    public function lessons(string $actorId, string $courseId): array
    {
        $this->authorize($actorId);
        if (! Course::whereKey($courseId)->exists()) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        $lessons = Lesson::where('course_id', $courseId)->where('is_visible', true)
            ->orderBy('order')->orderBy('id')->limit(201)->get(['id', 'title', 'summary']);
        if ($lessons->count() > 200) {
            throw new AiException('AI_SYNC_COURSE_TOO_LARGE');
        }

        return $lessons->map(function ($lesson) {
            // Strict field allowlist: never read activities, quiz banks, attempts or answer keys.
            $summary = preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is', '', (string) $lesson->summary);
            $summary = preg_replace('~</?(?:p|div|br|h[1-6]|li)[^>]*>~i', "\n", $summary);
            $summary = trim(html_entity_decode(strip_tags($summary), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $title = trim(html_entity_decode(strip_tags((string) $lesson->title), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            return [
                'lesson_id' => (string) $lesson->id, 'title' => mb_substr($title, 0, 191),
                'content' => $summary === '' ? '' : $title."\n\n".$summary,
                'warning' => $summary === '' ? 'Bài chưa có tóm tắt, bỏ qua.'
                    : 'Chỉ tiêu đề và tóm tắt; không có nội dung hoạt động/video/PDF. Kiểm tra độ đầy đủ trước khi publish.',
            ];
        })->all();
    }
}
