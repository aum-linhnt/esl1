<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Models\Lesson;
use Illuminate\Http\Request;

class CourseLessonController extends Controller
{
    /**
     * Store a new lesson for a course.
     */
    public function storeLesson(StoreLessonRequest $request, $courseId)
    {
        Lesson::create([
            'course_id' => $courseId,
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order,
            'estimated_minutes' => $request->estimated_minutes,
            'unlock_condition_score' => $request->unlock_condition_score,
            'is_free_trial' => $request->boolean('is_free_trial'),
        ]);

        return redirect()->route('admin.courses.show', $courseId)
            ->with('success', "Đã thêm bài học mới thành công!");
    }

    /**
     * Update an existing lesson.
     */
    public function updateLesson(StoreLessonRequest $request, $courseId, $lessonId)
    {
        $lesson = Lesson::where('id', $lessonId)->where('course_id', $courseId)->firstOrFail();
        $lesson->update([
            'title' => $request->title,
            'description' => $request->description,
            'order' => $request->order,
            'estimated_minutes' => $request->estimated_minutes,
            'unlock_condition_score' => $request->unlock_condition_score,
            'is_free_trial' => $request->boolean('is_free_trial'),
        ]);

        return redirect()->route('admin.courses.show', $courseId)
            ->with('success', "Đã cập nhật bài học '{$lesson->title}'.");
    }

    /**
     * Delete a lesson from a course.
     */
    public function destroyLesson($courseId, $lessonId)
    {
        Lesson::where('id', $lessonId)->where('course_id', $courseId)->delete();
        return redirect()->route('admin.courses.show', $courseId)
            ->with('success', 'Đã xóa bài học thành công.');
    }

    /**
     * Reorder lessons within a course via drag-drop (JSON API).
     * Expects: { items: [{id: 1, order: 1}, {id: 2, order: 2}, ...] }
     */
    public function reorderLessons(Request $request, $courseId)
    {
        $request->validate(['items' => 'required|array', 'items.*.id' => 'required|integer', 'items.*.order' => 'required|integer']);

        foreach ($request->items as $item) {
            Lesson::where('id', $item['id'])->where('course_id', $courseId)->update(['order' => $item['order']]);
        }

        return response()->json(['success' => true, 'message' => 'Đã cập nhật thứ tự bài học.']);
    }

    /**
     * 1-click toggle lesson visibility.
     */
    public function toggleLessonVisibility($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->update(['is_visible' => !$lesson->is_visible]);

        $status = $lesson->is_visible ? 'hiển thị' : 'ẩn';
        return response()->json(['success' => true, 'is_visible' => $lesson->is_visible, 'message' => "Bài học đã được {$status}."]);
    }

    /**
     * 1-click toggle lesson free trial status.
     */
    public function toggleLessonTrial($lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $lesson->update(['is_free_trial' => !$lesson->is_free_trial]);

        $status = $lesson->is_free_trial ? 'học thử (Free Trial)' : 'học chính thức (Cần ghi danh)';
        return response()->json(['success' => true, 'is_free_trial' => $lesson->is_free_trial, 'message' => "Bài học đã chuyển sang chế độ {$status}."]);
    }
}
