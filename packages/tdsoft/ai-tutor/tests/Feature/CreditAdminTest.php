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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use TDSoft\AiTutor\Billing\CreditAdministration;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Core\AiException;
use TDSoft\AiTutor\Core\AiExecutionService;
use TDSoft\AiTutor\Http\RequireCreditAdministrator;
use TDSoft\AiTutor\Tests\FoundationTestCase;

final class CreditAdminTest extends FoundationTestCase
{
    private object $admin;

    protected function setUp(): void
    {
        parent::setUp();
        (require __DIR__.'/../../database/migrations/'.CreditAdminSchema::MIGRATION.'.php')->up();
        $this->admin = new class implements CreditAdministrator
        {
            public bool $allowed = true;

            public function actorId(): ?string
            {
                return $this->allowed ? 'admin-1' : null;
            }

            public function recipients(string $search): array
            {
                return [];
            }

            public function recipient(string $id): array
            {
                if (! in_array($id, ['learner-1', 'learner-2'], true)) {
                    throw new AiException('AI_CREDIT_RECIPIENT_INVALID');
                }

                return ['id' => $id, 'name' => 'Learner'];
            }
        };
        $this->app->instance(CreditAdministrator::class, $this->admin);
    }

    public function test_grant_is_additive_idempotent_and_audited(): void
    {
        $service = $this->app->make(CreditAdministration::class);
        $id = (string) Str::uuid();
        $service->grant('learner-1', 20, 'Test allocation', $id);
        $service->grant('learner-1', 20, 'Test allocation', $id);
        $this->assertSame(120, DB::table('tutor_ai_credit_accounts')->where('owner_id', 'learner-1')->value('balance'));
        $this->assertSame(1, DB::table('tutor_ai_credit_transactions')->where('type', 'grant')->count());
        $audit = DB::table(CreditAdminSchema::TABLE)->first();
        $this->assertSame('admin-1', $audit->actor_id);
        $this->assertSame('Test allocation', json_decode($audit->details, true)['reason']);
        $this->assertError('AI_REQUEST_DUPLICATE', fn () => $service->grant('learner-1', 21, 'Test allocation', $id));
        $this->assertError('AI_REQUEST_DUPLICATE', fn () => $service->grant('learner-2', 20, 'Test allocation', $id));
        $this->assertFalse(DB::table('tutor_ai_credit_accounts')->where('owner_id', 'learner-2')->exists());
        $this->assertSame(1, DB::table(CreditAdminSchema::TABLE)->count());
    }

    public function test_new_account_has_default_quota_and_failed_operations_leave_no_audit_or_grants(): void
    {
        $service = $this->app->make(CreditAdministration::class);
        config(['ai-tutor.daily_credits' => 10]);
        $service->grant('learner-2', 100, 'Initial credit', (string) Str::uuid());
        $account = DB::table('tutor_ai_credit_accounts')->where('owner_id', 'learner-2')->first();
        $this->assertSame(100, $account->balance);
        $this->assertSame(10, $account->daily_limit);
        $this->assertError('AI_CREDIT_RECIPIENT_INVALID', fn () => $service->grant('missing', 10, 'Test', (string) Str::uuid()));
        $this->assertError('AI_CREDIT_GRANT_INVALID', fn () => $service->grant('learner-1', -1, 'Test', (string) Str::uuid()));
        DB::table('tutor_ai_credit_accounts')->where('owner_id', 'learner-1')->update(['status' => 'suspended']);
        $this->assertError('AI_RESERVATION_INVALID', fn () => $service->grant('learner-1', 10, 'Test', (string) Str::uuid()));
        $this->assertSame(1, DB::table(CreditAdminSchema::TABLE)->count());
    }

    public function test_rule_preserves_advanced_settings_rejects_stale_form_and_replays_once(): void
    {
        $service = $this->app->make(CreditAdministration::class);
        $old = DB::table('tutor_ai_credit_rules')->where('feature', 'tutor_message')->first();
        $key = (string) Str::uuid();
        $token = $service->ruleToken($old);
        $service->saveRule('tutor_message', 2, 8, false, $token, $key);
        $service->saveRule('tutor_message', 2, 8, false, $token, $key);
        $new = DB::table('tutor_ai_credit_rules')->where('feature', 'tutor_message')->first();
        $this->assertSame(2, $new->base_units);
        $this->assertSame(8, $new->max_units_per_request);
        $this->assertSame($old->blocks, $new->blocks);
        $this->assertSame($old->cost_rates, $new->cost_rates);
        $this->assertFalse((bool) $new->enabled);
        $this->assertSame(1, DB::table(CreditAdminSchema::TABLE)->count());
        $this->assertError('AI_CREDIT_RULE_CHANGED', fn () => $service->saveRule('tutor_message', 1, 1, true, $token, (string) Str::uuid()));
        $this->assertError('AI_CREDIT_RULE_INVALID', fn () => $service->saveRule('tutor_message', 9, 1, true, $service->ruleToken($new), (string) Str::uuid()));
        $service->saveRule('knowledge_embedding', 1, 1, true, $service->ruleToken(null), (string) Str::uuid());
        $this->assertSame(1, DB::table('tutor_ai_credit_rules')->where('feature', 'knowledge_embedding')->value('base_units'));
        $this->assertSame(2, DB::table(CreditAdminSchema::TABLE)->count());
        $this->assertSame(0, $this->provider->calls);
    }

    public function test_non_admin_is_denied_by_service_even_without_http_middleware(): void
    {
        $service = $this->app->make(CreditAdministration::class);
        $this->admin->allowed = false;
        $this->assertError('AI_CREDIT_ADMIN_FORBIDDEN', fn () => $service->grant('learner-1', 10, 'Test', (string) Str::uuid()));
        $this->assertError('AI_CREDIT_ADMIN_FORBIDDEN', fn () => $service->saveRule('knowledge_embedding', 1, 1, true, $service->ruleToken(null), (string) Str::uuid()));
        $this->assertSame(0, DB::table(CreditAdminSchema::TABLE)->count());
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
    }

    public function test_rule_edit_does_not_rewrite_completed_request_or_usage(): void
    {
        $this->app->make(AiExecutionService::class)->execute($this->request('rule-snapshot'));
        $before = DB::table('tutor_ai_requests')->get()->toJson();
        $usage = DB::table('tutor_ai_usage_records')->get()->toJson();
        $service = $this->app->make(CreditAdministration::class);
        $rule = DB::table('tutor_ai_credit_rules')->where('feature', 'tutor_message')->first();
        $service->saveRule('tutor_message', 4, 9, true, $service->ruleToken($rule), (string) Str::uuid());
        $this->assertSame($before, DB::table('tutor_ai_requests')->get()->toJson());
        $this->assertSame($usage, DB::table('tutor_ai_usage_records')->get()->toJson());
    }

    public function test_routes_are_authenticated_outside_csrf_exemption(): void
    {
        $router = new Router($this->app['events'], $this->app);
        Route::swap($router);
        require __DIR__.'/../../routes/credits.php';
        $this->assertCount(3, $router->getRoutes());
        foreach ($router->getRoutes() as $route) {
            $this->assertStringStartsWith('admin/ai/credits', $route->uri());
            $this->assertContains('web', $route->gatherMiddleware());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains(RequireCreditAdministrator::class, $route->gatherMiddleware());
        }
        $this->app->instance('env', 'production');
        $middleware = new class($this->app, $this->app['encrypter']) extends PreventRequestForgery
        {
            protected $except = ['api/*'];

            protected $addHttpCookie = false;
        };
        $session = new Store('credit-test', new ArraySessionHandler(120));
        $session->put('_token', 'expected');
        $request = Request::create('/admin/ai/credits/grant', 'POST');
        $request->setLaravelSession($session);
        $this->expectException(TokenMismatchException::class);
        $middleware->handle($request, fn () => $this->fail('CSRF bypassed'));
    }

    public function test_credit_admin_middleware_returns_403_for_non_admin(): void
    {
        $this->admin->allowed = false;
        try {
            (new RequireCreditAdministrator($this->admin))->handle(
                Request::create('/admin/ai/credits'), fn () => $this->fail('Student reached credit admin')
            );
            $this->fail('Expected 403');
        } catch (HttpException $error) {
            $this->assertSame(403, $error->getStatusCode());
        }
    }

    public function test_migration_preserves_accounts_and_reports_collisions(): void
    {
        $this->assertSame(100, DB::table('tutor_ai_credit_accounts')->value('balance'));
        $this->assertSame('conflict_or_partial', (new CreditAdminSchema)->inspect()['state']);
        Schema::create('migrations', function ($t) {
            $t->id();
            $t->string('migration');
            $t->integer('batch');
        });
        DB::table('migrations')->insert(['migration' => CreditAdminSchema::MIGRATION, 'batch' => 5]);
        $this->assertSame('installed', (new CreditAdminSchema)->inspect()['state']);
        $this->expectException(\RuntimeException::class);
        (require __DIR__.'/../../database/migrations/'.CreditAdminSchema::MIGRATION.'.php')->up();
    }
}
