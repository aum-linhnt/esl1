<?php

namespace TDSoft\AiTutor\Http;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TDSoft\AiTutor\Billing\CreditAdministration;
use TDSoft\AiTutor\Billing\CreditAdminSchema;
use TDSoft\AiTutor\Contracts\CreditAdministrator;
use TDSoft\AiTutor\Core\AiException;

final class ReconciliationController
{
    public function index(CreditAdministration $service, CreditAdministrator $admin): mixed
    {
        $service->authorize();
        $items = DB::table('tutor_ai_requests')->where('error_code', 'AI_REQUEST_RECONCILIATION_REQUIRED')
            ->whereIn('status', ['authorized', 'processing'])->orderBy('created_at')->limit(100)->get();
        $audit = Schema::hasTable(CreditAdminSchema::TABLE) ? DB::table(CreditAdminSchema::TABLE)
            ->whereIn('action', ['request.reconcile.commit', 'request.reconcile.release'])
            ->orderByDesc('id')->limit(50)->get() : collect();
        $adminNames = $audit->pluck('actor_id')->unique()->mapWithKeys(
            fn ($id) => [$id => $admin->administratorName((string) $id)]);

        return response()->view('ai-tutor::reconciliation', compact('items', 'audit', 'adminNames'))
            ->header('Cache-Control', 'private, no-store');
    }

    public function update(Request $request, string $requestId, CreditAdministration $service): mixed
    {
        $service->authorize();
        $request->merge(['request_id' => $requestId]);
        $data = $request->validate([
            'request_id' => 'required|uuid', 'decision' => 'required|in:commit,release',
            'actual_units' => 'required|integer|min:0|max:1000000', 'reason' => 'required|string|min:10|max:500',
            'operation_id' => 'required|uuid', 'confirm' => 'accepted',
        ]);
        try {
            $service->reconcile($data['request_id'], $data['decision'], (int) $data['actual_units'],
                $data['reason'], $data['operation_id']);

            return redirect()->route('ai-tutor.reconciliation.index')
                ->with('reconciliation_notice', 'Đã đối soát request và cập nhật credit an toàn.');
        } catch (AiException $error) {
            return redirect()->route('ai-tutor.reconciliation.index')
                ->with('reconciliation_error', $error->errorCode);
        }
    }
}
