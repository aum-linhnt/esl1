<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\SubjectEnglish\EnglishProfile;
use TDSoft\AiTutor\Writing\ProcessWritingSubmission;
use TDSoft\AiTutor\Writing\WritingDrafts;
use TDSoft\AiTutor\Writing\WritingSubmissions;

final class WritingController
{
    public function __construct(private WritingDrafts $drafts, private WritingSubmissions $submissions) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'framework' => 'required|string', 'target' => 'required|string', 'feedback_language' => 'sometimes|string',
            'task' => 'required|string', 'topic' => 'required|string|max:5000', 'content' => 'sometimes|nullable|string|max:20000',
            'lesson_id' => 'nullable|string|max:191', 'course_id' => 'nullable|string|max:191|required_with:lesson_id',
            ...$this->protectedFields(),
        ]);

        return new JsonResponse($this->drafts->create(new EnglishProfile($data['framework'], $data['target'], $data['feedback_language'] ?? 'vi'),
            $data['task'], $data['topic'], $data['content'] ?? '', $data['lesson_id'] ?? null, $data['course_id'] ?? null), 201);
    }

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['page' => 'sometimes|integer|min:1|max:10000']);

        return new JsonResponse($this->drafts->listing($data['page'] ?? 1));
    }

    public function submissions(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['page' => 'sometimes|integer|min:1|max:10000', 'request_id' => 'sometimes|uuid']);

        return new JsonResponse($this->submissions->listing($id, $data['page'] ?? 1, $data['request_id'] ?? null));
    }

    public function show(string $id): JsonResponse
    {
        return new JsonResponse($this->drafts->get($id));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['revision' => 'required|integer|min:1', 'content' => 'present|nullable|string|max:20000',
            ...$this->protectedFields()]);

        return new JsonResponse($this->drafts->save($id, $data['revision'], $data['content'] ?? ''));
    }

    public function submit(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['revision' => 'required|integer|min:1', 'request_id' => 'required|uuid',
            'idempotency_key' => 'required|string|max:191', 'confirm_cost' => 'required|accepted', ...$this->protectedFields()]);
        $this->queueReady();
        $draft = $this->drafts->get($id);
        // Browser cannot select an old or permissive rubric/prompt version.
        $rubricId = DB::table('tutor_ai_assessment_rubric_versions as v')
            ->join('tutor_ai_assessment_rubrics as r', 'r.id', '=', 'v.rubric_id')
            ->where('r.key', 'english_'.$draft['task'])->where('r.skill', 'writing')->orderByDesc('v.version')->value('v.id');
        if (! $rubricId) {
            throw new AiException('AI_RUBRIC_NOT_FOUND');
        }
        // Replays retain the rubric recorded at the original submit, even after a rubric upgrade.
        $existing = DB::table('tutor_ai_writing_submissions')->where('draft_id', $id)->where('idempotency_key', $data['idempotency_key'])->first();
        $result = $this->submissions->submit($id, $data['revision'], $existing->rubric_version_id ?? $rubricId,
            $data['request_id'], $data['idempotency_key']);
        if ($result['status'] === 'queued') {
            ProcessWritingSubmission::dispatch($result['id']);
        }

        return new JsonResponse($result, $result['status'] === 'completed' ? 200 : 202);
    }

    public function submission(string $id): JsonResponse
    {
        return new JsonResponse($this->submissions->get($id));
    }

    public function retry(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['request_id' => 'required|uuid', 'idempotency_key' => 'required|string|max:191',
            'confirm_retry' => 'required|accepted', ...$this->protectedFields()]);
        $this->queueReady();
        $result = $this->submissions->retry($id, $data['request_id'], $data['idempotency_key'], true);
        if ($result['status'] === 'queued') {
            ProcessWritingSubmission::dispatch($result['id']);
        }

        return new JsonResponse($result, 202);
    }

    private function protectedFields(): array
    {
        return array_fill_keys(['actor_id', 'user_id', 'rubric_version_id', 'prompt', 'instructions', 'provider', 'model',
            'feature', 'billing_mode', 'answer_policy', 'teacher_allows_solution', 'is_exam', 'score', 'result'], 'prohibited');
    }

    private function queueReady(): void
    {
        $connection = config('queue.default');
        $driver = config('queue.connections.'.$connection.'.driver');
        if (! in_array($driver, ['database', 'redis'], true)
            || (int) config('queue.connections.'.$connection.'.retry_after', 0) <= 60) {
            throw new AiException('AI_WRITING_QUEUE_INVALID');
        }
    }
}
