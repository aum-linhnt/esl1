<?php

namespace App\Services\Learning;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AiUsageOverview
{
    public function report(int $days): array
    {
        $start = now()->timezone('Asia/Ho_Chi_Minh')->startOfDay()->subDays($days - 1);
        $end = now();
        $ready = Schema::hasTable('tutor_ai_usage_records') && Schema::hasTable('tutor_ai_requests');
        $empty = ['ready' => $ready, 'start' => $start, 'end' => $end, 'statuses' => collect(), 'totals' => null, 'costs' => collect(), 'features' => collect(), 'history' => null];
        if (! $ready) {
            return $empty;
        }
        $from = $start->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $to = $end->copy()->timezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $usage = DB::table('tutor_ai_usage_records')->whereBetween('created_at', [$from, $to]);
        // Usage and cost snapshots describe the same request. Sum the recorded usage once, without joining snapshots.
        return array_replace($empty, [
            'statuses' => DB::table('tutor_ai_requests')->whereBetween('created_at', [$from, $to])
                ->selectRaw('status, COUNT(*) as total')->groupBy('status')->get(),
            'totals' => (clone $usage)->selectRaw('COUNT(*) as total, COALESCE(SUM(input_tokens), 0) as input_tokens, COALESCE(SUM(output_tokens), 0) as output_tokens, COALESCE(SUM(cached_tokens), 0) as cached_tokens, COALESCE(SUM(audio_seconds), 0) as audio_seconds, COALESCE(SUM(credit_units), 0) as credit_units')->first(),
            'costs' => (clone $usage)->selectRaw('currency, SUM(estimated_cost) as cost, COUNT(estimated_cost) as priced, COUNT(*) - COUNT(estimated_cost) as unpriced')->groupBy('currency')->get(),
            'features' => (clone $usage)->selectRaw('feature, billing_mode, provider, model, COUNT(*) as total, SUM(input_tokens) as input_tokens, SUM(output_tokens) as output_tokens, SUM(credit_units) as credit_units')->groupBy('feature', 'billing_mode', 'provider', 'model')->orderBy('feature')->get(),
            'history' => (clone $usage)->select('feature', 'billing_mode', 'provider', 'model', 'input_tokens', 'output_tokens', 'credit_units', 'estimated_cost', 'currency', 'created_at')->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString(),
        ]);
    }
}
