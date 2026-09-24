<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Contracts\VectorStore;
use TDSoft\AiTutor\Core\LessonContext;

final class LocalVectorStore implements VectorStore
{
    public function put(string $chunkId, array $vector, string $model): void
    {
        DB::table('tutor_ai_knowledge_chunks')->where('id', $chunkId)->update([
            'embedding' => json_encode(Vector::validate($vector), JSON_THROW_ON_ERROR),
            'embedding_model' => $model,
        ]);
    }

    public function search(array $vector, string $model, LessonContext $context, int $limit): array
    {
        Vector::validate($vector);
        $hits = [];
        // Stream candidates: local exact cosine index, replaceable by an ANN adapter.
        foreach (KnowledgeVisibility::query($context)->where('c.embedding_model', $model)
            ->whereNotNull('c.embedding')->select('c.id', 'c.embedding')->cursor() as $chunk) {
            $score = Vector::cosine($vector, json_decode($chunk->embedding, true, flags: JSON_THROW_ON_ERROR));
            if ($score >= (float) config('ai-tutor.knowledge.min_similarity', 0.25)) {
                $hits[] = ['id' => $chunk->id, 'score' => $score];
                usort($hits, fn ($a, $b) => $b['score'] <=> $a['score']);
                $hits = array_slice($hits, 0, max(1, min(10, $limit)));
            }
        }

        return $hits;
    }
}
