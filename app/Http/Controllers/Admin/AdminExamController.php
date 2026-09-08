<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExamSet;
use App\Models\QuestionBank;
use App\Models\AssessmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminExamController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamSet::with('creator')->withCount('submissions');

        if ($request->filled('skill') && $request->skill !== 'all') {
            $query->where('skill', $request->skill);
        }

        if ($request->filled('difficulty') && $request->difficulty !== 'all') {
            $query->where('difficulty', $request->difficulty);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_published', $request->status === 'published');
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('key', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $exams = $query->latest()->paginate(10)->withQueryString();

        // Metrics
        $totalExams = ExamSet::count();
        $publishedCount = ExamSet::where('is_published', true)->count();
        $fullMockCount = ExamSet::where('skill', 'full_mock')->count();
        $totalSubmissions = AssessmentSubmission::count();

        return view('admin.exams.index', [
            'exams' => $exams,
            'totalExams' => $totalExams,
            'publishedCount' => $publishedCount,
            'fullMockCount' => $fullMockCount,
            'totalSubmissions' => $totalSubmissions,
            'currentSkill' => $request->get('skill', 'all'),
            'currentDifficulty' => $request->get('difficulty', 'all'),
            'currentStatus' => $request->get('status', 'all'),
            'searchKeyword' => $request->get('search', ''),
        ]);
    }

    public function create()
    {
        $questions = QuestionBank::all();

        return view('admin.exams.form', [
            'exam' => new ExamSet(),
            'isEdit' => false,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'key' => 'nullable|string|max:100|unique:exam_sets,key',
            'skill' => 'required|in:full_mock,reading,listening,writing,speaking',
            'difficulty' => 'required|in:A1,A2,B1,B2,Mixed',
            'duration_minutes' => 'required|integer|min:1|max:180',
            'reward_coins' => 'required|integer|min:0|max:500',
            'description' => 'nullable|string|max:1000',
            'is_published' => 'nullable|boolean',
            'sections' => 'nullable|array',
            'question_ids' => 'nullable|array',
        ]);

        if (empty($validated['key'])) {
            $validated['key'] = Str::slug($validated['title'], '_') . '_' . Str::random(5);
        } else {
            $validated['key'] = Str::slug($validated['key'], '_');
        }

        $validated['is_published'] = $request->has('is_published');
        $validated['created_by'] = $request->user()->id;

        // If Full 4-Skill Mock Test, ensure sections are structured for 4 core skills
        if ($validated['skill'] === 'full_mock' && empty($validated['question_ids'])) {
            $validated['sections'] = [
                'listening' => (int) $request->input('sections.listening', 35),
                'reading' => (int) $request->input('sections.reading', 40),
                'writing' => (int) $request->input('sections.writing', 2),
                'speaking' => (int) $request->input('sections.speaking', 3),
            ];
            $validated['question_count'] = array_sum($validated['sections']);
        } elseif (!empty($validated['question_ids'])) {
            $validated['question_count'] = count($validated['question_ids']);
        } else {
            $validated['question_count'] = (int) $request->input('question_count', 5);
        }

        ExamSet::create($validated);

        return redirect()->route('admin.exams.index')->with('success', 'Đã tạo đề thi mới thành công!');
    }

    public function edit(int $id)
    {
        $exam = ExamSet::findOrFail($id);
        $questions = QuestionBank::all();

        return view('admin.exams.form', [
            'exam' => $exam,
            'isEdit' => true,
            'questions' => $questions,
        ]);
    }

    public function update(Request $request, int $id)
    {
        $exam = ExamSet::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'key' => 'required|string|max:100|unique:exam_sets,key,' . $exam->id,
            'skill' => 'required|in:full_mock,reading,listening,writing,speaking',
            'difficulty' => 'required|in:A1,A2,B1,B2,Mixed',
            'duration_minutes' => 'required|integer|min:1|max:180',
            'reward_coins' => 'required|integer|min:0|max:500',
            'description' => 'nullable|string|max:1000',
            'is_published' => 'nullable|boolean',
            'sections' => 'nullable|array',
            'question_ids' => 'nullable|array',
        ]);

        $validated['key'] = Str::slug($validated['key'], '_');
        $validated['is_published'] = $request->has('is_published');

        if ($validated['skill'] === 'full_mock' && empty($validated['question_ids'])) {
            $validated['sections'] = [
                'listening' => (int) $request->input('sections.listening', 35),
                'reading' => (int) $request->input('sections.reading', 40),
                'writing' => (int) $request->input('sections.writing', 2),
                'speaking' => (int) $request->input('sections.speaking', 3),
            ];
            $validated['question_count'] = array_sum($validated['sections']);
        } elseif (!empty($validated['question_ids'])) {
            $validated['question_count'] = count($validated['question_ids']);
        } else {
            $validated['question_count'] = (int) $request->input('question_count', 5);
        }

        $exam->update($validated);

        return redirect()->route('admin.exams.index')->with('success', 'Đã cập nhật đề thi thành công!');
    }

    public function destroy(int $id)
    {
        $exam = ExamSet::findOrFail($id);
        $exam->delete();

        return redirect()->route('admin.exams.index')->with('success', 'Đã xóa đề thi thành công!');
    }

    public function togglePublish(int $id)
    {
        $exam = ExamSet::findOrFail($id);
        $exam->update(['is_published' => !$exam->is_published]);

        return redirect()->back()->with('success', $exam->is_published ? 'Đã kích hoạt xuất bản đề thi!' : 'Đã ẩn đề thi khỏi học viên.');
    }
}
