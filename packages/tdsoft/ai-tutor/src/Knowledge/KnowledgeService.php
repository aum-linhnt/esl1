<?php

namespace TDSoft\AiTutor\Knowledge;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\VectorStore;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Core\AiRequest;
use Throwable;

final class KnowledgeService
{
    public function __construct(private Access $access, private TextChunker $chunker, private AiExecutionService $ai, private VectorStore $vectors) {}

    public function create(array $data): object
    {
        $this->access->administrator();
        $context = $this->access->lesson($data['lesson_id']);
        if (! in_array($data['visibility'] ?? 'private', ['private', 'learners'], true)
            || trim($data['title']) === '' || mb_strlen($data['title']) > 191) {
            throw new AiException('AI_DOCUMENT_INVALID');
        }
        $id = (string) Str::uuid();

        return DB::transaction(function () use ($data, $context, $id) {
            DB::table('tutor_ai_knowledge_documents')->insert([
                'id' => $id, 'title' => $data['title'], 'lesson_id' => $context->lessonId,
                'course_id' => $context->courseId, 'subject' => $context->subject, 'level' => $context->level,
                'visibility' => $data['visibility'] ?? 'private', 'contains_answers' => (bool) ($data['contains_answers'] ?? false),
                'created_by' => $this->access->actors->resolve()->id, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $version = $this->version($id, $data['content'], $data['format'] ?? 'text');

            return (object) ['id' => $id, 'version_id' => $version->id];
        });
    }

    public function document(string $id): object
    {
        $this->access->administrator();
        $document = DB::table('tutor_ai_knowledge_documents')->where('id', $id)->first();
        if (! $document) {
            throw new AiException('AI_DOCUMENT_NOT_FOUND');
        }
        $this->access->lesson($document->lesson_id, $document->course_id);

        return $document;
    }

    public function version(string $documentId, string $content, string $format): object
    {
        $this->document($documentId);
        $chunks = $this->chunker->split($content, $format);

        return DB::transaction(function () use ($documentId, $content, $format, $chunks) {
            $id = (string) Str::uuid();
            $actor = $this->access->actors->resolve()->id;
            DB::table('tutor_ai_knowledge_document_versions')->insert([
                'id' => $id, 'document_id' => $documentId, 'status' => 'draft', 'format' => $format,
                'content' => $content, 'checksum' => hash('sha256', $content), 'created_by' => $actor,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach ($chunks as $position => $text) {
                DB::table('tutor_ai_knowledge_chunks')->insert([
                    'id' => (string) Str::uuid(), 'version_id' => $id, 'position' => $position, 'content' => $text,
                ]);
            }
            DB::table('tutor_ai_knowledge_processing_jobs')->insert([
                'id' => (string) Str::uuid(), 'version_id' => $id, 'actor_id' => $actor,
                'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);

            return DB::table('tutor_ai_knowledge_document_versions')->where('id', $id)->first();
        });
    }

    public function process(string $versionId, int $batchSize = 100): void
    {
        $version = $this->getVersion($versionId);
        if (in_array($version->status, ['ready', 'published'], true)) {
            return;
        }
        $document = $this->document($version->document_id);
        $claimed = DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $versionId)
            ->whereIn('status', ['pending', 'failed'])->update([
                'status' => 'processing', 'attempts' => DB::raw('attempts + 1'), 'error_code' => null, 'updated_at' => now(),
            ]);
        if (! $claimed) {
            throw new AiException('AI_DOCUMENT_BUSY');
        }
        try {
            foreach (DB::table('tutor_ai_knowledge_chunks')->where('version_id', $versionId)->where('indexed', false)
                ->orderBy('position')->limit(max(1, min(100, $batchSize)))->get() as $chunk) {
                $response = $this->ai->execute(AiRequest::forFeature(
                    'knowledge_embedding', $this->access->actors->resolve(),
                    ['input' => $chunk->content, 'embedding_model' => config('ai-tutor.embedding_model')],
                    $chunk->id, 'knowledge:'.$chunk->id, $document->course_id, $document->lesson_id,
                ));
                $this->vectors->put($chunk->id, $response->data['embedding'] ?? [], $response->model);
                DB::table('tutor_ai_knowledge_chunks')->where('id', $chunk->id)->update([
                    'indexed' => true, 'embedding_model' => $response->model,
                ]);
            }
            DB::transaction(function () use ($versionId) {
                if (DB::table('tutor_ai_knowledge_chunks')->where('version_id', $versionId)->where('indexed', false)->exists()) {
                    DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $versionId)
                        ->update(['status' => 'pending', 'updated_at' => now()]);

                    return;
                }
                DB::table('tutor_ai_knowledge_document_versions')->where('id', $versionId)->update(['status' => 'ready', 'updated_at' => now()]);
                DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $versionId)->update([
                    'status' => 'completed', 'error_code' => null, 'updated_at' => now(),
                ]);
            });
        } catch (Throwable $error) {
            $code = $error instanceof AiException ? $error->errorCode : 'AI_KNOWLEDGE_PROCESSING_FAILED';
            DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $versionId)->update([
                'status' => 'failed', 'error_code' => $code, 'updated_at' => now(),
            ]);
            throw new AiException($code);
        }
    }

    public function getVersion(string $id): object
    {
        $this->access->administrator();
        $version = DB::table('tutor_ai_knowledge_document_versions')->where('id', $id)->first();
        if (! $version) {
            throw new AiException('AI_DOCUMENT_NOT_FOUND');
        }
        $this->document($version->document_id);

        return $version;
    }

    public function versions(string $documentId): array
    {
        $document = $this->document($documentId);

        return ['document' => $document, 'versions' => DB::table('tutor_ai_knowledge_document_versions')
            ->where('document_id', $documentId)->orderByDesc('created_at')->limit(100)
            ->get(['id', 'status', 'format', 'checksum', 'created_by', 'created_at', 'published_at'])->all()];
    }

    public function withdraw(string $documentId): void
    {
        $this->document($documentId);
        DB::transaction(function () use ($documentId) {
            $document = DB::table('tutor_ai_knowledge_documents')->where('id', $documentId)->lockForUpdate()->first();
            if ($document->published_version_id) {
                DB::table('tutor_ai_knowledge_document_versions')->where('id', $document->published_version_id)
                    ->update(['status' => 'ready', 'updated_at' => now()]);
                DB::table('tutor_ai_knowledge_documents')->where('id', $documentId)
                    ->update(['published_version_id' => null, 'updated_at' => now()]);
            }
        });
    }

    public function publish(string $id): void
    {
        $version = $this->getVersion($id);
        DB::transaction(function () use ($id, $version) {
            $document = DB::table('tutor_ai_knowledge_documents')->where('id', $version->document_id)->lockForUpdate()->first();
            $version = DB::table('tutor_ai_knowledge_document_versions')->where('id', $id)->lockForUpdate()->first();
            if (! in_array($version->status, ['ready', 'published'], true)
                || DB::table('tutor_ai_knowledge_chunks')->where('version_id', $id)->where('indexed', false)->exists()) {
                throw new AiException('AI_DOCUMENT_NOT_READY');
            }
            if ($document->published_version_id && $document->published_version_id !== $id) {
                DB::table('tutor_ai_knowledge_document_versions')->where('id', $document->published_version_id)
                    ->update(['status' => 'archived', 'updated_at' => now()]);
            }
            DB::table('tutor_ai_knowledge_document_versions')->where('id', $id)->update([
                'status' => 'published', 'published_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('tutor_ai_knowledge_documents')->where('id', $document->id)->update([
                'published_version_id' => $id, 'updated_at' => now(),
            ]);
        });
    }
}
