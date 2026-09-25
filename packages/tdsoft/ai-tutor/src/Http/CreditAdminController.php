<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TDSoft\AiTutor\Billing\CreditAdministration;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Core\AiException;

final class CreditAdminController
{
    public function index(Request $request, CreditAdministration $service, CreditAdministrator $admin): mixed
    {
        $service->authorize();
        $data = $request->validate(['tab' => 'nullable|in:rules,grant,history',
            'q' => 'nullable|string|max:100', 'recipient' => 'nullable|string|max:191']);
        $person = isset($data['recipient']) ? $admin->recipient($data['recipient']) : null;
        $account = $person ? DB::table('tutor_ai_credit_accounts')->where(['owner_type' => 'learner', 'owner_id' => $person['id'], 'scope' => 'system'])->first() : null;

        $heldUnits = 0;
        if ($account) {
            $heldUnits = (int) DB::table('tutor_ai_credit_transactions as reserve')
                ->where('reserve.account_id', $account->id)->where('reserve.type', 'reserve')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')->from('tutor_ai_credit_transactions as settled')
                        ->whereColumn('settled.request_id', 'reserve.request_id')
                        ->whereIn('settled.type', ['commit', 'release']);
                })->sum('reserve.units');
        }
        $pendingReconciliations = DB::table('tutor_ai_requests')
            ->where('error_code', 'AI_REQUEST_RECONCILIATION_REQUIRED')
            ->whereIn('status', ['authorized', 'processing'])->count();
        $rules = DB::table('tutor_ai_credit_rules')->get()->keyBy('feature');
        $ledger = $account ? DB::table('tutor_ai_credit_transactions')->where('account_id', $account->id)->orderByDesc('id')->limit(20)->get() : collect();
        $grantOperations = $ledger->where('type', 'grant')->pluck('idempotency_key')
            ->filter(fn ($key) => str_starts_with($key, 'grant:admin:'))
            ->map(fn ($key) => substr($key, strlen('grant:admin:')));
        $grantAudit = $person && Schema::hasTable(CreditAdminSchema::TABLE) ? DB::table(CreditAdminSchema::TABLE)
            ->where('action', 'credit.grant')->where('target_id', $person['id'])
            ->whereIn('operation_id', $grantOperations)->get()->keyBy('operation_id') : collect();
        $audit = Schema::hasTable(CreditAdminSchema::TABLE) ? DB::table(CreditAdminSchema::TABLE)
            ->whereIn('action', ['credit_rule.update', 'credit.grant'])->orderByDesc('id')->limit(20)->get() : collect();
        $adminNames = $audit->pluck('actor_id')->merge($grantAudit->pluck('actor_id'))->unique()->mapWithKeys(
            fn ($id) => [$id => $admin->administratorName((string) $id)]);

        return response()->view('ai-tutor::credits', [
            'people' => $admin->recipients($data['q'] ?? ''), 'person' => $person, 'account' => $account,
            'rules' => $rules, 'service' => $service, 'grantId' => (string) Str::uuid(),
            'ready' => Schema::hasTable(CreditAdminSchema::TABLE),
            'ledger' => $ledger, 'grantAudit' => $grantAudit,
            'audit' => $audit, 'adminNames' => $adminNames, 'tab' => $data['tab'] ?? 'rules',
            'heldUnits' => $heldUnits, 'pendingReconciliations' => $pendingReconciliations,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function rule(Request $request, CreditAdministration $service): mixed
    {
        $service->authorize();
        $d = $request->validate(['feature' => 'required|string|max:64', 'base_units' => 'required|integer|min:0|max:1000000',
            'max_units' => 'required|integer|min:0|max:1000000', 'enabled' => 'required|boolean', 'expected' => 'required|string|size:64', 'operation_id' => 'required|uuid']);

        return $this->action(fn () => $service->saveRule($d['feature'], (int) $d['base_units'], (int) $d['max_units'], (bool) $d['enabled'], $d['expected'], $d['operation_id']), 'rules');
    }

    public function grant(Request $request, CreditAdministration $service): mixed
    {
        $service->authorize();
        $d = $request->validate(['recipient' => 'required|string|max:191', 'units' => 'required|integer|min:1|max:1000000',
            'reason' => 'required|string|max:500', 'operation_id' => 'required|uuid', 'confirm' => 'accepted']);

        return $this->action(fn () => $service->grant($d['recipient'], (int) $d['units'], $d['reason'], $d['operation_id']), 'grant', $d['recipient']);
    }

    private function action(callable $work, string $tab, ?string $recipient = null): mixed
    {
        try {
            $work();

            return redirect()->route('ai-tutor.credits.index', array_filter(['tab' => $tab, 'recipient' => $recipient]))->with('credit_notice', 'Đã lưu. Gửi lại cùng mã thao tác không áp dụng lần thứ hai.');
        } catch (AiException $error) {
            return redirect()->route('ai-tutor.credits.index', array_filter(['tab' => $tab, 'recipient' => $recipient]))->with('credit_error', $error->errorCode);
        }
    }
}
