<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSubmission;
use Illuminate\Http\Request;

class SubmissionController extends Controller
{
    public function index(Request $request)
    {
        $testType = $request->get('test_type');
        $passed = $request->get('passed');
        $search = $request->get('search');

        $query = AssessmentSubmission::with('user');

        if ($testType) {
            $query->where('test_type', $testType);
        }

        if ($passed !== null && $passed !== '') {
            $query->where('is_passed', (bool)$passed);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $submissions = $query->latest()->paginate(15);

        return view('admin.submissions.index', compact('submissions', 'testType', 'passed', 'search'));
    }

    public function show($id)
    {
        $submission = AssessmentSubmission::with('user')->findOrFail($id);
        return view('admin.submissions.show', compact('submission'));
    }

    public function destroy($id)
    {
        $submission = AssessmentSubmission::findOrFail($id);
        $submission->delete();

        return redirect()->route('admin.submissions.index')
            ->with('success', 'Đã xóa bản ghi bài nộp thành công.');
    }
}
