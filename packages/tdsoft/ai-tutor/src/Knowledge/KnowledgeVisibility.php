<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\LessonContext;

final class KnowledgeVisibility
{
    public static function query(LessonContext $context): Builder
    {
        return DB::table('tutor_ai_knowledge_chunks as c')
            ->join('tutor_ai_knowledge_document_versions as v', 'v.id', '=', 'c.version_id')
            ->join('tutor_ai_knowledge_documents as d', 'd.id', '=', 'v.document_id')
            ->whereColumn('d.published_version_id', 'v.id')->where('v.status', 'published')
            ->where('c.indexed', true)
            ->where('d.visibility', 'learners')->where('d.contains_answers', false)
            ->where('d.course_id', $context->courseId)->where('d.lesson_id', $context->lessonId)
            ->where('d.subject', $context->subject)
            ->where(fn ($q) => $q->where('d.level', '')->orWhere('d.level', $context->level));
    }
}
