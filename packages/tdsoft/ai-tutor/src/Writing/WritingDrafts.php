<?php

namespace TDSoft\AiTutor\Writing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\SubjectEnglish\EnglishProfile;

final class WritingDrafts
{
    public function __construct(private WritingAccess $access) {}

    public function create(EnglishProfile $profile, string $task, string $topic, string $content = '', ?string $lessonId = null, ?string $courseId = null): array
    {
        $actor = $this->access->check($lessonId, $courseId);
        $profile->validateTask('writing', $task);
        $this->text($content);
        if (trim($topic) === '' || strlen($topic) > 5000 || ! mb_check_encoding($topic, 'UTF-8')) {
            throw new AiException('AI_WRITING_DRAFT_INVALID');
        }

        return DB::transaction(function () use ($actor, $profile, $task, $topic, $content, $lessonId, $courseId) {
            $id = (string) Str::uuid();
            DB::table('tutor_ai_writing_drafts')->insert([
                'id' => $id, 'actor_id' => $actor, 'lesson_id' => $lessonId, 'course_id' => $courseId,
                'profile' => json_encode($profile->toArray(), JSON_THROW_ON_ERROR), 'task' => $task,
                'topic' => $topic, 'content' => $content, 'revision' => 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->revision($id, 1, $content);

            return $this->get($id);
        });
    }

    public function get(string $id): array
    {
        $draft = DB::table('tutor_ai_writing_drafts')->where('id', $id)->first();
        if (! $draft) {
            throw new AiException('AI_WRITING_NOT_FOUND');
        }
        $this->access->owned($draft);

        return ['id' => $draft->id, 'revision' => (int) $draft->revision, 'content' => $draft->content,
            'profile' => json_decode($draft->profile, true, flags: JSON_THROW_ON_ERROR), 'task' => $draft->task,
            'topic' => $draft->topic, 'lesson_id' => $draft->lesson_id, 'course_id' => $draft->course_id];
    }

    public function listing(int $page = 1): array
    {
        $actor = $this->access->check();
        $rows = DB::table('tutor_ai_writing_drafts')->where('actor_id', $actor)
            ->orderByDesc('updated_at')->orderByDesc('id')->offset(($page - 1) * 20)->limit(21)->get();
        $items = [];
        foreach ($rows->take(20) as $draft) {
            try {
                $this->access->owned($draft);
                $items[] = ['id' => $draft->id, 'topic' => $draft->topic, 'task' => $draft->task,
                    'revision' => (int) $draft->revision, 'updated_at' => $draft->updated_at];
            } catch (AiException) {
                // Revoked lesson titles are not returned in history.
            }
        }

        return ['data' => $items, 'page' => $page, 'next_page' => $rows->count() > 20 ? $page + 1 : null];
    }

    public function save(string $id, int $expectedRevision, string $content): array
    {
        $this->text($content);

        return DB::transaction(function () use ($id, $expectedRevision, $content) {
            $draft = DB::table('tutor_ai_writing_drafts')->where('id', $id)->lockForUpdate()->first();
            if (! $draft) {
                throw new AiException('AI_WRITING_NOT_FOUND');
            }
            $this->access->owned($draft);
            if ((int) $draft->revision !== $expectedRevision) {
                throw new AiException('AI_WRITING_REVISION_CONFLICT');
            }
            if ($draft->content !== $content) {
                DB::table('tutor_ai_writing_drafts')->where('id', $id)->update([
                    'content' => $content, 'revision' => $expectedRevision + 1, 'updated_at' => now(),
                ]);
                $this->revision($id, $expectedRevision + 1, $content);
            }

            return $this->get($id);
        }, 3);
    }

    private function revision(string $id, int $revision, string $content): void
    {
        DB::table('tutor_ai_writing_revisions')->insert(['id' => (string) Str::uuid(), 'draft_id' => $id,
            'revision' => $revision, 'content' => $content, 'created_at' => now()]);
    }

    private function text(string $content): void
    {
        if (strlen($content) > 20000 || ! mb_check_encoding($content, 'UTF-8')) {
            throw new AiException('AI_WRITING_DRAFT_INVALID');
        }
    }
}
