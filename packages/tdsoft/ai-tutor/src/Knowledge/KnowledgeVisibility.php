<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\KnowledgeSourceAdapter;
use TDSoft\AiTutor\Core\LessonContext;

final class KnowledgeVisibility
{
    public static function query(LessonContext $context): Builder
    {
        $query = DB::table('tutor_ai_knowledge_chunks as c')
            ->join('tutor_ai_knowledge_document_versions as v', 'v.id', '=', 'c.version_id')
            ->join('tutor_ai_knowledge_documents as d', 'd.id', '=', 'v.document_id')
            ->whereColumn('d.published_version_id', 'v.id')->where('v.status', 'published')
            ->where('c.indexed', true)
            ->where('d.visibility', 'learners')->where('d.contains_answers', false)
            ->where('d.course_id', $context->courseId)->where('d.lesson_id', $context->lessonId)
            ->where('d.subject', $context->subject)
            ->where(fn ($q) => $q->where('d.level', '')->orWhere('d.level', $context->level));
        if (Schema::hasTable(SyncSchema::TABLE)
            && ! app(KnowledgeSourceAdapter::class)->canReadLesson(
                app(ActorResolver::class)->resolve()->id, $context->lessonId)) {
            $query->whereNotExists(fn ($q) => $q->selectRaw('1')->from(SyncSchema::TABLE.' as sync')
                ->whereColumn('sync.document_id', 'd.id'));
        }

        return $query;
    }
}
