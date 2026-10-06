<?php

namespace TDSoft\AiTutor\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Assessment\PhaseFourSchema;
use TDSoft\AiTutor\Http\RequireWritingSchema;
use TDSoft\AiTutor\Licensing\Http\RequireModule;
use TDSoft\AiTutor\Tests\FoundationTestCase;
use TDSoft\AiTutor\Writing\WritingSchema;

final class WritingRoutesSchemaTest extends FoundationTestCase
{
    public function test_writing_routes_keep_session_csrf_owner_module_and_schema_guards(): void
    {
        $router = new Router($this->app['events'], $this->app);
        Route::swap($router);
        require __DIR__.'/../../routes/writing.php';
        $this->assertCount(10, $router->getRoutes());
        foreach ($router->getRoutes() as $route) {
            $this->assertTrue(str_starts_with($route->uri(), 'ai-tutor/api/v1/writing/') || str_starts_with($route->uri(), 'ai-tutor/writing'));
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains(RequireModule::class.':ai_tutor_writing', $route->gatherMiddleware());
            $this->assertContains(RequireWritingSchema::class, $route->gatherMiddleware());
        }
        $this->app->instance('env', 'production');
        $middleware = new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected $except = ['api/*'];

            protected $addHttpCookie = false;
        };
        $session = new Store('writing-csrf', new ArraySessionHandler(120));
        $session->put('_token', 'token');
        $request = Request::create('/ai-tutor/api/v1/writing/drafts', 'POST');
        $request->setLaravelSession($session);
        $this->expectException(TokenMismatchException::class);
        $middleware->handle($request, fn () => $this->fail('Missing CSRF allowed'));
    }

    public function test_schema_guard_fails_closed_until_both_forward_migrations_are_recorded(): void
    {
        $guard = new RequireWritingSchema;
        $this->assertError('AI_WRITING_SCHEMA_REQUIRED', fn () => $guard->handle(null, fn () => true));
        (require __DIR__.'/../../database/migrations/'.PhaseFourSchema::MIGRATION.'.php')->up();
        $this->assertSame('pending', (new WritingSchema)->inspect()['state']);
        (require __DIR__.'/../../database/migrations/'.WritingSchema::MIGRATION.'.php')->up();
        $this->assertSame('conflict_or_partial', (new WritingSchema)->inspect()['state']);
        Schema::create('migrations', function (Blueprint $t) {
            $t->id();
            $t->string('migration');
            $t->integer('batch');
        });
        foreach ([PhaseFourSchema::MIGRATION, WritingSchema::MIGRATION] as $migration) {
            DB::table('migrations')->insert(['migration' => $migration, 'batch' => 1]);
        }
        $this->assertTrue($guard->handle(null, fn () => true));
        Schema::table('tutor_ai_writing_submissions', fn (Blueprint $t) => $t->dropUnique('tai_ws_retry_uq'));
        $this->assertSame('schema_mismatch', (new WritingSchema)->inspect()['state']);
        $this->assertError('AI_WRITING_SCHEMA_REQUIRED', fn () => $guard->handle(null, fn () => true));
    }
}
