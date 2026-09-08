<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Services\LMS\GradebookService;
use Illuminate\Http\Request;

class GradebookController extends Controller
{
    public function __construct(
        private GradebookService $gradebookService
    ) {}

    /**
     * Show the student's gradebook overview.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $enrollments = Enrollment::where('user_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->with('course')
            ->get();

        $courseGrades = $enrollments->map(function ($enrollment) use ($user) {
            $course = $enrollment->course;
            $rawGrade = $this->gradebookService->getCourseGrade($user, $course);
            $formatted = $this->gradebookService->formatGradeByScale(
                $rawGrade,
                $course->grading_scale ?? 'scale_100',
                (float) ($course->passing_grade ?? 50)
            );

            return [
                'enrollment' => $enrollment,
                'course' => $course,
                'raw_grade' => $rawGrade,
                'formatted' => $formatted,
                'status' => $enrollment->status,
            ];
        });

        $overallGPA = $this->gradebookService->getOverallGPA($user);
        $overallFormatted = $this->gradebookService->formatGradeByScale($overallGPA, 'scale_100', 50);

        return view('gradebook.index', [
            'courseGrades' => $courseGrades,
            'overallGPA' => $overallGPA,
            'overallFormatted' => $overallFormatted,
        ]);
    }

    /**
     * Show detailed grades for a specific course.
     */
    public function courseDetail(Request $request, $courseId)
    {
        $user = $request->user();
        $course = Course::with('lessons.activities')->findOrFail($courseId);

        // Ensure user is enrolled
        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->firstOrFail();

        $lessonGrades = $this->gradebookService->getLessonGrades($user, $course);
        $rawCourseGrade = $this->gradebookService->getCourseGrade($user, $course);
        $courseFormatted = $this->gradebookService->formatGradeByScale(
            $rawCourseGrade,
            $course->grading_scale ?? 'scale_100',
            (float) ($course->passing_grade ?? 50)
        );

        return view('gradebook.show', [
            'course' => $course,
            'enrollment' => $enrollment,
            'lessonGrades' => $lessonGrades,
            'courseGrade' => $rawCourseGrade,
            'courseFormatted' => $courseFormatted,
        ]);
    }
}
