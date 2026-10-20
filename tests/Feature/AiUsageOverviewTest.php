<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Learning\AiCreditOverview;
use App\Services\Learning\AiUsageOverview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class AiUsageOverviewTest extends TestCase
{
    use RefreshDatabase;

    private function aiRequest(User $user, string $status = 'completed', $time = null): string
    {
        $id = (string) Str::uuid();
        DB::table('tutor_ai_requests')->insert(['request_id' => $id, 'idempotency_key' => $id, 'fingerprint' => str_repeat('a', 64),
            'user_id' => (string) $user->id, 'feature' => 'writing', 'billing_mode' => 'vendor_credit', 'status' => $status,
            'created_at' => $time ?? now(), 'updated_at' => $time ?? now()]);
        return $id;
    }

    public function test_credit_reads_available_balance_once_and_includes_old_pending_holds_in_quota(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $user = User::factory()->create();
        $other = User::factory()->create();
        $id = DB::table('tutor_ai_credit_accounts')->insertGetId(['owner_type' => 'learner', 'owner_id' => (string) $user->id, 'scope' => 'system', 'balance' => 80, 'daily_limit' => 10, 'status' => 'active']);
        $foreign = DB::table('tutor_ai_credit_accounts')->insertGetId(['owner_type' => 'learner', 'owner_id' => (string) $other->id, 'scope' => 'system', 'balance' => 123456, 'status' => 'active']);
        $pending = $this->aiRequest($user, 'processing', now()->subDay());
        $done = $this->aiRequest($user);
        foreach ([[$id, $pending, 'reserve', 3, now()->subDay(), 'writing'], [$id, $done, 'reserve', 4, now(), 'writing'], [$id, $done, 'commit', 2, now(), 'writing'], [$id, $done, 'release', 2, now(), 'writing'], [$foreign, null, 'grant', 99, now(), 'foreign-secret']] as [$account, $request, $type, $units, $time, $feature]) {
            DB::table('tutor_ai_credit_transactions')->insert(['account_id' => $account, 'request_id' => $request, 'type' => $type, 'units' => $units, 'feature' => $feature, 'balance_after' => 80, 'idempotency_key' => Str::uuid(), 'created_at' => $time]);
        }
        $account = app(AiCreditOverview::class)->account($user);
        $this->assertSame(80, (int) $account['balance']);
        $this->assertSame(3, $account['held']);
        $this->assertSame(5, $account['quotas']['daily_limit']['remaining']);
        $this->actingAs($user)->get('/dashboard-v2/credits?user_id='.$other->id)->assertOk()->assertSee('80 credit khả dụng')->assertDontSee('foreign-secret')->assertHeader('Cache-Control', 'no-store, private');
        $this->get('/dashboard-v2')->assertOk()->assertSee('80 credit');
    }

    public function test_empty_and_unlimited_accounts_do_not_grant_credit_on_read(): void
    {
        $user = User::factory()->create();
        $this->get('/dashboard-v2/credits')->assertRedirect('/login');
        $this->actingAs($user)->get('/dashboard-v2/credits')->assertOk()->assertSee('Chưa có tài khoản');
        $this->assertDatabaseCount('tutor_ai_credit_accounts', 0);
        DB::table('tutor_ai_credit_accounts')->insert(['owner_type' => 'learner', 'owner_id' => (string) $user->id, 'scope' => 'system', 'balance' => null, 'status' => 'inactive']);
        $this->get('/dashboard-v2/credits')->assertOk()->assertSee('Số dư không giới hạn')->assertSee('tạm khóa');
        $this->get('/dashboard-v2')->assertOk()->assertSee('Credit tạm khóa');
    }

    public function test_usage_keeps_currencies_and_unknown_costs_separate_without_double_counting(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(12));
        $user = User::factory()->create();
        foreach ([['USD', '1.250000', now()], ['USD', null, now()], ['VND', '25000.000000', now()], ['USD', '99.000000', now()->subDays(91)]] as [$currency, $cost, $time]) {
            $id = $this->aiRequest($user, 'completed', $time);
            DB::table('tutor_ai_usage_records')->insert(['request_id' => $id, 'user_id' => (string) $user->id, 'feature' => 'writing', 'billing_mode' => 'customer_key', 'provider' => 'test', 'model' => 'test-model', 'input_tokens' => 100, 'output_tokens' => 20, 'cached_tokens' => 30, 'credit_units' => 2, 'estimated_cost' => $cost, 'currency' => $currency, 'status' => 'completed', 'created_at' => $time]);
            DB::table('tutor_ai_cost_snapshots')->insert(['request_id' => $id, 'estimated_cost' => $cost, 'currency' => $currency, 'created_at' => $time]);
        }
        $this->aiRequest($user, 'failed');
        $report = app(AiUsageOverview::class)->report(30);
        $this->assertSame(3, (int) $report['totals']->total);
        $this->assertSame(300, (int) $report['totals']->input_tokens);
        $this->assertSame(90, (int) $report['totals']->cached_tokens);
        $usd = $report['costs']->firstWhere('currency', 'USD');
        $this->assertEquals(1.25, $usd->cost);
        $this->assertSame(1, (int) $usd->unpriced);
        $this->assertEquals(25000, $report['costs']->firstWhere('currency', 'VND')->cost);
        $this->assertSame(1, (int) $report['statuses']->firstWhere('status', 'failed')->total);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get('/admin/ai/usage')->assertOk()->assertSee('1.250000')->assertSee('25,000.000000')->assertSee('Chưa có giá')->assertDontSee('99.000000');
        $this->get('/admin/ai/usage?days=365')->assertSessionHasErrors('days');
    }

    public function test_report_uses_vietnam_calendar_bounds_with_utc_storage(): void
    {
        config(['app.timezone' => 'UTC']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-06 03:00:00', 'UTC'));
        $user = User::factory()->create();
        $this->aiRequest($user, 'completed', '2026-09-29 17:00:00'); // Sept 30 midnight in Vietnam.
        $this->aiRequest($user, 'failed', '2026-09-29 16:59:59');
        $this->aiRequest($user, 'pending', '2026-10-06 03:00:01'); // Future records are excluded.
        $report = app(AiUsageOverview::class)->report(7);
        $this->assertCount(1, $report['statuses']);
        $this->assertSame('completed', $report['statuses']->first()->status);
    }

    public function test_usage_is_only_visible_to_active_administrators(): void
    {
        $this->get('/admin/ai/usage')->assertRedirect('/login');
        foreach ([['student', 'active'], ['teacher', 'active'], ['admin', 'blocked']] as [$role, $status]) {
            $this->actingAs(User::factory()->create(compact('role', 'status')))->get('/admin/ai/usage')->assertForbidden();
        }
        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))->get('/admin/ai/usage?days=7')->assertOk()->assertSee('Chưa có dữ liệu');
    }
}
