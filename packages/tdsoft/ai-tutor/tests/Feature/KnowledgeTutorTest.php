<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Contracts\ActorResolver;
use TDSoft\AiTutor\Contracts\Entitlements;
use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;
use TDSoft\AiTutor\Contracts\VectorStore;
use TDSoft\AiTutor\Conversations\ConversationService;
use TDSoft\AiTutor\Core\LearnerIdentity;
use TDSoft\AiTutor\Knowledge\KnowledgeService;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Knowledge\TextChunker;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class KnowledgeTutorTest extends FoundationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
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
    }

    private function document(array $overrides = []): object
    {
        return $this->app->make(KnowledgeService::class)->create(array_merge([
            'title' => 'Lesson guide', 'lesson_id' => 'lesson-1', 'visibility' => 'learners',
            'content' => "Grammar example.\n\nPractice using the present tense.",
        ], $overrides));
    }

    public function test_only_current_published_visible_non_answer_sources_are_retrieved(): void
    {
        $knowledge = $this->app->make(KnowledgeService::class);
        $vectors = $this->app->make(VectorStore::class);
        $context = $this->lms->getLessonContext('learner-1', 'lesson-1');
        $document = $this->document();
        $this->assertSame([], $vectors->search([1, 0, 0], 'mock-embedding', $context, 5));
        $knowledge->process($document->version_id);
        $this->assertSame([], $vectors->search([1, 0, 0], 'mock-embedding', $context, 5));
        $knowledge->publish($document->version_id);
        $this->assertCount(1, $vectors->search([1, 0, 0], 'mock-embedding', $context, 5));
        $calls = $this->provider->calls;
        $knowledge->process($document->version_id);
        $this->assertSame($calls, $this->provider->calls);
        foreach ([
            ['visibility' => 'private'], ['contains_answers' => true], ['lesson_id' => 'lesson-other'],
        ] as $options) {
            $hidden = $this->document($options);
            $knowledge->process($hidden->version_id);
            $knowledge->publish($hidden->version_id);
        }
        $this->assertCount(1, $vectors->search([1, 0, 0], 'mock-embedding', $context, 5));
        $new = $knowledge->version($document->id, 'Replacement version.', 'text');
        $knowledge->process($new->id);
        $knowledge->publish($new->id);
        $hits = $vectors->search([1, 0, 0], 'mock-embedding', $context, 5);
        $this->assertCount(1, $hits);
        $this->assertSame($new->id, DB::table('tutor_ai_knowledge_chunks')->where('id', $hits[0]['id'])->value('version_id'));
        $this->assertSame([], $vectors->search([1, 0, 0], 'other-model', $context, 5));
    }

    public function test_chat_rag_replay_bills_once_and_citations_recheck_visibility(): void
    {
        $knowledge = $this->app->make(KnowledgeService::class);
        $document = $this->document();
        $knowledge->process($document->version_id);
        $knowledge->publish($document->version_id);
        $tutor = $this->app->make(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $request = (string) Str::uuid();
        $result = $tutor->send($conversation->id, 'Explain this.', $request, 'chat-1');
        $calls = $this->provider->calls;
        $this->assertSame('completed', $result['status']);
        $this->assertCount(1, $result['sources']);
        $this->assertStringContainsString('Never provide a final answer', $this->provider->lastRequest->payload['instructions']);
        $this->assertStringContainsString('Grammar example', $this->provider->lastRequest->payload['input']);
        $this->assertEquals($result, $tutor->send($conversation->id, 'Explain this.', $request, 'chat-1'));
        $this->assertSame($calls, $this->provider->calls);
        $this->assertSame(3, DB::table('tutor_ai_usage_records')->count()); // index + query + chat
        $this->assertSame(3, DB::table('tutor_ai_credit_transactions')->where('type', 'commit')->count());
        $this->assertError('AI_REQUEST_DUPLICATE', fn () => $tutor->send($conversation->id, 'Different', $request, 'chat-1'));
        $chunk = $result['sources'][0]->chunk_id;
        $this->assertStringContainsString('Grammar', $tutor->source($result['id'], $chunk)->content);
        DB::table('tutor_ai_knowledge_documents')->where('id', $document->id)->update(['visibility' => 'private']);
        $this->assertSame([], $tutor->message($result['id'])['sources']);
        $this->assertError('AI_SOURCE_NOT_FOUND', fn () => $tutor->source($result['id'], $chunk));
        $this->lms->allowed = false;
        $this->assertError('AI_CONTEXT_FORBIDDEN', fn () => $tutor->message($result['id']));
    }

    public function test_exam_does_not_call_provider_and_missing_sources_are_explicit(): void
    {
        $tutor = $this->app->make(ConversationService::class);
        $exam = $tutor->create('lesson-1', 'exam');
        $blocked = $tutor->send($exam->id, 'Give answer', (string) Str::uuid(), 'exam');
        $this->assertSame('no_answer', $blocked['metadata']['answer_policy']);
        $this->assertSame(0, $this->provider->calls);
        $this->assertSame(0, DB::table('tutor_ai_usage_records')->count());
        $normal = $tutor->create('lesson-1');
        $reply = $tutor->send($normal->id, 'Hello', (string) Str::uuid(), 'normal');
        $this->assertTrue($reply['metadata']['missing_sources']);
    }

    public function test_owner_and_entitlements_are_enforced_at_service_boundary(): void
    {
        $tutor = $this->app->make(ConversationService::class);
        $conversation = $tutor->create('lesson-1');
        $this->app->instance(ActorResolver::class, new class implements ActorResolver
        {
            public function resolve(): LearnerIdentity
            {
                return new LearnerIdentity('other');
            }
        });
        $this->assertError('AI_CONVERSATION_NOT_FOUND', fn () => $this->app->make(ConversationService::class)->conversation($conversation->id));
        $this->app->instance(Entitlements::class, new class implements Entitlements
        {
            public function allows(string $module): bool
            {
                return false;
            }
        });
        $this->assertError('LICENSE_MODULE_NOT_ALLOWED', fn () => $this->app->make(ConversationService::class)->create('lesson-1'));
        $this->assertError('LICENSE_MODULE_NOT_ALLOWED', fn () => $this->document());
        $this->assertSame(0, $this->provider->calls);
    }

    public function test_knowledge_admin_denied_and_draft_cannot_publish(): void
    {
        $document = $this->document();
        $this->assertError('AI_DOCUMENT_NOT_READY', fn () => $this->app->make(KnowledgeService::class)->publish($document->version_id));
        $this->app->instance(KnowledgeAdministrator::class, new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return false;
            }
        });
        $this->assertError('AI_KNOWLEDGE_FORBIDDEN', fn () => $this->document());
    }

    public function test_version_history_preserves_content_and_withdraw_hides_sources(): void
    {
        $service = $this->app->make(KnowledgeService::class);
        $document = $this->document();
        $original = $service->getVersion($document->version_id)->content;
        $new = $service->version($document->id, 'New version content', 'text');
        $this->assertCount(2, $service->versions($document->id)['versions']);
        $this->assertSame($original, $service->getVersion($document->version_id)->content);
        $service->process($new->id);
        $service->publish($new->id);
        $service->withdraw($document->id);
        $context = $this->lms->getLessonContext('learner-1', 'lesson-1');
        $this->assertSame([], $this->app->make(VectorStore::class)->search([1, 0, 0], 'mock-embedding', $context, 5));
        $this->assertNull($service->document($document->id)->published_version_id);
        $this->assertCount(2, $service->versions($document->id)['versions']);
    }

    public function test_text_limits_html_and_unsupported_binary_format(): void
    {
        $chunker = new TextChunker;
        $chunks = $chunker->split('<p>Hello</p><script>alert("secret")</script><p>World</p>', 'html');
        $this->assertStringNotContainsString('secret', implode('', $chunks));
        $this->assertStringContainsString('World', implode('', $chunks));
        $this->assertCount(3, $chunker->split(str_repeat('a', 4000)));
        $this->assertError('AI_DOCUMENT_TYPE_NOT_SUPPORTED', fn () => $chunker->split('%PDF', 'pdf'));
        $this->assertError('AI_DOCUMENT_INVALID', fn () => $chunker->split("bad\0"));
    }

    public function test_upgrade_preserves_foundation_and_collision_stops_before_creating_more_tables(): void
    {
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame('conflict_or_partial', (new PhaseThreeSchema)->inspect()['state']);
        Schema::create('migrations', function ($t) {
            $t->id();
            $t->string('migration');
            $t->integer('batch');
        });
        DB::table('migrations')->insert(['migration' => PhaseThreeSchema::MIGRATION, 'batch' => 3]);
        $this->assertSame('installed', (new PhaseThreeSchema)->inspect()['state']);
        $this->expectException(\RuntimeException::class);
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
    }
}
