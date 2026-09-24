<?php

namespace TDSoft\AiTutor\Conversations;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\LmsContextAdapter;
use TDSoft\AiTutor\Contracts\VectorStore;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Core\AiRequest;
use TDSoft\AiTutor\Knowledge\Access;
use TDSoft\AiTutor\Knowledge\KnowledgeVisibility;
use Throwable;

final class ConversationService
{
    public function __construct(private Access $access, private LmsContextAdapter $lms, private TeachingPolicy $policy,
        private AiExecutionService $ai, private VectorStore $vectors) {}

    public function create(string $lessonId, string $mode = 'hints_first', ?string $questionId = null): object
    {
        $this->access->module('ai_tutor_core');
        $lesson = $this->access->lesson($lessonId);
        $this->policy->resolve($mode, $lesson->answerPolicy);
        if ($questionId !== null) {
            $question = $this->lms->getQuestionContext($this->access->actors->resolve()->id, $questionId, $lessonId);
            if ($question->questionId !== $questionId || $question->lesson->courseId !== $lesson->courseId || $question->lesson->lessonId !== $lessonId) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
        }
        $id = (string) Str::uuid();
        DB::table('tutor_ai_conversations')->insert([
            'id' => $id, 'user_id' => $this->access->actors->resolve()->id, 'lesson_id' => $lessonId,
            'course_id' => $lesson->courseId, 'question_id' => $questionId, 'teaching_mode' => $mode,
            'created_at' => now(), 'updated_at' => now(),
        ]);

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
            $question = $this->lms->getQuestionContext($conversation->user_id, $conversation->question_id, $lesson->lessonId);
            if ($question->questionId !== $conversation->question_id || $question->lesson->lessonId !== $lesson->lessonId
                || $question->lesson->courseId !== $lesson->courseId) {
                throw new AiException('AI_CONTEXT_FORBIDDEN');
            }
        }

        return $conversation;
    }

    public function send(string $id, string $text, string $requestId, string $key): array
    {
        $conversation = $this->conversation($id);
        if (trim($text) === '' || mb_strlen($text) > 4000 || ! mb_check_encoding($text, 'UTF-8')) {
            throw new AiException('AI_MESSAGE_INVALID');
        }
        $actor = $this->access->actors->resolve();
        // Validate caller identifiers before any insert/provider call.
        AiRequest::forFeature('tutor_message', $actor, [], $requestId, $key);
        $key = 'tutor:'.hash('sha256', $actor->id.':'.$key);
        $fingerprint = hash('sha256', json_encode([$id, $text], JSON_THROW_ON_ERROR));
        $message = DB::transaction(function () use ($id, $text, $requestId, $key, $fingerprint) {
            DB::table('tutor_ai_conversations')->where('id', $id)->lockForUpdate()->first();
            $existing = DB::table('tutor_ai_conversation_messages')->where('idempotency_key', $key)->first();
            if ($existing) {
                if (! hash_equals($existing->fingerprint, $fingerprint) || $existing->request_id !== $requestId) {
                    throw new AiException('AI_REQUEST_DUPLICATE');
                }

                return $existing;
            }
            if (DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)
                ->where(fn ($q) => $q->whereIn('status', ['pending', 'processing'])->orWhere('error_code', 'AI_REQUEST_RECONCILIATION_REQUIRED'))->exists()) {
                throw new AiException('AI_CONVERSATION_BUSY');
            }
            $messageId = (string) Str::uuid();
            DB::table('tutor_ai_conversation_messages')->insert([
                'id' => $messageId, 'conversation_id' => $id, 'idempotency_key' => $key, 'fingerprint' => $fingerprint,
                'request_id' => $requestId, 'embedding_request_id' => (string) Str::uuid(),
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
            $answerPolicy = $this->policy->resolve($conversation->teaching_mode, $lesson->answerPolicy);
            if ($answerPolicy === 'no_answer') {
                DB::table('tutor_ai_conversation_messages')->where('id', $message->id)->update([
                    'status' => 'completed', 'content' => 'Bài này không cho phép Gia sư AI hỗ trợ đáp án. Hãy tự làm bài hoặc hỏi giáo viên.',
                    'metadata' => json_encode(['answer_policy' => $answerPolicy, 'credit_units' => 0, 'prompt_version' => TeachingPolicy::PROMPT_VERSION]),
                    'updated_at' => now(),
                ]);

                return $this->message($message->id);
            }
            $question = $conversation->question_id ? $this->lms->getQuestionContext($actor->id, $conversation->question_id, $lesson->lessonId) : null;
            $contextStamp = hash('sha256', json_encode([$lesson, $question, $answerPolicy], JSON_THROW_ON_ERROR));
            if ($message->encrypted_payload) {
                $saved = json_decode(Crypt::decryptString($message->encrypted_payload), true, flags: JSON_THROW_ON_ERROR);
                if ($saved['context_stamp'] !== $contextStamp) {
                    throw new AiException('AI_CONTEXT_CHANGED');
                }
                foreach ($saved['sources'] as $source) {
                    if (! KnowledgeVisibility::query($lesson)->where('c.id', $source['id'])->exists()) {
                        throw new AiException('AI_CONTEXT_CHANGED');
                    }
                }
            } else {
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
                $question = $conversation->question_id ? $this->lms->getQuestionContext($actor->id, $conversation->question_id, $lesson->lessonId) : null;
                $history = DB::table('tutor_ai_conversation_messages')->where('conversation_id', $id)
                    ->where('status', 'completed')->orderByDesc('created_at')->limit(8)->get(['user_content', 'content'])->reverse()->values()->all();
                $saved = [
                    'context_stamp' => $contextStamp, 'sources' => $sources,
                    'payload' => [
                        'instructions' => $this->policy->instructions($conversation->teaching_mode, $answerPolicy),
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
                $lesson->courseId, $lesson->lessonId, $conversation->question_id,
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
                    'metadata' => json_encode([
                        'answer_policy' => $answerPolicy, 'prompt_version' => TeachingPolicy::PROMPT_VERSION,
                        'provider' => $response->provider, 'model' => $response->model,
                        'missing_sources' => $saved['sources'] === [], 'credit_units' => (int) $units,
                    ], JSON_THROW_ON_ERROR), 'updated_at' => now(),
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
            'sources' => $sources,
            'credit_balance' => DB::table('tutor_ai_credit_accounts')->where([
                'owner_type' => 'learner', 'owner_id' => $conversation->user_id, 'scope' => 'system',
            ])->value('balance'),
        ];
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
