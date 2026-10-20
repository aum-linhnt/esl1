<?php

namespace App\Http\Controllers;

use App\Models\LearningGoal;
use App\Services\Learning\LearningGoals;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class LearningGoalController extends Controller
{
    public function edit(Request $request, LearningGoals $goals)
    {
        $query = $request->validate(['framework' => ['nullable', 'string', Rule::in(array_keys(LearningGoal::targets()))]]);
        return view('dashboard-v2.goal-edit', ['goal' => $goals->forUser($request->user()),
            'goalReady' => $goals->ready(), 'initialFramework' => $query['framework'] ?? null, 'user' => $request->user()]);
    }

    public function update(Request $request, LearningGoals $goals)
    {
        abort_unless($goals->ready(), 503, 'Mục tiêu học tập chưa sẵn sàng.');
        $framework = is_string($request->input('framework')) ? $request->input('framework') : '';
        $data = $request->validateWithBag('learningGoal', [
            'framework' => ['required', 'string', Rule::in(array_keys(LearningGoal::targets()))],
            'target' => ['required', 'string', Rule::in(array_keys(LearningGoal::targets()[$framework] ?? []))],
            'target_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ], [
            'framework.in' => 'Chọn CEFR, TOEIC hoặc IELTS.',
            'target.in' => 'Chọn mục tiêu phù hợp với hệ chứng chỉ.',
            'target_date.after_or_equal' => 'Thời hạn phải từ hôm nay trở đi.',
        ]);
        $data['target_date'] ??= null;
        DB::transaction(function () use ($request, $data) {
            LearningGoal::updateOrCreate(['user_id' => $request->user()->id], $data);
            if ($data['framework'] === 'cefr') $request->user()->update(['target_level' => $data['target']]);
        });
        return redirect()->route('dashboard.v2')->with('learning-goal-saved', true);
    }
}
