<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\QuestionBank;
use App\Models\UserProgress;
use App\Services\Storage\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function __construct(
        private \App\Services\LMS\EnrollmentService $enrollmentService
    ) {}

    /**
     * Display a listing of courses with curriculum metrics.
     */
    public function index(Request $request)
    {
        $level = $request->get('level');
        $query = Course::withCount(['lessons'])->with(['lessons.activities']);

        if ($level) {
            $query->where('level', $level);
        }

        $courses = $query->orderBy('order')->get();

        return view('admin.courses.index', compact('courses', 'level'));
    }

    /**
     * Show the form for creating a new course.
     */
    public function create()
    {
        return view('admin.courses.form', ['course' => null]);
    }

    /**
     * Store a newly created course in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        $thumbnail = $request->thumbnail ?: '/images/course-' . strtolower($request->level) . '.png';
        if ($request->hasFile('thumbnail_file')) {
            $thumbRecord = app(FileStorageService::class)->store($request->file('thumbnail_file'), 'courses/thumbnails');
            $thumbnail = $thumbRecord->storage_path;
        }

        $course = Course::create([
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . Str::random(4),
            'description' => $request->description,
            'level' => $request->level,
            'target_audience' => $request->target_audience,
            'thumbnail' => $thumbnail,
            'order' => $request->order,
            'is_published' => $request->boolean('is_published'),
            'allow_self_enrollment' => $request->boolean('allow_self_enrollment', true),
            'enrollment_key' => $request->enrollment_key,
            'enrollment_duration_days' => $request->enrollment_duration_days ? (int) $request->enrollment_duration_days : null,
            'grading_scale' => $request->input('grading_scale', 'scale_100'),
            'passing_grade' => $request->input('passing_grade', 50),
            'certificate_enabled' => $request->boolean('certificate_enabled', true),
            'badge_reward' => $request->badge_reward,
            'created_by' => $request->user()->id,
        ]);


        return redirect()->route('admin.courses.show', $course->id)
            ->with('success', "Đã tạo khóa học '{$course->title}' thành công! Hãy bắt đầu thêm bài học và học liệu.");
    }

    /**
     * Display course curriculum studio and enrolled students.
     */
    public function show($courseId)
    {
        $course = Course::with(['lessons.activities.file'])->findOrFail($courseId);

        // Enrolled students from Enrollment model
        $enrollments = \App\Models\Enrollment::where('course_id', $courseId)
            ->with('user')
            ->get();

        $totalLessons = $course->lessons->count();
        $enrolledStudents = [];

        foreach ($enrollments as $enrollment) {
            $user = $enrollment->user;
            if (!$user) continue;

            $completedCount = UserProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $course->lessons->pluck('id'))
                ->where('completed', true)
                ->count();

            $percentage = $totalLessons > 0 ? round(($completedCount / $totalLessons) * 100) : 0;

            $lastProgress = UserProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $course->lessons->pluck('id'))
                ->orderBy('updated_at', 'desc')
                ->first();

            $enrolledStudents[] = [
                'user' => $user,
                'enrollment' => $enrollment,
                'completed_lessons' => $completedCount,
                'total_lessons' => $totalLessons,
                'percentage' => $percentage,
                'last_active' => $lastProgress?->updated_at,
            ];
        }

        // Users NOT enrolled in this course (for manual enrollment dropdown)
        $enrolledUserIds = $enrollments->pluck('user_id')->toArray();
        $unenrolledUsers = \App\Models\User::whereNotIn('id', $enrolledUserIds)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username', 'role']);

        $courseRoles = \App\Models\Enrollment::$roleDefinitions;

        // Questions belonging specifically to this course
        $courseQuestions = QuestionBank::where('course_id', $courseId)
            ->orderBy('id', 'desc')
            ->get();

        // Global questions available for import
        $globalQuestions = QuestionBank::whereNull('course_id')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.courses.show', compact('course', 'enrolledStudents', 'unenrolledUsers', 'courseRoles', 'courseQuestions', 'globalQuestions'));
    }

    /**
     * Show the form for editing the course metadata.
     */
    public function edit($courseId)
    {
        $course = Course::with('lessons.activities')->findOrFail($courseId);
        return view('admin.courses.form', ['course' => $course]);
    }

    /**
     * Update the specified course in storage.
     */
    public function update(UpdateCourseRequest $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        $thumbnail = $request->thumbnail ?: $course->thumbnail;
        if ($request->hasFile('thumbnail_file')) {
            $thumbRecord = app(FileStorageService::class)->store($request->file('thumbnail_file'), 'courses/thumbnails');
            $thumbnail = $thumbRecord->storage_path;
        }

        $course->update([
            'title' => $request->title,
            'description' => $request->description,
            'level' => $request->level,
            'target_audience' => $request->target_audience,
            'thumbnail' => $thumbnail,
            'order' => $request->order,
            'is_published' => $request->boolean('is_published'),
            'allow_self_enrollment' => $request->boolean('allow_self_enrollment'),
            'enrollment_key' => $request->enrollment_key,
            'enrollment_duration_days' => $request->enrollment_duration_days ? (int) $request->enrollment_duration_days : null,
            'grading_scale' => $request->input('grading_scale', $course->grading_scale),
            'passing_grade' => $request->input('passing_grade', $course->passing_grade),
            'certificate_enabled' => $request->boolean('certificate_enabled', true),
            'badge_reward' => $request->badge_reward,
        ]);


        return redirect()->route('admin.courses.show', $course->id)
            ->with('success', "Đã cập nhật khóa học '{$course->title}' thành công.");
    }

    /**
     * Remove the specified course from storage.
     */
    public function destroy($courseId)
    {
        $course = Course::findOrFail($courseId);
        $title = $course->title;
        $course->delete();

        return redirect()->route('admin.courses.index')
            ->with('success', "Đã xóa khóa học '{$title}' cùng toàn bộ bài học thành công.");
    }

    /**
     * 1-Click Toggle Publish Status.
     */
    public function togglePublish($courseId)
    {
        $course = Course::findOrFail($courseId);
        $course->update(['is_published' => !$course->is_published]);

        $status = $course->is_published ? 'Xuất bản (Published)' : 'Ẩn (Draft)';
        return back()->with('success', "Khóa học '{$course->title}' đã chuyển sang trạng thái {$status}.");
    }
}
