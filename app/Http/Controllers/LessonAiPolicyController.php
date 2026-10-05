<?php

namespace App\Http\Controllers;

use App\Http\Requests\LessonAiPolicyRequest;
use App\Models\Lesson;
use Illuminate\Http\Request;

class LessonAiPolicyController extends Controller
{
    public function edit(Request $request, string $courseId, string $lessonId)
    {
        $lesson = Lesson::where('course_id', $courseId)->findOrFail($lessonId);
        abort_unless($lesson->canManageAiTutorPolicy($request->user()), 403);

        return view('lessons.ai-policy', compact('lesson'));
    }

    public function update(LessonAiPolicyRequest $request, string $courseId, string $lessonId)
    {
        $lesson = Lesson::where('course_id', $courseId)->findOrFail($lessonId);
        $lesson->update($request->validated());

        return redirect()->route('courses.lessons.ai-policy.edit', [$courseId, $lessonId])
            ->with('success', 'Đã lưu chính sách Gia sư AI của bài học.');
    }
}
