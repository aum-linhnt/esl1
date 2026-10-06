<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Learning\AiUsageOverview;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class AiUsageController extends Controller
{
    public function index(Request $request, AiUsageOverview $usage)
    {
        $data = $request->validate(['days' => ['sometimes', 'integer', Rule::in([7, 30, 90])]]);
        $days = (int) ($data['days'] ?? 30);
        return response()->view('admin.ai-usage', ['days' => $days, ...$usage->report($days)])->header('Cache-Control', 'no-store');
    }
}
