<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Contracts\KnowledgeSourceAdapter;
use TDSoft\AiTutor\Core\AiException;

final class CourseSyncService
{
    public function __construct(private Access $access, private KnowledgeSourceAdapter $source,
        private TextChunker $chunker, private KnowledgeService $knowledge) {}

    public function courses(): array
    {
        $this->access->administrator();

        return $this->source->courses($this->access->actors->resolve()->id);
    }

    public function preview(string $courseId): array
    {
        $this->access->administrator();
        if (! Schema::hasTable(SyncSchema::TABLE)) {
            throw new AiException('AI_SYNC_MIGRATION_REQUIRED');
        }
        $rows = $this->source->lessons($this->access->actors->resolve()->id, $courseId);
        if (count($rows) > 200) {
            throw new AiException('AI_SYNC_COURSE_TOO_LARGE');
        }
        $results = [];
        foreach ($rows as $row) {
            $context = $this->access->lesson($row['lesson_id'], $courseId);
            $chunks = trim($row['content']) === '' ? [] : $this->chunker->split($row['content'], 'text');
            $fingerprint = hash('sha256', json_encode([
                $row['title'], $row['content'], $context->subject, $context->level,
                config('ai-tutor.provider'), config('ai-tutor.embedding_model'), 'sync-v1',
            ], JSON_THROW_ON_ERROR));
            $link = DB::table(SyncSchema::TABLE)->where('id', $this->key($courseId, $row['lesson_id']))->first();
            $results[] = [
                'lesson_id' => $row['lesson_id'], 'title' => $row['title'], 'content' => $row['content'],
                'warning' => $row['warning'] ?? '', 'fingerprint' => $fingerprint, 'chunks' => count($chunks),
                'status' => ! $chunks ? 'empty' : ($link?->fingerprint === $fingerprint ? 'unchanged' : ($link ? 'changed' : 'new')),
                'document_id' => $link?->document_id, 'version_id' => $link?->version_id,
            ];
        }

        return $results;
    }

    /** No provider call or job dispatch: creates draft versions only. */
    public function sync(string $courseId, array $selected): array
    {
        if (! $selected || count($selected) > 50) {
            throw new AiException('AI_SYNC_SELECTION_INVALID');
        }
        $rows = array_column($this->preview($courseId), null, 'lesson_id');
        $seen = [];
        foreach ($selected as $selection) {
            $id = $selection['lesson_id'];
            if (isset($seen[$id]) || ! isset($rows[$id]) || $rows[$id]['status'] === 'empty'
                || ! hash_equals($rows[$id]['fingerprint'], $selection['fingerprint'])) {
                throw new AiException('AI_SYNC_PREVIEW_STALE');
            }
            $seen[$id] = true;
        }

        return DB::transaction(function () use ($selected, $rows, $courseId) {
            $result = [];
            // Stable lock ordering across concurrent batches.
            usort($selected, fn ($a, $b) => strcmp($a['lesson_id'], $b['lesson_id']));
            foreach ($selected as $selection) {
                $row = $rows[$selection['lesson_id']];
                $key = $this->key($courseId, $row['lesson_id']);
                DB::table(SyncSchema::TABLE)->insertOrIgnore(['id' => $key, 'created_at' => now(), 'updated_at' => now()]);
                $link = DB::table(SyncSchema::TABLE)->where('id', $key)->lockForUpdate()->first();
                if ($link->fingerprint === $row['fingerprint']) {
                    $result[] = ['lesson_id' => $row['lesson_id'], 'document_id' => $link->document_id,
                        'version_id' => $link->version_id, 'status' => 'unchanged'];

                    continue;
                }
                if ($link->document_id) {
                    $document = $this->knowledge->document($link->document_id);
                    $context = $this->access->lesson($row['lesson_id'], $courseId);
                    if ($document->lesson_id !== $row['lesson_id'] || $document->course_id !== $courseId) {
                        throw new AiException('AI_CONTEXT_FORBIDDEN');
                    }
                    if ($document->subject !== $context->subject || $document->level !== $context->level) {
                        throw new AiException('AI_SYNC_CONTEXT_CHANGED');
                    }
                    $version = $this->knowledge->version($document->id, $row['content'], 'text');
                    $documentId = $document->id;
                    $versionId = $version->id;
                } else {
                    $created = $this->knowledge->create([
                        'lesson_id' => $row['lesson_id'], 'title' => $row['title'],
                        'content' => $row['content'], 'format' => 'text', 'visibility' => 'learners', 'contains_answers' => false,
                    ]);
                    $documentId = $created->id;
                    $versionId = $created->version_id;
                }
                // Metadata is unchanged while an older version is published.
                DB::table(SyncSchema::TABLE)->where('id', $key)->update([
                    'document_id' => $documentId, 'version_id' => $versionId,
                    'fingerprint' => $row['fingerprint'], 'updated_at' => now(),
                ]);
                $result[] = ['lesson_id' => $row['lesson_id'], 'document_id' => $documentId,
                    'version_id' => $versionId, 'status' => 'draft'];
            }

            return $result;
        }, 3);
    }

    private function key(string $course, string $lesson): string
    {
        return hash('sha256', json_encode(['lms-lesson-v1', $course, $lesson], JSON_THROW_ON_ERROR));
    }
}
