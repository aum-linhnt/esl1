<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Knowledge\Access;
use TDSoft\AiTutor\Knowledge\KnowledgeService;
use TDSoft\AiTutor\Knowledge\ProcessKnowledgeVersion;
use TDSoft\AiTutor\Providers\ProviderReadiness;

final class KnowledgeController
{
    public function __construct(private KnowledgeService $knowledge, private Access $access) {}

    public function index(Request $request): JsonResponse
    {
        $this->access->administrator();
        $data = $request->validate(['lesson_id' => 'required|string|max:191']);
        $context = $this->access->lesson($data['lesson_id']);

        return new JsonResponse(DB::table('tutor_ai_knowledge_documents')->where('lesson_id', $context->lessonId)
            ->where('course_id', $context->courseId)->orderByDesc('created_at')->limit(100)->get());
    }

    public function store(Request $request): JsonResponse
    {
        $this->access->administrator();
        $data = $request->validate([
            'title' => 'required|string|max:191', 'lesson_id' => 'required|string|max:191',
            'content' => 'required|string|max:200000', 'format' => 'sometimes|in:text,markdown,html',
            'visibility' => 'sometimes|in:private,learners', 'contains_answers' => 'sometimes|boolean',
        ]);

        return new JsonResponse($this->knowledge->create($data), 201);
    }

    public function version(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['content' => 'required|string|max:200000', 'format' => 'sometimes|in:text,markdown,html']);
        $version = $this->knowledge->version($id, $data['content'], $data['format'] ?? 'text');

        return new JsonResponse(['id' => $version->id, 'status' => $version->status], 201);
    }

    public function versions(string $id): JsonResponse
    {
        return new JsonResponse($this->knowledge->versions($id));
    }

    public function preview(string $id): JsonResponse
    {
        $version = $this->knowledge->getVersion($id);

        return new JsonResponse(['id' => $version->id, 'content' => $version->content, 'format' => $version->format]);
    }

    public function withdraw(string $id): JsonResponse
    {
        $this->knowledge->withdraw($id);

        return new JsonResponse(['id' => $id, 'status' => 'unpublished']);
    }

    public function process(string $id, ProviderReadiness $readiness): JsonResponse
    {
        if ($error = $readiness->embeddingError()) {
            return new JsonResponse(['error' => ['code' => $error]], 409);
        }
        $this->knowledge->getVersion($id);
        ProcessKnowledgeVersion::dispatch($id)->afterCommit();

        return new JsonResponse(['version_id' => $id, 'status' => 'queued'], 202);
    }

    public function status(string $id): JsonResponse
    {
        $version = $this->knowledge->getVersion($id);
        $job = DB::table('tutor_ai_knowledge_processing_jobs')->where('version_id', $id)->first();

        return new JsonResponse(['id' => $id, 'status' => $version->status, 'processing_status' => $job?->status ?? 'not_queued',
            'error_code' => $job?->error_code]);
    }

    public function publish(string $id): JsonResponse
    {
        try {
            $this->knowledge->publish($id);
        } catch (AiException $error) {
            return new JsonResponse(['error' => ['code' => $error->errorCode]], 409);
        }

        return new JsonResponse(['id' => $id, 'status' => 'published']);
    }
}
