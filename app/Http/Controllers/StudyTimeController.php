<?php

namespace App\Http\Controllers;

use App\Services\Learning\StudyTime;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class StudyTimeController extends Controller
{
    public function store(Request $request, StudyTime $time)
    {
        $data = $request->validate(['source' => ['required', 'string', Rule::in(['lesson', 'activity', 'speaking', 'writing'])],
            'context_id' => ['nullable', 'integer', 'min:1', 'required_if:source,lesson,activity', 'prohibited_if:source,speaking,writing']]);
        return response()->json(['id' => $time->start($request->user(), $data['source'], $data['context_id'] ?? null)], 201);
    }

    public function update(Request $request, string $id, StudyTime $time)
    {
        $data = $request->validate(['active_seconds' => ['required', 'integer', 'min:0', 'max:86400']]);
        return response()->json(['recorded_seconds' => $time->report($request->user(), $id, $data['active_seconds'])]);
    }

    public function index(Request $request, StudyTime $time)
    {
        return view('dashboard-v2.study-progress', ['studyDays' => $time->week($request->user())]);
    }
}
