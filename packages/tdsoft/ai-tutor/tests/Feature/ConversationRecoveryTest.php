<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Billing\CreditAdministration;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;
use TDSoft\AiTutor\Conversations\ConversationService;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Knowledge\KnowledgeService;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class ConversationRecoveryTest extends FoundationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
    }

    private function failTurn(string $code = 'AI_PROVIDER_AUTH_FAILED'): array
    {
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $requestId = (string) Str::uuid();
        $this->provider->failure = new AiException($code);
        $expected = $code === 'AI_PROVIDER_OUTCOME_UNKNOWN' ? 'AI_REQUEST_RECONCILIATION_REQUIRED' : $code;
        $this->assertError($expected, fn () => $tutor->send($conversation->id, 'Explain this.', $requestId, 'original'));
        $messageId = DB::table('tutor_ai_conversation_messages')->where('request_id', $requestId)->value('id');

        return [$conversation->id, $requestId, $messageId];
    }

    private function administrator(): CreditAdministration
    {
        (require __DIR__.'/../../database/migrations/'.CreditAdminSchema::MIGRATION.'.php')->up();
        $this->app->instance(CreditAdministrator::class, new class implements CreditAdministrator
        {
            public function actorId(): ?string
            {
                return 'admin-1';
            }

            public function recipients(string $search): array
            {
                return [];
            }

            public function recipient(string $id): array
            {
                return ['id' => $id, 'name' => 'Learner'];
            }

            public function administratorName(string $id): string
            {
                return 'Admin';
            }
        });

        return app(CreditAdministration::class);
    }

    public function test_reconciliation_release_enables_retry_but_charged_failure_stays_blocked(): void
    {
        [$conversation, $request, $original] = $this->failTurn('AI_PROVIDER_OUTCOME_UNKNOWN');
        $admin = $this->administrator();
        $admin->reconcile($request, 'commit', 2, 'Provider confirmed charge', (string) Str::uuid());
        $tutor = app(ConversationService::class);
        $this->assertSame('blocked', $tutor->message($original)['recovery']);
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($conversation, 'Explain this.', (string) Str::uuid(), 'charged-retry', $original));
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_releasing_unknown_embedding_updates_turn_and_allows_retry_in_the_same_conversation(): void
    {
        $this->app->instance(KnowledgeAdministrator::class, new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return true;
            }
        });
        config(['ai-tutor.embedding_model' => 'mock-embedding']);
        DB::table('tutor_ai_credit_rules')->insert([
            'feature' => 'knowledge_embedding', 'base_units' => 1, 'max_units_per_request' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $knowledge = app(KnowledgeService::class);
        $document = $knowledge->create(['title' => 'Guide', 'lesson_id' => 'lesson-1', 'visibility' => 'learners', 'content' => 'Grammar guidance.']);
        $knowledge->process($document->version_id);
        $knowledge->publish($document->version_id);
        [$conversation, , $original] = $this->failTurn('AI_PROVIDER_OUTCOME_UNKNOWN');
        $embeddingRequest = DB::table('tutor_ai_conversation_messages')->where('id', $original)->value('embedding_request_id');
        $this->administrator()->reconcile($embeddingRequest, 'release', 0, 'Provider confirmed no usage', (string) Str::uuid());
        $tutor = app(ConversationService::class);
        $this->assertSame('new_attempt', $tutor->message($original)['recovery']);
        $this->provider->failure = null;
        $reply = $tutor->send($conversation, 'Explain this.', (string) Str::uuid(), 'released-retry', $original);
        $this->assertSame('completed', $reply['status']);
        $this->assertCount(1, $reply['sources']);
        $this->assertSame(96, DB::table('tutor_ai_credit_accounts')->value('balance'));
    }

    public function test_corrected_credentials_allow_explicit_new_attempt_in_same_conversation_and_replay_bills_once(): void
    {
        [$conversation, $originalRequest, $original] = $this->failTurn();
        $tutor = app(ConversationService::class);
        $this->assertSame('new_attempt', $tutor->message($original)['recovery']);
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->provider->failure = null;
        // A reconnect cannot silently turn an old failed ID into a new paid call.
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $tutor->send($conversation, 'Explain this.', $originalRequest, 'original'));
        $this->assertSame(1, $this->provider->calls);
        $request = (string) Str::uuid();
        $reply = $tutor->send($conversation, 'Explain this.', $request, 'retry', $original);
        $this->assertSame($conversation, $reply['conversation_id']);
        $this->assertSame($original, $reply['metadata']['retry_of_message_id']);
        $this->assertSame('completed', $reply['recovery']);
        $this->assertEquals($reply, $tutor->send($conversation, 'Explain this.', $request, 'retry', $original));
        $this->assertSame(2, $this->provider->calls);
        $this->assertSame(1, DB::table('tutor_ai_usage_records')->count());
        $this->assertSame(1, DB::table('tutor_ai_credit_transactions')->where('type', 'commit')->count());
        $this->assertSame(98, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame('failed', DB::table('tutor_ai_requests')->where('request_id', $originalRequest)->value('status'));
        // Another tab cannot create a second paid child of the same failed turn.
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($conversation, 'Explain this.', (string) Str::uuid(), 'another-retry', $original));
    }

    public function test_unknown_outcome_and_unreleased_hold_cannot_be_retried_with_new_identifiers(): void
    {
        [$conversation, $request, $original] = $this->failTurn('AI_PROVIDER_OUTCOME_UNKNOWN');
        $tutor = app(ConversationService::class);
        $this->assertSame('reconciliation', $tutor->message($original)['recovery']);
        $this->provider->failure = null;
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($conversation, 'Explain this.', (string) Str::uuid(), 'retry', $original));
        $this->assertError('AI_REQUEST_RECONCILIATION_REQUIRED', fn () => $tutor->send($conversation, 'Explain this.', $request, 'original'));
        // A failed flag by itself is insufficient evidence that money was released.
        DB::table('tutor_ai_requests')->where('request_id', $request)->update(['status' => 'failed', 'error_code' => 'AI_PROVIDER_AUTH_FAILED']);
        DB::table('tutor_ai_conversation_messages')->where('id', $original)->update(['error_code' => 'AI_PROVIDER_AUTH_FAILED']);
        $this->assertSame('reconciliation', $tutor->message($original)['recovery']);
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($conversation, 'Explain this.', (string) Str::uuid(), 'retry-2', $original));
        $this->assertSame(1, $this->provider->calls);
        $this->assertSame(95, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(1, DB::table('tutor_ai_conversation_messages')->count());
    }

    public function test_lost_completed_response_is_replayed_without_new_inference_or_credit(): void
    {
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $request = (string) Str::uuid();
        $reply = $tutor->send($conversation->id, 'Hello', $request, 'lost-connection');
        $balance = DB::table('tutor_ai_credit_accounts')->value('balance');
        $this->assertEquals($reply, $tutor->send($conversation->id, 'Hello', $request, 'lost-connection'));
        $this->assertSame($balance, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(1, $this->provider->calls);
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($conversation->id, 'Hello', (string) Str::uuid(), 'wrong-retry', $reply['id']));
    }

    public function test_completed_ai_request_recovers_after_local_message_persistence_failure(): void
    {
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $request = (string) Str::uuid();
        DB::unprepared("CREATE TRIGGER fail_turn BEFORE UPDATE ON tutor_ai_conversation_messages WHEN NEW.status = 'completed' BEGIN SELECT RAISE(ABORT, 'fixture'); END");
        $this->assertError('AI_TUTOR_FAILED', fn () => $tutor->send($conversation->id, 'Hello', $request, 'local-failure'));
        DB::unprepared('DROP TRIGGER fail_turn');
        $message = DB::table('tutor_ai_conversation_messages')->where('request_id', $request)->value('id');
        $this->assertSame('same_request', $tutor->message($message)['recovery']);
        $reply = $tutor->send($conversation->id, 'Hello', $request, 'local-failure');
        $this->assertSame('completed', $reply['status']);
        $this->assertSame(1, $this->provider->calls);
        $this->assertSame(1, DB::table('tutor_ai_usage_records')->count());
        $this->assertSame(98, DB::table('tutor_ai_credit_accounts')->value('balance'));
    }

    public function test_retry_cannot_change_content_use_another_conversation_or_bypass_revoked_access(): void
    {
        [$conversation, , $original] = $this->failTurn();
        $tutor = app(ConversationService::class);
        $other = $tutor->create('lesson-1');
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($other->id, 'Explain this.', (string) Str::uuid(), 'cross-conversation', $original));
        $this->assertError('AI_RETRY_NOT_ALLOWED', fn () => $tutor->send($conversation, 'Changed', (string) Str::uuid(), 'changed', $original));
        $this->lms->allowed = false;
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $tutor->send($conversation, 'Explain this.', (string) Str::uuid(), 'revoked', $original));
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_retry_reuses_paid_rag_query_and_preserves_sources(): void
    {
        $this->app->instance(KnowledgeAdministrator::class, new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return true;
            }
        });
        config(['ai-tutor.embedding_model' => 'mock-embedding']);
        DB::table('tutor_ai_credit_rules')->insert([
            'feature' => 'knowledge_embedding', 'base_units' => 1, 'max_units_per_request' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $knowledge = app(KnowledgeService::class);
        $document = $knowledge->create(['title' => 'Guide', 'lesson_id' => 'lesson-1', 'visibility' => 'learners', 'content' => 'Grammar guidance.']);
        $knowledge->process($document->version_id);
        $knowledge->publish($document->version_id);
        $tutor = app(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $request = (string) Str::uuid();
        // Complete retrieval, then fail chat without undoing the paid query embedding.
        DB::unprepared("CREATE TRIGGER fail_chat BEFORE INSERT ON tutor_ai_requests WHEN NEW.feature = 'tutor_message' BEGIN SELECT RAISE(ABORT, 'fixture'); END");
        $this->assertError('AI_TUTOR_FAILED', fn () => $tutor->send($conversation->id, 'Explain', $request, 'rag'));
        DB::unprepared('DROP TRIGGER fail_chat');
        $original = DB::table('tutor_ai_conversation_messages')->where('request_id', $request)->value('id');
        // Retry the same turn to exercise a known provider failure after retrieval.
        $this->provider->failure = new AiException('AI_PROVIDER_AUTH_FAILED');
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $tutor->send($conversation->id, 'Explain', $request, 'rag'));
        $this->provider->failure = null;
        $reply = $tutor->send($conversation->id, 'Explain', (string) Str::uuid(), 'rag-retry', $original);
        $this->assertCount(1, $reply['sources']);
        $this->assertSame(2, $reply['metadata']['credit_units']);
        $this->assertSame(2, DB::table('tutor_ai_usage_records')->where('feature', 'knowledge_embedding')->count());
        $this->assertSame(1, DB::table('tutor_ai_usage_records')->where('feature', 'tutor_message')->count());
        $this->assertSame(96, DB::table('tutor_ai_credit_accounts')->value('balance'));
    }
}
