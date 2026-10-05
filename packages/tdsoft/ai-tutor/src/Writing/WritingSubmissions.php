<?php

namespace TDSoft\AiTutor\Writing;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Assessment\AssessmentResult;
use TDSoft\AiTutor\Assessment\RubricDefinition;
use TDSoft\AiTutor\Assessment\RubricRepository;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Events\WritingAssessmentCompleted;
use Throwable;

final class WritingSubmissions
{
    public function __construct(private WritingAccess $access, private RubricRepository $rubrics, private AiExecutionService $execution) {}

    public function submit(string $draftId, int $revision, string $rubricId, string $requestId, string $key): array
    {
        $this->identifiers($requestId, $key);

        return DB::transaction(function () use ($draftId, $revision, $rubricId, $requestId, $key) {
            $draft = DB::table('tutor_ai_writing_drafts')->where('id', $draftId)->lockForUpdate()->first();
            if (! $draft) {
                throw new AiException('AI_WRITING_NOT_FOUND');
            }
            $this->access->owned($draft, true);
            // Same IDs replay the snapshot even after later autosaves.
            $existing = DB::table('tutor_ai_writing_submissions')->where('idempotency_key', $key)->first();
            if ($existing) {
                if ($existing->draft_id !== $draftId || (int) $existing->revision !== $revision
                    || $existing->rubric_version_id !== $rubricId || $existing->request_id !== $requestId) {
                    throw new AiException('AI_REQUEST_DUPLICATE');
                }

                return $this->get($existing->id);
            }
            if ((int) $draft->revision !== $revision) {
                throw new AiException('AI_WRITING_REVISION_CONFLICT');
            }
            if (mb_strlen(trim($draft->content), 'UTF-8') < 10) {
                throw new AiException('AI_WRITING_SUBMISSION_INVALID');
            }
            // A new ID is not a way to retry a failed/uncertain attempt for the same revision.
            if (DB::table('tutor_ai_writing_submissions')->where('draft_id', $draftId)->where('revision', $revision)->exists()) {
                throw new AiException('AI_WRITING_ALREADY_SUBMITTED');
            }
            if (DB::table('tutor_ai_writing_submissions')->where('draft_id', $draftId)
                ->whereIn('status', ['queued', 'processing', 'reconciliation_required'])->exists()) {
                throw new AiException('AI_WRITING_BUSY');
            }
            $rubric = $this->rubrics->snapshot($rubricId);
            if ($rubric['skill'] !== 'writing' || $rubric['task'] !== $draft->task) {
                throw new AiException('AI_RUBRIC_CONTEXT_CHANGED');
            }
            $feature = DB::table('tutor_ai_writing_submissions')->where('draft_id', $draftId)->where('status', 'completed')->exists()
                ? 'writing_recheck' : 'writing_assessment';
            $id = (string) Str::uuid();
            $payload = WritingPrompt::payload($draft, $rubric);
            $request = $this->request($draft, $feature, $payload, $requestId, $key);
            DB::table('tutor_ai_writing_submissions')->insert([
                'id' => $id, 'draft_id' => $draftId, 'actor_id' => $draft->actor_id,
                'course_id' => $draft->course_id, 'lesson_id' => $draft->lesson_id,
                'revision' => $revision, 'original' => $draft->content, 'profile' => $draft->profile,
                'task' => $draft->task, 'topic' => $draft->topic, 'rubric_version_id' => $rubricId,
                'rubric_snapshot' => json_encode($rubric, JSON_THROW_ON_ERROR),
                'encrypted_payload' => Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
                'feature' => $feature, 'request_id' => $requestId, 'idempotency_key' => $key,
                'fingerprint' => $request->fingerprint(), 'status' => 'queued', 'created_at' => now(), 'updated_at' => now(),
            ]);

            return $this->get($id);
        }, 3);
    }

    public function retry(string $id, string $requestId, string $key, bool $confirmed): array
    {
        $this->identifiers($requestId, $key);
        if (! $confirmed) {
            throw new AiException('AI_WRITING_RETRY_CONFIRMATION_REQUIRED');
        }

        return DB::transaction(function () use ($id, $requestId, $key) {
            $original = $this->record($id);
            DB::table('tutor_ai_writing_drafts')->where('id', $original->draft_id)->lockForUpdate()->first();
            $original = $this->record($id, true);
            $this->access->owned($original, true);
            $child = DB::table('tutor_ai_writing_submissions')->where('retry_of_submission_id', $id)->first();
            if ($child) {
                if ($child->request_id !== $requestId || $child->idempotency_key !== $key) {
                    throw new AiException('AI_REQUEST_DUPLICATE');
                }

                return $this->get($child->id);
            }
            if ($this->recovery($original) !== 'new_attempt') {
                throw new AiException('AI_WRITING_RETRY_BLOCKED');
            }
            if (DB::table('tutor_ai_writing_submissions')->where('draft_id', $original->draft_id)
                ->whereIn('status', ['queued', 'processing', 'reconciliation_required'])->exists()) {
                throw new AiException('AI_WRITING_BUSY');
            }
            $copy = (array) $original;
            $copy['id'] = (string) Str::uuid();
            $copy['retry_of_submission_id'] = $original->id;
            $copy['request_id'] = $requestId;
            $copy['idempotency_key'] = $key;
            $copy['fingerprint'] = $this->prepared($original, $requestId, $key)->fingerprint();
            $copy['status'] = 'queued';
            foreach (['error_code', 'result', 'provider', 'model', 'completed_at'] as $field) {
                $copy[$field] = null;
            }
            $copy['created_at'] = $copy['updated_at'] = now();
            DB::table('tutor_ai_writing_submissions')->insert($copy);

            return $this->get($copy['id']);
        }, 3);
    }

    public function get(string $id): array
    {
        $record = $this->record($id);
        // Assessment feedback must not remain available after the lesson becomes an exam.
        $this->access->owned($record, true);
        $billing = DB::table('tutor_ai_requests')->where('request_id', $record->request_id)->first();

        $result = $record->result ? json_decode($record->result, true, flags: JSON_THROW_ON_ERROR) : null;
        if ($result !== null) {
            // Revalidate stored feedback for old submissions without rewriting scores,
            // billing records or provider responses and without another AI call.
            $result['issues'] = WritingIssues::validate($result['issues'], $record->original);
        }

        return ['id' => $record->id, 'draft_id' => $record->draft_id, 'revision' => (int) $record->revision,
            'original' => $record->original, 'status' => $record->status, 'error_code' => $record->error_code,
            'recovery' => $this->recovery($record), 'request_id' => $record->request_id, 'idempotency_key' => $record->idempotency_key,
            'feature' => $record->feature, 'retry_of_submission_id' => $record->retry_of_submission_id,
            'rubric' => json_decode($record->rubric_snapshot, true, flags: JSON_THROW_ON_ERROR),
            'result' => $result,
            'provider' => $record->provider, 'model' => $record->model,
            'credit_units' => $billing?->actual_units,
            'credit_balance' => DB::table('tutor_ai_credit_accounts')->where('owner_type', 'learner')
                ->where('owner_id', $record->actor_id)->where('scope', 'system')->value('balance')];
    }

    public function listing(string $draftId, int $page = 1, ?string $requestId = null): array
    {
        $draft = DB::table('tutor_ai_writing_drafts')->where('id', $draftId)->first();
        if (! $draft) {
            throw new AiException('AI_WRITING_NOT_FOUND');
        }
        $this->access->owned($draft, true);
        $query = DB::table('tutor_ai_writing_submissions')->where('draft_id', $draftId)->orderByDesc('created_at')->orderByDesc('id');
        if ($requestId !== null) {
            $query->where('request_id', $requestId);
        }
        $rows = $query->offset(($page - 1) * 20)->limit(21)->get();

        return ['data' => $rows->take(20)->map(fn ($row) => [
            'id' => $row->id, 'revision' => (int) $row->revision, 'status' => $row->status,
            'request_id' => $row->request_id, 'retry_of_submission_id' => $row->retry_of_submission_id,
            'created_at' => \Illuminate\Support\Carbon::parse($row->created_at)->toISOString(),
        ])->all(), 'page' => $page, 'next_page' => $rows->count() > 20 ? $page + 1 : null];
    }

    public function process(string $id): void
    {
        $record = DB::transaction(function () use ($id) {
            $record = $this->record($id, true);
            $this->access->owned($record, true);
            if (in_array($record->status, ['completed', 'failed'], true)) {
                return null;
            }
            $billing = DB::table('tutor_ai_requests')->where('request_id', $record->request_id)->first();
            if ($record->status !== 'queued' && (! $billing || $billing->status !== 'completed')) {
                return null; // Another worker or unknown crash: never rerun inference.
            }
            DB::table('tutor_ai_writing_submissions')->where('id', $id)->update(['status' => 'processing', 'updated_at' => now()]);

            return $record;
        }, 3);
        if (! $record) {
            return;
        }
        try {
            $request = $this->prepared($record);
            if (! hash_equals($record->fingerprint, $request->fingerprint())) {
                throw new AiException('AI_WRITING_SNAPSHOT_INVALID');
            }
            $response = $this->execution->execute($request);
            // Billing has settled before validation. Bad JSON cannot erase real provider usage.
            $data = json_decode($response->content, true, flags: JSON_THROW_ON_ERROR);
            if (! is_array($data)) {
                throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
            }
            $rubric = json_decode($record->rubric_snapshot, true, flags: JSON_THROW_ON_ERROR);
            $result = AssessmentResult::fromArray($data, new RubricDefinition($rubric['criteria']), ['text'])->toArray();
            $result['criteria'] = WritingEvidence::validate($result['criteria'], $record->original);
            $result = AssessmentResult::fromArray($result, new RubricDefinition($rubric['criteria']), ['text'])->toArray();
            $result['issues'] = WritingIssues::validate($data['issues'] ?? null, $record->original);
            foreach (['strengths', 'improvements'] as $field) {
                // Older execution snapshots did not request these fields.
                $points = $data[$field] ?? [];
                if (! is_array($points) || ! array_is_list($points) || count($points) > 5) {
                    throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
                }
                foreach ($points as $point) {
                    if (! is_string($point) || trim($point) === '' || strlen($point) > 2000) {
                        throw new AiException('AI_ASSESSMENT_RESULT_INVALID');
                    }
                }
                $result[$field] = array_map('trim', $points);
            }
            DB::transaction(function () use ($record, $rubric, $result, $response) {
                $current = $this->record($record->id, true);
                $this->access->owned($current, true);
                if ($current->status === 'completed') {
                    return;
                }
                foreach ($result['criteria'] as $criterion => $score) {
                    DB::table('tutor_ai_assessment_scores')->insert([
                        'id' => (string) Str::uuid(), 'writing_submission_id' => $record->id, 'criterion' => $criterion,
                        'status' => $score['status'], 'score' => $score['score'], 'evidence' => json_encode($score['evidence'], JSON_THROW_ON_ERROR), 'created_at' => now(),
                    ]);
                }
                foreach ($result['issues'] as $issue) {
                    if (! $issue['applicable']) {
                        continue;
                    }
                    unset($issue['applicable']);
                    DB::table('tutor_ai_writing_issues')->insert(['id' => (string) Str::uuid(), 'submission_id' => $record->id, ...$issue, 'created_at' => now()]);
                }
                DB::table('tutor_ai_writing_submissions')->where('id', $record->id)->update([
                    'status' => 'completed', 'result' => json_encode($result, JSON_THROW_ON_ERROR), 'error_code' => null,
                    'provider' => $response->provider, 'model' => $response->model, 'completed_at' => now(), 'updated_at' => now(),
                ]);
                event(new WritingAssessmentCompleted($record->id, $record->id, $record->actor_id,
                    $record->lesson_id ?? $record->draft_id, $result['criteria'], $rubric['id']));
            }, 3);
        } catch (Throwable $error) {
            $code = $error instanceof AiException ? $error->errorCode : ($error instanceof \JsonException ? 'AI_ASSESSMENT_RESULT_INVALID' : 'AI_WRITING_PROCESSING_FAILED');
            $billing = DB::table('tutor_ai_requests')->where('request_id', $record->request_id)->first();
            $uncertain = $billing && in_array($billing->status, ['pending', 'processing'], true);
            DB::table('tutor_ai_writing_submissions')->where('id', $record->id)->where('status', '!=', 'completed')->update([
                'status' => $uncertain ? 'reconciliation_required' : 'failed', 'error_code' => $code, 'updated_at' => now(),
                'provider' => $billing->provider ?? null, 'model' => $billing->model ?? null,
            ]);
            throw new AiException($code);
        }
    }

    public function record(string $id, bool $lock = false): object
    {
        $query = DB::table('tutor_ai_writing_submissions')->where('id', $id);
        $record = ($lock ? $query->lockForUpdate() : $query)->first();
        if (! $record) {
            throw new AiException('AI_WRITING_NOT_FOUND');
        }

        return $record;
    }

    private function recovery(object $record): string
    {
        if ($record->status === 'completed') {
            return 'completed';
        }
        $billing = DB::table('tutor_ai_requests')->where('request_id', $record->request_id)->first();
        if ($record->status === 'queued') {
            return 'same_request';
        }
        if ($billing && in_array($billing->status, ['pending', 'processing'], true)) {
            return 'reconciliation';
        }
        if ($record->status !== 'failed') {
            return $billing && $billing->status === 'completed' ? 'same_request' : 'reconciliation';
        }
        if (DB::table('tutor_ai_credit_transactions')->where('request_id', $record->request_id)->where('type', 'commit')->exists()) {
            return 'blocked';
        }
        $transactions = DB::table('tutor_ai_credit_transactions')->where('request_id', $record->request_id)->get();
        if ($transactions->where('type', 'reserve')->sum('units') !== $transactions->where('type', 'release')->sum('units')) {
            return 'reconciliation';
        }
        if ((! $billing || $billing->status === 'failed') && in_array($record->error_code, [
            'AI_PROVIDER_AUTH_FAILED', 'AI_PROVIDER_RATE_LIMITED', 'AI_PROVIDER_UNAVAILABLE', 'AI_PROVIDER_NOT_CONFIGURED',
            'AI_CREDIT_INSUFFICIENT', 'AI_CREDIT_RULE_INVALID', 'AI_DAILY_LIMIT_REACHED', 'AI_REQUEST_RECONCILED_RELEASED',
        ], true)) {
            return 'new_attempt';
        }

        return 'blocked';
    }

    private function identifiers(string $requestId, string $key): void
    {
        if (! Str::isUuid($requestId) || $key === '' || strlen($key) > 191) {
            throw new AiException('AI_REQUEST_INVALID');
        }
        if (DB::table('tutor_ai_writing_submissions')->where('request_id', $requestId)->where('idempotency_key', '!=', $key)->exists()) {
            throw new AiException('AI_REQUEST_DUPLICATE');
        }
    }

    private function prepared(object $record, ?string $requestId = null, ?string $key = null): AiRequest
    {
        return $this->request($record, $record->feature,
            json_decode(Crypt::decryptString($record->encrypted_payload), true, flags: JSON_THROW_ON_ERROR),
            $requestId ?? $record->request_id, $key ?? $record->idempotency_key);
    }

    private function request(object $record, string $feature, array $payload, string $requestId, string $key): AiRequest
    {
        return new AiRequest($feature, $this->access->actors->resolve(), $payload, $requestId, $key, $record->course_id, $record->lesson_id);
    }
}
