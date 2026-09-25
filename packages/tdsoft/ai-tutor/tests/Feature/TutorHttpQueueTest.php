<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TDSoft\AiTutor\Contracts\BackgroundActor;
use TDSoft\AiTutor\Contracts\KnowledgeAdministrator;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Http\RequireKnowledgeAdministrator;
use TDSoft\AiTutor\Knowledge\KnowledgeService;
use TDSoft\AiTutor\Knowledge\PhaseThreeSchema;
use TDSoft\AiTutor\Knowledge\ProcessKnowledgeVersion;
use TDSoft\AiTutor\Licensing\Http\RequireModule;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class TutorHttpQueueTest extends FoundationTestCase
{
    public function test_routes_keep_auth_csrf_module_guard_and_stream_get_is_read_only(): void
    {
        $router = new Router($this->app['events'], $this->app);
        Route::swap($router);
        require __DIR__.'/../../routes/tutor.php';
        $this->assertCount(24, $router->getRoutes());
        foreach ($router->getRoutes() as $route) {
            $this->assertFalse(str_starts_with($route->uri(), 'api/'));
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertNotEmpty(array_filter($route->gatherMiddleware(), fn ($m) => str_starts_with($m, RequireModule::class.':')));
            if (str_contains($route->uri(), 'knowledge')) {
                $this->assertContains(RequireKnowledgeAdministrator::class, $route->gatherMiddleware());
            }
            if (str_ends_with($route->uri(), '/stream')) {
                $this->assertSame(['GET', 'HEAD'], $route->methods());
            }
        }
        $this->app->instance('env', 'production');
        $middleware = new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected $except = ['api/*'];

            protected $addHttpCookie = false;
        };
        $session = new Store('tutor-csrf', new ArraySessionHandler(120));
        $session->put('_token', 'token');
        $request = Request::create('/ai-tutor/api/v1/conversations', 'POST');
        $request->setLaravelSession($session);
        $this->expectException(TokenMismatchException::class);
        $middleware->handle($request, fn () => $this->fail('Missing CSRF allowed'));
    }

    public function test_knowledge_admin_middleware_returns_403_for_non_admin(): void
    {
        $guard = new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return false;
            }
        };
        try {
            (new RequireKnowledgeAdministrator($guard))->handle(
                Request::create('/admin/ai/knowledge'), fn () => $this->fail('Student reached Knowledge admin')
            );
            $this->fail('Expected 403');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
    }

    public function test_queue_rechecks_actor_and_replay_does_not_reembed_or_serialize_secrets(): void
    {
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
        $this->app->instance(KnowledgeAdministrator::class, new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return true;
            }
        });
        config(['ai-tutor.embedding_model' => 'mock-embedding', 'ai-tutor.credentials.openai' => 'must-not-serialize']);
        DB::table('tutor_ai_credit_rules')->insert([
            'feature' => 'knowledge_embedding', 'base_units' => 1, 'max_units_per_request' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $service = $this->app->make(KnowledgeService::class);
        $document = $service->create(['title' => 'Title', 'lesson_id' => 'lesson-1', 'content' => 'Private text']);
        $job = new ProcessKnowledgeVersion($document->version_id);
        $serialized = serialize($job);
        $this->assertStringNotContainsString('must-not-serialize', $serialized);
        $this->assertStringNotContainsString('Private text', $serialized);
        $actors = new class implements BackgroundActor
        {
            public array $seen = [];

            public bool $allowed = true;

            public function run(string $actorId, \Closure $work): mixed
            {
                $this->seen[] = $actorId;
                if (! $this->allowed) {
                    throw new AiException('AI_ACTOR_INVALID');
                }

                return $work();
            }
        };
        $job->handle($actors, $service);
        $job->handle($actors, $service);
        $this->assertSame(['learner-1', 'learner-1'], $actors->seen);
        $this->assertSame(1, $this->provider->calls);
        $this->assertSame('completed', DB::table('tutor_ai_knowledge_processing_jobs')->value('status'));
        $actors->allowed = false;
        $this->assertError('AI_ACTOR_INVALID', fn () => $job->handle($actors, $service));
        $this->assertSame(1, $this->provider->calls);
    }

    public function test_embedding_safe_failure_releases_credit_and_can_retry_after_configuration_is_fixed(): void
    {
        (require __DIR__.'/../../database/migrations/'.PhaseThreeSchema::MIGRATION.'.php')->up();
        $this->app->instance(KnowledgeAdministrator::class, new class implements KnowledgeAdministrator
        {
            public function allows(): bool
            {
                return true;
            }
        });
        DB::table('tutor_ai_credit_rules')->insert([
            'feature' => 'knowledge_embedding', 'base_units' => 1, 'max_units_per_request' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $service = $this->app->make(KnowledgeService::class);
        $document = $service->create(['title' => 'Title', 'lesson_id' => 'lesson-1', 'content' => 'Text']);
        $this->provider->failure = new AiException('AI_PROVIDER_AUTH_FAILED');
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $service->process($document->version_id));
        $this->assertError('AI_PROVIDER_AUTH_FAILED', fn () => $service->process($document->version_id));
        $this->assertSame(2, $this->provider->calls);
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame(2, DB::table('tutor_ai_credit_transactions')->where('type', 'release')->count());
        // Compatibility: an older worker overwrote the specific safe error on final failure.
        DB::table('tutor_ai_knowledge_processing_jobs')->update(['error_code' => 'AI_KNOWLEDGE_PROCESSING_FAILED']);
        $this->provider->failure = null;
        $service->process($document->version_id);
        $this->assertSame(3, $this->provider->calls);
        $this->assertSame('ready', DB::table('tutor_ai_knowledge_document_versions')->value('status'));
        $this->assertSame(99, DB::table('tutor_ai_credit_accounts')->value('balance'));
    }
}
