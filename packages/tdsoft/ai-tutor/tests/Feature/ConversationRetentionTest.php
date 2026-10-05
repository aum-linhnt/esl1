<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Conversations\ConversationRetention;
use TDSoft\AiTutor\Conversations\ConversationService;
use TDSoft\AiTutor\Core\LearnerIdentity;
use TDSoft\AiTutor\Http\ConversationController;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class ConversationRetentionTest extends FoundationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
    }

    private function turn(): array
    {
        $service = $this->app->make(ConversationService::class);
        $conversation = $service->create('lesson-1');

        return $service->send($conversation->id, 'Private learner question', (string) Str::uuid(), (string) Str::uuid());
    }

    public function test_delete_removes_content_and_replay_but_preserves_usage_and_ledger(): void
    {
        $turn = $this->turn();
        $usage = DB::table('tutor_ai_usage_records')->get()->toJson();
        $ledger = DB::table('tutor_ai_credit_transactions')->get()->toJson();
        $balance = DB::table('tutor_ai_credit_accounts')->value('balance');
        $this->assertNotNull(DB::table('tutor_ai_requests')->value('encrypted_result'));
        $this->app->make(ConversationController::class)->destroy($turn['conversation_id'], new ConversationRetention);
        $this->assertSame(0, DB::table('tutor_ai_conversation_messages')->count());
        $this->assertSame(0, DB::table('tutor_ai_conversations')->count());
        $this->assertNull(DB::table('tutor_ai_requests')->value('encrypted_result'));
        $this->assertSame($usage, DB::table('tutor_ai_usage_records')->get()->toJson());
        $this->assertSame($ledger, DB::table('tutor_ai_credit_transactions')->get()->toJson());
        $this->assertSame($balance, DB::table('tutor_ai_credit_accounts')->value('balance'));
    }

    public function test_export_omits_internal_prompt_and_other_actor_cannot_export_or_delete(): void
    {
        $turn = $this->turn();
        $response = $this->app->make(ConversationController::class)->export($turn['conversation_id']);
        ob_start();
        ($response->getCallback())();
        $json = ob_get_clean();
        $export = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Private learner question', $export['messages'][0]['user_content']);
        $this->assertStringNotContainsString('encrypted_payload', $json);
        $this->assertStringNotContainsString('instructions', $json);
        $this->app->instance(ActorResolver::class, new class implements ActorResolver
        {
            public function resolve(): LearnerIdentity
            {
                return new LearnerIdentity('other-user');
            }
        });
        $controller = $this->app->make(ConversationController::class);
        $this->assertError('AI_CONVERSATION_NOT_FOUND', fn () => $controller->export($turn['conversation_id']));
        $this->assertError('AI_CONVERSATION_NOT_FOUND', fn () => $controller->destroy($turn['conversation_id'], new ConversationRetention));
        $this->assertSame(1, DB::table('tutor_ai_conversations')->count());
    }

    public function test_retention_checks_recent_messages_dry_run_and_unsettled_requests(): void
    {
        $turn = $this->turn();
        $retention = new ConversationRetention;
        $cutoff = now()->subDays(365);
        DB::table('tutor_ai_conversations')->update(['updated_at' => now()->subDays(400)]);
        $this->assertFalse($retention->erase($turn['conversation_id'], $cutoff)); // recent message
        DB::table('tutor_ai_conversation_messages')->update(['updated_at' => now()->subDays(400)]);
        $this->assertTrue($retention->erase($turn['conversation_id'], $cutoff, true));
        $this->assertSame(1, DB::table('tutor_ai_conversations')->count());
        DB::table('tutor_ai_requests')->update(['status' => 'processing']);
        $this->assertError('AI_CONVERSATION_BUSY', fn () => $retention->erase($turn['conversation_id'], $cutoff));
        $this->assertSame(1, DB::table('tutor_ai_conversations')->count());
    }
}
