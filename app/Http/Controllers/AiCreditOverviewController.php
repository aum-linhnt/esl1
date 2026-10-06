<?php

namespace App\Http\Controllers;

use App\Services\Learning\AiCreditOverview;
use Illuminate\Http\Request;

final class AiCreditOverviewController extends Controller
{
    public function index(Request $request, AiCreditOverview $credits)
    {
        return response()->view('dashboard-v2.credits', [
            'account' => $credits->account($request->user()), 'history' => $credits->history($request->user()),
        ])->header('Cache-Control', 'no-store');
    }
}
