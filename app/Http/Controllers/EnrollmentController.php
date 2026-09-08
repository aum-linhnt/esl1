<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\LMS\EnrollmentService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    /**
     * Enroll the current user into a course (Self-enrollment).
     */
    public function enroll(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::where('is_published', true)->findOrFail($courseId);

        // Check if self-enrollment is permitted
        if ($course->isManualEnrollmentOnly() && !$user->isAdmin()) {
            return redirect()->route('courses.show', $courseId)
                ->with('error', '🔒 Khóa học này chỉ dành cho Quản trị viên/Giáo viên ghi danh thủ công. Vui lòng liên hệ để được cấp quyền vào học.');
        }

        // Check enrollment key if required
        if ($course->requiresEnrollmentKey() && !$user->isAdmin()) {
            $providedKey = $request->input('enrollment_key');
            if (empty($providedKey) || $providedKey !== $course->enrollment_key) {
                return redirect()->route('courses.show', $courseId)
                    ->with('error', 'Mật khẩu ghi danh khóa học không chính xác. Vui lòng thử lại.');
            }
        }

        // Calculate default expiration date if course defines enrollment duration
        $expiresAt = null;
        if ($course->enrollment_duration_days && $course->enrollment_duration_days > 0) {
            $expiresAt = now()->addDays($course->enrollment_duration_days)->endOfDay();
        }

        $enrollment = $this->enrollmentService->enrollUser($user, $course, 'student', $expiresAt);

        $durationMsg = $expiresAt ? ' (Thời hạn: ' . $course->enrollment_duration_days . ' ngày, đến ' . $expiresAt->format('d/m/Y') . ')' : '';

        return redirect()->route('courses.show', $courseId)
            ->with('success', '🎉 Ghi danh thành công vào khóa học "' . $course->title . '"!' . $durationMsg);
    }

    /**
     * Unenroll (drop) the current user from a course.
     */
    public function unenroll(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::findOrFail($courseId);

        $result = $this->enrollmentService->unenrollUser($user, $course);

        if (!$result) {
            return redirect()->route('courses.show', $courseId)
                ->with('error', 'Không thể hủy ghi danh. Khóa học đã hoàn thành hoặc chưa ghi danh.');
        }

        return redirect()->route('courses.index')
            ->with('success', 'Đã hủy ghi danh khóa học "' . $course->title . '".');
    }

    /**
     * Show "My Courses" page with enrolled courses and progress.
     */
    public function myCourses(Request $request)
    {
        $user = $request->user();

        $enrollments = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->with('course.lessons')
            ->orderByDesc('enrolled_at')
            ->get();

        $activeCount = $enrollments->where('status', 'active')->count();
        $completedCount = $enrollments->where('status', 'completed')->count();
        $avgProgress = $enrollments->where('status', 'active')->avg('progress_percentage') ?? 0;

        return view('enrollments.my-courses', [
            'enrollments' => $enrollments,
            'activeCount' => $activeCount,
            'completedCount' => $completedCount,
            'avgProgress' => round($avgProgress),
        ]);
    }
}
