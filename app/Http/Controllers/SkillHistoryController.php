<?php

namespace App\Http\Controllers;

use App\Models\LearnerSkillSnapshot;
use App\Services\Learning\SkillSnapshots;
use Illuminate\Http\Request;

final class SkillHistoryController extends Controller
{
    public function index(Request $request, SkillSnapshots $snapshots)
    {
        return view('dashboard-v2.skill-history', ['history' => $snapshots->ready()
            ? LearnerSkillSnapshot::where('user_id', $request->user()->id)->orderByDesc('assessed_at')->orderByDesc('id')->paginate(20) : null]);
    }
}
