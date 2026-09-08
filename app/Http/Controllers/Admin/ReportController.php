<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\ActivityCompletion;
use App\Models\UserProgress;
use App\Services\LMS\EnrollmentService;
use App\Services\LMS\GradebookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private GradebookService $gradebookService
    ) {}

    /**
     * Reports overview dashboard.
     */
    public function index()
    {
        $enrollmentStats = $this->enrollmentService->getEnrollmentStats();
        $gradeDistribution = $this->gradebookService->getGradeDistribution();

        // Completion stats
        $totalLessonsCompleted = UserProgress::where('completed', true)->count();
        $totalActivitiesCompleted = ActivityCompletion::count();
        $avgCompletionTime = (int) (ActivityCompletion::avg('time_spent_seconds') ?? 0);

        // Enrollment trends (last 30 days)
        $enrollmentTrend = Enrollment::where('enrolled_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw('DATE(enrolled_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Top courses by enrollment
        $topCourses = Course::withCount(['enrollments' => function ($query) {
            $query->whereIn('status', ['active', 'completed']);
        }])
            ->orderByDesc('enrollments_count')
            ->take(5)
            ->get();

        // Recent enrollments
        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->latest('enrolled_at')
            ->take(10)
            ->get();

        return view('admin.reports.index', compact(
            'enrollmentStats',
            'gradeDistribution',
            'totalLessonsCompleted',
            'totalActivitiesCompleted',
            'avgCompletionTime',
            'enrollmentTrend',
            'topCourses',
            'recentEnrollments'
        ));
    }

    /**
     * Detailed enrollment report.
     */
    public function enrollments(Request $request)
    {
        $query = Enrollment::with(['user', 'course']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $enrollments = $query->latest('enrolled_at')->paginate(20);
        $courses = Course::orderBy('title')->get();

        return view('admin.reports.enrollments', compact('enrollments', 'courses'));
    }

    /**
     * Detailed grades report.
     */
    public function grades(Request $request)
    {
        $query = Enrollment::with(['user', 'course'])
            ->whereIn('status', ['active', 'completed']);

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $enrollments = $query->latest()->paginate(20);

        // Calculate grades for each enrollment
        $gradesData = $enrollments->getCollection()->map(function ($enrollment) {
            $grade = $this->gradebookService->getCourseGrade($enrollment->user, $enrollment->course);
            return [
                'enrollment' => $enrollment,
                'grade' => $grade,
                'letter' => $this->getLetterGrade($grade),
            ];
        });

        $courses = Course::orderBy('title')->get();
        $gradeDistribution = $this->gradebookService->getGradeDistribution();

        return view('admin.reports.grades', [
            'gradesData' => $gradesData,
            'enrollments' => $enrollments,
            'courses' => $courses,
            'gradeDistribution' => $gradeDistribution,
        ]);
    }

    /**
     * Detailed completion report.
     */
    public function completions(Request $request)
    {
        $query = ActivityCompletion::with(['user', 'activity.lesson.course', 'lesson']);

        if ($request->filled('course_id')) {
            $query->whereHas('lesson', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $completions = $query->latest('completed_at')->paginate(20);

        // Summary stats
        $avgScore = (float) (ActivityCompletion::avg('score') ?? 0);
        $avgTimeSpent = (int) (ActivityCompletion::avg('time_spent_seconds') ?? 0);
        $totalCompletions = ActivityCompletion::count();
        $todayCompletions = ActivityCompletion::whereDate('completed_at', Carbon::today())->count();

        $courses = Course::orderBy('title')->get();

        return view('admin.reports.completions', compact(
            'completions',
            'courses',
            'avgScore',
            'avgTimeSpent',
            'totalCompletions',
            'todayCompletions'
        ));
    }

    /**
     * Export report data as CSV.
     */
    public function exportCsv(Request $request, $type)
    {
        $filename = "report_{$type}_" . date('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = match ($type) {
            'enrollments' => $this->exportEnrollmentsCsv(),
            'grades' => $this->exportGradesCsv(),
            'completions' => $this->exportCompletionsCsv(),
            default => abort(404),
        };

        return Response::stream($callback, 200, $headers);
    }

    private function exportEnrollmentsCsv(): \Closure
    {
        return function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, ['Học viên', 'Email', 'Khóa học', 'Trạng thái', 'Ngày ghi danh', 'Tiến độ (%)', 'Điểm cuối']);

            Enrollment::with(['user', 'course'])->chunk(200, function ($enrollments) use ($handle) {
                foreach ($enrollments as $e) {
                    fputcsv($handle, [
                        $e->user->name ?? '',
                        $e->user->email ?? '',
                        $e->course->title ?? '',
                        $e->status,
                        $e->enrolled_at?->format('Y-m-d H:i'),
                        $e->progress_percentage,
                        $e->final_grade ?? '',
                    ]);
                }
            });

            fclose($handle);
        };
    }

    private function exportGradesCsv(): \Closure
    {
        return function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Học viên', 'Email', 'Khóa học', 'Điểm số', 'Xếp loại', 'Trạng thái']);

            Enrollment::with(['user', 'course'])
                ->whereIn('status', ['active', 'completed'])
                ->chunk(200, function ($enrollments) use ($handle) {
                    foreach ($enrollments as $e) {
                        $grade = $this->gradebookService->getCourseGrade($e->user, $e->course);
                        fputcsv($handle, [
                            $e->user->name ?? '',
                            $e->user->email ?? '',
                            $e->course->title ?? '',
                            $grade,
                            $this->getLetterGrade($grade),
                            $e->status,
                        ]);
                    }
                });

            fclose($handle);
        };
    }

    private function exportCompletionsCsv(): \Closure
    {
        return function () {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Học viên', 'Hoạt động', 'Bài học', 'Điểm', 'Điểm tối đa', 'Thời gian (giây)', 'Hoàn thành lúc']);

            ActivityCompletion::with(['user', 'activity', 'lesson'])
                ->chunk(200, function ($completions) use ($handle) {
                    foreach ($completions as $c) {
                        fputcsv($handle, [
                            $c->user->name ?? '',
                            $c->activity->title ?? '',
                            $c->lesson->title ?? '',
                            $c->score,
                            $c->max_score,
                            $c->time_spent_seconds,
                            $c->completed_at?->format('Y-m-d H:i'),
                        ]);
                    }
                });

            fclose($handle);
        };
    }

    private function getLetterGrade(float $score): string
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}
