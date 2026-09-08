<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\LMS\EnrollmentService;
use Illuminate\Http\Request;

class CourseEnrollmentController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService
    ) {}

    /**
     * Admin manually enrolls a user into a course with a designated Course Context Role and Expiration Date (Moodle-style).
     */
    public function manualEnroll(Request $request, $courseId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'course_role' => 'required|in:student,teacher,assistant,manager',
            'duration_preset' => 'nullable|string|in:unlimited,30_days,60_days,90_days,180_days,365_days,custom_date',
            'custom_expires_at' => 'nullable|date|after:today',
        ]);

        $course = Course::findOrFail($courseId);
        $user = User::findOrFail($request->user_id);
        $courseRole = $request->input('course_role', 'student');
        $durationPreset = $request->input('duration_preset', 'unlimited');

        // Calculate expires_at
        $expiresAt = match ($durationPreset) {
            '30_days' => now()->addDays(30),
            '60_days' => now()->addDays(60),
            '90_days' => now()->addDays(90),
            '180_days' => now()->addDays(180),
            '365_days' => now()->addDays(365),
            'custom_date' => $request->filled('custom_expires_at') ? \Carbon\Carbon::parse($request->custom_expires_at)->endOfDay() : null,
            default => null, // unlimited
        };

        // Check if already enrolled
        $existing = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if ($existing && $existing->status !== 'dropped') {
            return back()->with('active_tab', 'students')->with('error', "Người dùng '{$user->name}' đã được ghi danh trong khóa học này.");
        }

        $this->enrollmentService->enrollUser($user, $course, $courseRole, $expiresAt);

        $roleName = Enrollment::$roleDefinitions[$courseRole]['name'] ?? $courseRole;
        $expiryText = $expiresAt ? ' (Hạn đến ' . $expiresAt->format('d/m/Y') . ')' : ' (Vô thời hạn)';

        return back()->with('active_tab', 'students')->with('success', "✅ Đã ghi danh '{$user->name}' với vai trò: '{$roleName}'{$expiryText} thành công!");
    }

    /**
     * Update a user's course context role in an existing enrollment.
     */
    public function updateEnrollmentRole(Request $request, $courseId, $userId)
    {
        $request->validate([
            'course_role' => 'required|in:student,teacher,assistant,manager',
        ]);

        $enrollment = Enrollment::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $newRole = $request->input('course_role');
        $this->enrollmentService->updateCourseRole($enrollment, $newRole);

        $roleName = Enrollment::$roleDefinitions[$newRole]['name'] ?? $newRole;
        $userName = $enrollment->user->name ?? 'Người dùng';

        return back()->with('active_tab', 'students')->with('success', "✅ Đã cập nhật vai trò khóa học của '{$userName}' thành: '{$roleName}'!");
    }

    /**
     * Extend or modify enrollment expiration date for a participant.
     */
    public function updateEnrollmentExpiry(Request $request, $courseId, $userId)
    {
        $request->validate([
            'duration_preset' => 'required|string|in:unlimited,30_days,60_days,90_days,180_days,365_days,custom_date',
            'custom_expires_at' => 'nullable|date',
        ]);

        $enrollment = Enrollment::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $durationPreset = $request->input('duration_preset');
        $expiresAt = match ($durationPreset) {
            '30_days' => now()->addDays(30),
            '60_days' => now()->addDays(60),
            '90_days' => now()->addDays(90),
            '180_days' => now()->addDays(180),
            '365_days' => now()->addDays(365),
            'custom_date' => $request->filled('custom_expires_at') ? \Carbon\Carbon::parse($request->custom_expires_at)->endOfDay() : null,
            default => null, // unlimited
        };

        $this->enrollmentService->updateExpiry($enrollment, $expiresAt);

        $userName = $enrollment->user->name ?? 'Người dùng';
        $expiryText = $expiresAt ? 'đến ngày ' . $expiresAt->format('d/m/Y') : 'Vô thời hạn (Unlimited)';

        return back()->with('active_tab', 'students')->with('success', "✅ Đã cập nhật hạn kết thúc ghi danh của '{$userName}': {$expiryText}!");
    }

    /**
     * 1-Click Toggle Suspend / Active status for an enrolled participant in course context (Moodle-style).
     */
    public function toggleSuspendEnrollment(Request $request, $courseId, $userId)
    {
        $enrollment = Enrollment::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $userName = $enrollment->user->name ?? 'Học viên';
        $isActive = $this->enrollmentService->toggleSuspend($enrollment);

        if ($isActive) {
            return back()->with('active_tab', 'students')->with('success', "✅ Đã kích hoạt lại quyền truy cập khóa học cho '{$userName}'!");
        } else {
            return back()->with('active_tab', 'students')->with('success', "⏸️ Đã tạm đình chỉ (Suspend) ghi danh của '{$userName}' trong khóa học này!");
        }
    }

    /**
     * Admin manually removes a user from a course.
     */
    public function manualUnenroll(Request $request, $courseId, $userId)
    {
        $enrollment = Enrollment::where('course_id', $courseId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $userName = $enrollment->user->name ?? 'Unknown';
        $this->enrollmentService->unenrollUser($enrollment->user, $enrollment->course, true);

        return back()->with('active_tab', 'students')->with('success', "Đã hủy ghi danh '{$userName}' khỏi khóa học.");
    }
}
