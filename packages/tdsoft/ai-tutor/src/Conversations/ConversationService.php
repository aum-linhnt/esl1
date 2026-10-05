<?php

namespace TDSoft\AiTutor\Conversations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\AttemptQuestionContextAdapter;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Contracts\VectorStore;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Core\QuestionContext;
use TDSoft\AiTutor\Knowledge\Access;
use TDSoft\AiTutor\Knowledge\KnowledgeVisibility;
use Throwable;

final class ConversationService
{
    public function __construct(private Access $access, private LmsContextAdapter $lms, private TeachingPolicy $policy,
        private AiExecutionService $ai, private VectorStore $vectors) {}

    public function questionContext(string $userId, string $questionId, string $lessonId, ?string $attemptId = null): QuestionContext
    {
        if ($attemptId !== null) {
            if (! $this->lms instanceof AttemptQuestionContextAdapter) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
            $question = $this->lms->getAttemptQuestionContext($userId, $questionId, $lessonId, $attemptId);
        } else {
            $question = $this->lms->getQuestionContext($userId, $questionId, $lessonId);
        }
        $lesson = $this->access->lesson($lessonId);
        if ($question->questionId !== $questionId || $question->lesson->lessonId !== $lessonId
            || $question->lesson->courseId !== $lesson->courseId) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }

        return $question;
    }

    public function create(string $lessonId, string $mode = 'hints_first', ?string $questionId = null, ?string $attemptId = null): object
    {
        $this->access->module('ai_tutor_core');
        $lesson = $this->access->lesson($lessonId);
        $this->policy->resolve($mode, $lesson->answerPolicy, $lesson->teacherAllowsSolution, $lesson->isExam);
        if ($attemptId !== null && $questionId === null) {
            throw new AiException('AI_CONTEXT_FORBIDDEN');
        }
        if ($questionId !== null) {
            $question = $this->questionContext($this->access->actors->resolve()->id, $questionId, $lessonId, $attemptId);
            if ($question->questionId !== $questionId || $question->lesson->courseId !== $lesson->courseId || $question->lesson->lessonId !== $lessonId) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
        }
        $id = (string) Str::uuid();
        DB::table('tutor_ai_conversations')->insert([
            'id' => $id, 'user_id' => $this->access->actors->resolve()->id, 'lesson_id' => $lessonId,
            'course_id' => $lesson->courseId, 'question_id' => $questionId, 'teaching_mode' => $mode,
            'created_at' => now(), 'updated_at' => now(),
        ] + ($attemptId !== null ? ['attempt_id' => $attemptId] : []));

        return $this->conversation($id);
    }

    public function conversation(string $id): object
    {
        $this->access->module('ai_tutor_core');
        $conversation = DB::table('tutor_ai_conversations')->where('id', $id)
            ->where('user_id', $this->access->actors->resolve()->id)->first();
        if (! $conversation) {
            throw new AiException('AI_CONVERSATION_NOT_FOUND');
        }
        $lesson = $this->access->lesson($conversation->lesson_id, $conversation->course_id);
        if ($conversation->question_id !== null) {
            $question = $this->questionContext($conversation->user_id, $conversation->question_id, $lesson->lessonId, $conversation->attempt_id ?? null);
            if ($question->questionId !== $conversation->question_id || $question->lesson->lessonId !== $lesson->lessonId
                || $question->lesson->courseId !== $lesson->courseId) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
        }

        return $conversation;
    }

    public function send(string $id, string $text, string $requestId, string $key, ?string $retryOf = null, bool $nextHint = false): array
    {
        $conversation = $this->conversation($id);
        if (trim($text) === '' || mb_strlen($text) > 4000 || ! mb_check_encoding($text, 'UTF-8')) {
            throw new AiException('AI_MESSAGE_INVALID');
        }
        $actor = $this->access->actors->resolve();
        // Validate caller identifiers before any insert/provider call.
        AiRequest::forFeature('tutor_message', $actor, [], $requestId, $key);
        $key = 'tutor:'.hash('sha256', $actor->id.':'.$key);
        $identity = $retryOf === null ? [$id, $text] : [$id, $text, $retryOf];
        if ($nextHint) {
            $identity[] = ['next_hint' => true];
        }
        $fingerprint = hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR));
        $message = DB::transaction(function () use ($id, $text, $requestId, $key, $fingerprint, $retryOf, $nextHint, $conversation) {
            DB::table('tutor_ai_conversations')->where('id', $id)->lockForUpdate()->first();
            $existing = DB::table('tutor_ai_conversation_messages')->where('idempotency_key', $key)->first();
            if ($existing) {
                if (! hash_equals($existing->fingerprint, $fingerprint) || $existing->request_id !== $requestId) {
                    throw new AiException('AI_REQUEST_DUPLICATE');
                }

                return $existing;
            }
            $previous = null;
            if ($retryOf !== null) {
                $previous = DB::table('tutor_ai_conversation_messages')->where('id', $retryOf)
                    ->where('conversation_id', $id)->first();
                if (! $previous || $previous->user_content !== $text || $previous->request_id === $requestId
                    || $this->recovery($previous, true) !== 'new_attempt') {
                    throw new AiException('AI_RETRY_NOT_ALLOWED');
                }
                $previousMetadata = $previous->metadata ? json_decode($previous->metadata, true, flags: JSON_THROW_ON_ERROR) : [];
                if (($previousMetadata['requested_next_hint'] ?? false) !== $nextHint) {
                    throw new AiException('AI_RETRY_NOT_ALLOWED');
                }
            }
            if (DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)
                ->when($retryOf !== null, fn ($q) => $q->where('id', '!=', $retryOf))
                ->where(fn ($q) => $q->whereIn('status', ['pending', 'processing'])->orWhere('error_code', 'AI_REQUEST_RECONCILIATION_REQUIRED'))->exists()) {
                throw new AiException('AI_CONVERSATION_BUSY');
            }
            $messageId = (string) Str::uuid();
            $metadata = $previous === null ? $this->hintState($conversation, $nextHint)
                : array_merge($previousMetadata, ['retry_of_message_id' => $retryOf]);
            $sequence = 0;
            foreach (DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)->select('metadata')->cursor() as $turn) {
                $sequence = max($sequence, $turn->metadata ? (json_decode($turn->metadata, true)['hint_sequence'] ?? 0) : 0);
            }
            $metadata['hint_sequence'] = $sequence + 1;
            DB::table('tutor_ai_conversation_messages')->insert([
                'id' => $messageId, 'conversation_id' => $id, 'idempotency_key' => $key, 'fingerprint' => $fingerprint,
                'request_id' => $requestId, 'embedding_request_id' => (string) Str::uuid(),
                // Reuse a prepared RAG payload; a completed query embedding is never charged again.
                'encrypted_payload' => $previous?->encrypted_payload,
                'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
                'user_content' => $text, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);

            return DB::table('tutor_ai_conversation_messages')->where('id', $messageId)->first();
        });
        if ($message->status === 'completed') {
            return $this->message($message->id);
        }
        // CAS prevents simultaneous execution. A completed AI request can finish local persistence on retry.
        if ($message->status === 'processing'
            && DB::table('tutor_ai_requests')->where('request_id', $message->request_id)->value('status') !== 'completed') {
            throw new AiException('AI_CONVERSATION_BUSY');
        }
        if ($message->status !== 'processing' && ! DB::table('tutor_ai_conversation_messages')->where('id', $message->id)
            ->where('status', $message->status)->update(['status' => 'processing', 'error_code' => null, 'updated_at' => now()])) {
            throw new AiException('AI_CONVERSATION_BUSY');
        }
        try {
            $lesson = $this->access->lesson($conversation->lesson_id, $conversation->course_id);
            $answerPolicy = $this->policy->resolve($conversation->teaching_mode, $lesson->answerPolicy, $lesson->teacherAllowsSolution, $lesson->isExam);
            if ($answerPolicy === 'no_answer') {
                DB::table('tutor_ai_conversation_messages')->where('id', $message->id)->update([
                    'status' => 'completed', 'content' => 'Bài này không cho phép Gia sư AI hỗ trợ đáp án. Hãy tự làm bài hoặc hỏi giáo viên.',
                    'metadata' => json_encode(array_merge($message->metadata ? json_decode($message->metadata, true) : [],
                        ['answer_policy' => $answerPolicy, 'hint_level' => 0, 'max_hint_level' => 0,
                            'credit_units' => 0, 'prompt_version' => TeachingPolicy::PROMPT_VERSION])),
                    'updated_at' => now(),
                ]);

                return $this->message($message->id);
            }
            $question = $conversation->question_id ? $this->questionContext($actor->id, $conversation->question_id, $lesson->lessonId, $conversation->attempt_id ?? null) : null;
            $contextStamp = hash('sha256', json_encode([$lesson, $question, $answerPolicy], JSON_THROW_ON_ERROR));
            if ($message->encrypted_payload) {
                $saved = json_decode(Crypt::decryptString($message->encrypted_payload), true, flags: JSON_THROW_ON_ERROR);
                // Pre-upgrade snapshots lacked the two optional policy flags. Accept
                // their exact old stamp only while both trusted flags remain false.
                $legacyLesson = get_object_vars($lesson);
                unset($legacyLesson['teacherAllowsSolution'], $legacyLesson['isExam']);
                $legacyQuestion = $question ? get_object_vars($question) : null;
                if ($legacyQuestion !== null) {
                    $legacyQuestion['lesson'] = get_object_vars($question->lesson);
                    unset($legacyQuestion['lesson']['teacherAllowsSolution'], $legacyQuestion['lesson']['isExam']);
                }
                $legacyStamp = hash('sha256', json_encode([$legacyLesson, $legacyQuestion, $answerPolicy], JSON_THROW_ON_ERROR));
                if ($saved['context_stamp'] !== $contextStamp
                    && ($lesson->teacherAllowsSolution || $lesson->isExam || $saved['context_stamp'] !== $legacyStamp)) {
                    throw new AiException('AI_CONTEXT_CHANGED');
                }
                foreach ($saved['sources'] as $source) {
                    if (! KnowledgeVisibility::query($lesson)->where('c.id', $source['id'])->exists()) {
                        throw new AiException('AI_CONTEXT_CHANGED');
                    }
                }
            } else {
                $hintState = $this->hintState($conversation, false);
                $hintLevel = $this->messageHintLevel($message, $hintState);
                $sources = [];
                if (KnowledgeVisibility::query($lesson)->exists()) {
                    $this->access->module('ai_tutor_knowledge');
                    $embedding = $this->ai->execute(AiRequest::forFeature(
                        'knowledge_embedding', $actor,
                        ['input' => $text, 'embedding_model' => config('ai-tutor.embedding_model')],
                        $message->embedding_request_id, 'tutor-search:'.$message->id, $lesson->courseId, $lesson->lessonId,
                    ));
                    foreach ($this->vectors->search($embedding->data['embedding'] ?? [], $embedding->model, $lesson,
                        (int) config('ai-tutor.knowledge.top_k', 5)) as $hit) {
                        $chunk = KnowledgeVisibility::query($lesson)->where('c.id', $hit['id'])
                            ->select('c.id', 'c.content', 'd.title')->first();
                        if ($chunk) {
                            $sources[] = ['id' => $chunk->id, 'label' => count($sources) + 1, 'title' => $chunk->title, 'text' => $chunk->content];
                        }
                    }
                }
                $question = $conversation->question_id ? $this->questionContext($actor->id, $conversation->question_id, $lesson->lessonId, $conversation->attempt_id ?? null) : null;
                $history = DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)
                    ->where('status', 'completed')->orderByDesc('created_at')->limit(8)->get(['user_content', 'content'])->reverse()->values()->all();
                $saved = [
                    'context_stamp' => $contextStamp, 'sources' => $sources,
                    'hint_level' => $hintLevel, 'policy_stamp' => $hintState['policy_stamp'], 'max_hint_level' => $hintState['max_hint_level'],
                    'prompt_version' => TeachingPolicy::PROMPT_VERSION,
                    'payload' => [
                        'instructions' => $this->policy->instructions($conversation->teaching_mode, $answerPolicy, $hintLevel),
                        'input' => json_encode(['lesson' => $lesson->content, 'question' => $question,
                            'history' => $history, 'sources' => $sources, 'learner_message' => $text], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    ],
                ];
                DB::table('tutor_ai_conversation_messages')->where('id', $message->id)->update([
                    'encrypted_payload' => Crypt::encryptString(json_encode($saved, JSON_THROW_ON_ERROR)), 'updated_at' => now(),
                ]);
            }
            $response = $this->ai->execute(AiRequest::forFeature(
                'tutor_message', $actor, $saved['payload'], $message->request_id, $key,
                $lesson->courseId, $lesson->lessonId, $conversation->question_id, $conversation->attempt_id ?? null,
            ));
            DB::transaction(function () use ($message, $response, $saved, $answerPolicy) {
                DB::table('tutor_ai_conversation_messages')->where('id', $message->id)->lockForUpdate()->first();
                foreach ($saved['sources'] as $source) {
                    DB::table('tutor_ai_message_sources')->insertOrIgnore([
                        'message_id' => $message->id, 'chunk_id' => $source['id'], 'citation' => $source['label'],
                    ]);
                }
                $units = DB::table('tutor_ai_requests')->whereIn('request_id', [$message->request_id, $message->embedding_request_id])->sum('actual_units');
                DB::table('tutor_ai_conversation_messages')->where('id', $message->id)->update([
                    'status' => 'completed', 'content' => $response->content, 'error_code' => null,
                    'metadata' => json_encode(array_merge($message->metadata ? json_decode($message->metadata, true) : [], [
                        'answer_policy' => $answerPolicy, 'prompt_version' => $saved['prompt_version'] ?? 'tutor-core-1',
                        'hint_level' => $saved['hint_level'] ?? 1,
                        'max_hint_level' => $saved['max_hint_level'] ?? $this->policy->maxHintLevel($answerPolicy),
                        'policy_stamp' => $saved['policy_stamp'] ?? null,
                        'provider' => $response->provider, 'model' => $response->model,
                        'missing_sources' => $saved['sources'] === [], 'credit_units' => (int) $units,
                    ]), JSON_THROW_ON_ERROR), 'updated_at' => now(),
                ]);
            });

            return $this->message($message->id);
        } catch (Throwable $error) {
            $code = $error instanceof AiException ? $error->errorCode : 'AI_TUTOR_FAILED';
            DB::table('tutor_ai_conversation_messages')->where('id', $message->id)->update([
                'status' => 'failed', 'error_code' => $code, 'updated_at' => now(),
            ]);
            throw new AiException($code);
        }
    }

    public function message(string $id): array
    {
        $message = DB::table('tutor_ai_conversation_messages')->where('id', $id)->first();
        if (! $message) {
            throw new AiException('AI_MESSAGE_NOT_FOUND');
        }
        $conversation = $this->conversation($message->conversation_id);
        $context = $this->access->lesson($conversation->lesson_id, $conversation->course_id);
        $sources = KnowledgeVisibility::query($context)->join('tutor_ai_message_sources as s', 's.chunk_id', '=', 'c.id')
            ->where('s.message_id', $id)->orderBy('s.citation')->get(['c.id as chunk_id', 'd.title', 's.citation'])->all();

        return [
            'id' => $message->id, 'conversation_id' => $message->conversation_id, 'request_id' => $message->request_id,
            'user_content' => $message->user_content, 'content' => $message->content, 'status' => $message->status,
            'error_code' => $message->error_code, 'metadata' => $message->metadata ? json_decode($message->metadata, true) : [],
            'created_at' => $message->created_at ? Carbon::parse($message->created_at, config('app.timezone', 'UTC'))->toIso8601String() : null,
            'completed_at' => $message->status === 'completed' && $message->updated_at
                ? Carbon::parse($message->updated_at, config('app.timezone', 'UTC'))->toIso8601String() : null,
            'sources' => $sources,
            'recovery' => $this->recovery($message),
            'credit_balance' => DB::table('tutor_ai_credit_accounts')->where([
                'owner_type' => 'learner', 'owner_id' => $conversation->user_id, 'scope' => 'system',
            ])->value('balance'),
        ];
    }

    private function hintState(object $conversation, bool $nextHint): array
    {
        $lesson = $this->access->lesson($conversation->lesson_id, $conversation->course_id);
        $policy = $this->policy->resolve($conversation->teaching_mode, $lesson->answerPolicy, $lesson->teacherAllowsSolution, $lesson->isExam);
        $stamp = hash('sha256', json_encode([$lesson->answerPolicy, $lesson->teacherAllowsSolution, $lesson->isExam, $policy], JSON_THROW_ON_ERROR));
        $latest = [];
        foreach (DB::table('tutor_ai_conversation_messages')->where('conversation_id', $conversation->id)
            ->where('status', 'completed')->select('metadata')->cursor() as $turn) {
            $metadata = $turn->metadata ? json_decode($turn->metadata, true) : [];
            if (($metadata['hint_sequence'] ?? 0) > ($latest['hint_sequence'] ?? 0)) {
                $latest = $metadata;
            }
        }
        $priorLevel = ($latest['policy_stamp'] ?? null) === $stamp ? ($latest['hint_level'] ?? 0) : 0;
        $max = $this->policy->maxHintLevel($policy);
        $level = $policy === 'full_solution' ? 4 : min($max, max(1, $priorLevel + ($nextHint ? 1 : 0)));

        return ['hint_level' => $level, 'max_hint_level' => $max, 'policy_stamp' => $stamp, 'requested_next_hint' => $nextHint];
    }

    private function messageHintLevel(object $message, array $current): int
    {
        $metadata = $message->metadata ? json_decode($message->metadata, true, flags: JSON_THROW_ON_ERROR) : [];

        // Changed server policy resets progression. Retries never advance a hint twice.
        return ($metadata['policy_stamp'] ?? null) === $current['policy_stamp']
            ? min($current['max_hint_level'], $metadata['hint_level'] ?? 1) : min($current['max_hint_level'], 1);
    }

    private function recovery(object $message, bool $lock = false): string
    {
        if ($message->status === 'completed') {
            return 'completed';
        }
        $query = DB::table('tutor_ai_requests')->whereIn('request_id', [$message->request_id, $message->embedding_request_id]);
        $records = ($lock ? $query->lockForUpdate() : $query)->get();
        if ($records->contains(fn ($r) => $r->request_id === $message->request_id && $r->status === 'completed')) {
            // Inference/settlement succeeded but local turn persistence may have failed.
            return 'same_request';
        }
        foreach ($records as $record) {
            if ($record->error_code === 'AI_REQUEST_RECONCILIATION_REQUIRED') {
                return 'reconciliation';
            }
            if (in_array($record->status, ['authorized', 'processing'], true)) {
                return $message->status === 'failed' ? 'reconciliation' : 'same_request';
            }
            if ($record->status === 'pending') {
                return 'same_request';
            }
            if ($record->status === 'failed') {
                $transactions = DB::table('tutor_ai_credit_transactions')->where('request_id', $record->request_id)->get();
                if ($transactions->contains('type', 'commit')) {
                    return 'blocked';
                }
                if ($transactions->where('type', 'reserve')->sum('units') !== $transactions->where('type', 'release')->sum('units')) {
                    return 'reconciliation';
                }
            }
        }
        if ($message->status !== 'failed') {
            return 'same_request';
        }
        $safe = ['AI_PROVIDER_AUTH_FAILED', 'AI_PROVIDER_RATE_LIMITED', 'AI_PROVIDER_UNAVAILABLE',
            'AI_PROVIDER_NOT_CONFIGURED', 'AI_CREDIT_INSUFFICIENT', 'AI_DAILY_LIMIT_REACHED',
            'AI_CREDIT_RULE_INVALID', 'AI_REQUEST_RECONCILED_RELEASED'];
        $errorCode = $message->error_code;
        // An embedding reconciliation also resolves the turn, even though its request ID
        // differs from the chat ID used by the administration UI.
        if ($errorCode === 'AI_REQUEST_RECONCILIATION_REQUIRED'
            && $records->contains('error_code', 'AI_REQUEST_RECONCILED_RELEASED')) {
            $errorCode = 'AI_REQUEST_RECONCILED_RELEASED';
        }
        if (! in_array($errorCode, $safe, true)
            || $records->contains(fn ($r) => $r->status !== 'completed' && ($r->status !== 'failed' || ! in_array($r->error_code, $safe, true)))) {
            return 'blocked';
        }
        if (DB::table('tutor_ai_conversation_messages')->where('conversation_id', $message->conversation_id)
            ->where('metadata->retry_of_message_id', $message->id)->exists()) {
            return 'blocked';
        }

        return 'new_attempt';
    }

    public function source(string $messageId, string $chunkId): object
    {
        $this->access->module('ai_tutor_knowledge');
        $message = $this->message($messageId);
        $conversation = $this->conversation($message['conversation_id']);
        $context = $this->access->lesson($conversation->lesson_id, $conversation->course_id);
        $source = KnowledgeVisibility::query($context)->join('tutor_ai_message_sources as s', 's.chunk_id', '=', 'c.id')
            ->where('s.message_id', $messageId)->where('c.id', $chunkId)->select('c.content', 'd.title', 's.citation')->first();
        if (! $source) {
            throw new AiException('AI_SOURCE_NOT_FOUND');
        }

        return $source;
    }
}
