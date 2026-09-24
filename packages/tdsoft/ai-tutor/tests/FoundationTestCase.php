<?php

namespace TDSoft\AiTutor\Tests;

use Illuminate\Config\Repository;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Encryption\Encrypter;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\{DB, Facade};
use PHPUnit\Framework\TestCase;
use TDSoft\AiTutor\AiTutorServiceProvider;
use TDSoft\AiTutor\Contracts\{ActorResolver, Entitlements, LmsContextAdapter};
use TDSoft\AiTutor\Core\{AiException, AiRequest, LearnerIdentity};
use TDSoft\AiTutor\Providers\MockProvider;
use TDSoft\AiTutor\Tests\Fakes\FakeLmsAdapter;

abstract class FoundationTestCase extends TestCase
{
    protected Application $app;
    protected MockProvider $provider;
    protected FakeLmsAdapter $lms;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('APP_ENV') !== 'testing' || getenv('DB_CONNECTION') !== 'sqlite' || getenv('DB_DATABASE') !== ':memory:') {
            throw new \RuntimeException('Refusing tests outside SQLite :memory:');
        }
        // No host bootstrap, .env, cached config, seeders or existing migrations.
        $this->app = new Application(dirname(__DIR__, 4));
        $this->app->instance('env', 'testing');
        $this->app->instance('config', new Repository(['app' => ['cipher' => 'AES-256-CBC']]));
        $this->app->instance('events', new Dispatcher($this->app));
        $capsule = new Capsule($this->app);
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]);
        $capsule->setEventDispatcher($this->app['events']);
        $capsule->bootEloquent();
        $this->app->instance('db', $capsule->getDatabaseManager());
        $this->app->bind('db.schema', fn () => $capsule->getConnection()->getSchemaBuilder());
        $this->app->instance('encrypter', new Encrypter(random_bytes(32), 'AES-256-CBC'));
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
        $this->app->register(AiTutorServiceProvider::class);
        config(['ai-tutor.enabled' => true, 'ai-tutor.provider' => 'mock', 'ai-tutor.providers.mock' => MockProvider::class]);
        $this->provider = new MockProvider;
        $this->app->instance(MockProvider::class, $this->provider);
        $this->app->instance(ActorResolver::class, new class implements ActorResolver {
            public function resolve(): LearnerIdentity { return new LearnerIdentity('learner-1'); }
        });
        $this->app->instance(Entitlements::class, new class implements Entitlements {
            public function allows(string $module): bool { return true; }
        });
        $this->lms = new FakeLmsAdapter;
        $this->app->instance(LmsContextAdapter::class, $this->lms);
        (require __DIR__.'/../database/migrations/2026_09_24_000001_create_tutor_ai_foundation.php')->up();
        DB::table('tutor_ai_credit_accounts')->insert([
            'owner_type' => 'learner', 'owner_id' => 'learner-1', 'scope' => 'system', 'balance' => 100, 'daily_limit' => 50,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('tutor_ai_credit_rules')->insert([
            'feature' => 'tutor_message', 'base_units' => 1, 'max_units_per_request' => 5,
            'blocks' => json_encode(['input_tokens' => ['size' => 2000, 'units' => 1]]),
            'cost_rates' => json_encode(['input_tokens' => 1000000, 'output_tokens' => 2000000]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    protected function request(string $key = 'test-key', array $payload = ['message' => 'Hello']): AiRequest
    {
        return new AiRequest('tutor_message', new LearnerIdentity('learner-1'), $payload,
            (string) \Illuminate\Support\Str::uuid(), $key, 'course-1', 'lesson-1');
    }

    protected function assertError(string $code, callable $action): void
    {
        try { $action(); $this->fail('Expected '.$code); }
        catch (AiException $error) { $this->assertSame($code, $error->errorCode); }
    }

    protected function tearDown(): void
    {
        DB::disconnect();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        $this->app->flush();
        parent::tearDown();
    }
}
