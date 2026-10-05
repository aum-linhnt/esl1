<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Activity;
use App\Models\Enrollment;
use App\Models\ActivityCompletion;
use App\Models\UserProgress;
use App\Services\LMS\EnrollmentService;
use App\Services\LMS\GradebookService;
use App\Services\Reports\CompletionReportService;
use App\Services\Reports\GradeMatrixService;
use App\Services\Reports\ReportCsvExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct(
        private EnrollmentService $enrollmentService,
        private GradebookService $gradebookService,
        private GradeMatrixService $gradeMatrixService,
        private CompletionReportService $completionReportService,
        private ReportCsvExportService $csvExportService
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
        $totalActivitiesCompleted = ActivityCompletion::whereNotNull('completed_at')->count();
        $avgCompletionTime = (int) (ActivityCompletion::avg('time_spent_seconds') ?? 0);
        $avgActivityScore = (float) (ActivityCompletion::avg('score') ?? 0);

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
            'avgActivityScore',
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
     * Báo cáo điểm số (Từng hoạt động & Tổng kết khóa học).
     */
    public function grades(Request $request)
    {
        $tab = $request->get('tab', 'activities');

        if ($tab === 'course') {
            return $this->courseGrades($request);
        }

        return $this->activityGrades($request);
    }

    /**
     * Báo cáo điểm số từng hoạt động (Activity Grades Report & Grade Matrix).
     */
    public function activityGrades(Request $request)
    {
        $courses = Course::withCount(['enrollments' => function ($q) {
            $q->whereIn('status', ['active', 'completed']);
        }])->orderBy('title')->get();

        // ─── Course Selection for Grade Matrix ───
        $selectedCourseId = $request->input('course_id');
        if (!$selectedCourseId && $courses->isNotEmpty()) {
            $selectedCourseId = $courses->first()->id;
        }

        $selectedCourse = $selectedCourseId
            ? Course::with([
                'lessons' => fn($q) => $q->orderBy('order'),
                'lessons.activities' => fn($q) => $q->orderBy('order')
            ])->find($selectedCourseId)
            : null;

        $gradeMatrix = collect();
        $activityColumnSummaries = [];
        $courseActivities = collect();
        $totalEnrolled = 0;
        $classOverallAvgScore = 0;

        if ($selectedCourse) {
            $matrixResult = $this->gradeMatrixService->buildCourseGradeMatrix(
                $selectedCourse,
                $request->input('matrix_search')
            );
            $gradeMatrix = $matrixResult['grade_matrix'];
            $activityColumnSummaries = $matrixResult['activity_column_summaries'];
            $courseActivities = $matrixResult['course_activities'];
            $totalEnrolled = $matrixResult['total_enrolled'];
            $classOverallAvgScore = $matrixResult['class_avg_score'];
        }

        // ─── Base query for Detailed Activity Completions Log ───
        $query = ActivityCompletion::with(['user', 'activity.lesson.course', 'lesson']);

        if ($request->filled('course_id')) {
            $query->whereHas('lesson', function ($q) use ($request) {
                $q->where('course_id', $request->course_id);
            });
        }

        if ($request->filled('lesson_id')) {
            $query->where('lesson_id', $request->lesson_id);
        }

        if ($request->filled('activity_id')) {
            $query->where('activity_id', $request->activity_id);
        }

        if ($request->filled('activity_type')) {
            $query->whereHas('activity', function ($q) use ($request) {
                $q->where('type', $request->activity_type);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('activity', function ($aq) use ($search) {
                    $aq->where('title', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('result')) {
            if ($request->result === 'passed') {
                $query->whereRaw('score >= COALESCE((SELECT passing_grade FROM activities WHERE activities.id = activity_completions.activity_id), 50)');
            } elseif ($request->result === 'failed') {
                $query->whereRaw('score < COALESCE((SELECT passing_grade FROM activities WHERE activities.id = activity_completions.activity_id), 50)');
            } elseif ($request->result === 'excellent') {
                $query->where('score', '>=', 90);
            } elseif ($request->result === 'good') {
                $query->whereBetween('score', [80, 89]);
            } elseif ($request->result === 'average') {
                $query->whereBetween('score', [60, 79]);
            } elseif ($request->result === 'weak') {
                $query->where('score', '<', 60);
            }
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        match ($sort) {
            'score_desc' => $query->orderByDesc('score'),
            'score_asc'  => $query->orderBy('score'),
            'time_desc'  => $query->orderByDesc('time_spent_seconds'),
            'name_asc'   => $query->join('users', 'activity_completions.user_id', '=', 'users.id')
                                  ->orderBy('users.name')
                                  ->select('activity_completions.*'),
            default      => $query->latest('completed_at')->latest('id'),
        };

        // Summary stats on filtered query
        $statsQuery = clone $query;
        $totalGraded = $statsQuery->count();
        $avgScore = $totalGraded > 0 ? round($statsQuery->avg('score'), 1) : 0;
        $maxScore = $totalGraded > 0 ? $statsQuery->max('score') : 0;
        $minScore = $totalGraded > 0 ? $statsQuery->min('score') : 0;
        $avgTime = $totalGraded > 0 ? (int) $statsQuery->avg('time_spent_seconds') : 0;

        // Pass count
        $passCountQuery = clone $query;
        $passCount = $passCountQuery->whereRaw('score >= COALESCE((SELECT passing_grade FROM activities WHERE activities.id = activity_completions.activity_id), 50)')->count();
        $passRate = $totalGraded > 0 ? round(($passCount / $totalGraded) * 100, 1) : 0;

        // Grade distribution for activities
        $distQuery = clone $query;
        $allScores = $distQuery->pluck('score');
        $distribution = [
            'A' => $allScores->filter(fn($s) => $s >= 90)->count(),
            'B' => $allScores->filter(fn($s) => $s >= 80 && $s < 90)->count(),
            'C' => $allScores->filter(fn($s) => $s >= 70 && $s < 80)->count(),
            'D' => $allScores->filter(fn($s) => $s >= 60 && $s < 70)->count(),
            'F' => $allScores->filter(fn($s) => $s < 60)->count(),
        ];

        // Activity summaries (aggregated by activity)
        $summaryQuery = ActivityCompletion::with('activity.lesson.course')
            ->selectRaw('activity_id, COUNT(*) as attempts_count, AVG(score) as avg_score, MAX(score) as highest_score, MIN(score) as lowest_score, AVG(time_spent_seconds) as avg_time')
            ->groupBy('activity_id')
            ->orderByDesc('attempts_count');

        if ($request->filled('course_id')) {
            $summaryQuery->whereHas('lesson', fn($lq) => $lq->where('course_id', $request->course_id));
        }
        if ($request->filled('activity_type')) {
            $summaryQuery->whereHas('activity', fn($aq) => $aq->where('type', $request->activity_type));
        }

        $activitySummaries = $summaryQuery->take(15)->get();

        // Paginated records
        $activityGrades = $query->paginate(20)->withQueryString();

        // Available lessons if course is selected
        $availableLessons = $selectedCourseId
            ? Lesson::where('course_id', $selectedCourseId)->orderBy('order')->get()
            : collect();

        // Available activities if lesson is selected
        $availableActivities = $request->filled('lesson_id')
            ? Activity::where('lesson_id', $request->lesson_id)->orderBy('order')->get()
            : collect();

        $activityTypes = Activity::$typeRegistry;
        $activeTab = $request->get('tab', 'matrix');

        return view('admin.reports.activity_grades', compact(
            'courses',
            'selectedCourseId',
            'selectedCourse',
            'gradeMatrix',
            'activityColumnSummaries',
            'courseActivities',
            'totalEnrolled',
            'classOverallAvgScore',
            'availableLessons',
            'availableActivities',
            'activityTypes',
            'activityGrades',
            'totalGraded',
            'avgScore',
            'maxScore',
            'minScore',
            'avgTime',
            'passCount',
            'passRate',
            'distribution',
            'activitySummaries',
            'activeTab'
        ));
    }

    /**
     * Báo cáo điểm tổng kết theo Khóa học (Course GPA).
     */
    protected function courseGrades(Request $request)
    {
        $query = Enrollment::with(['user', 'course'])
            ->whereIn('status', ['active', 'completed']);

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

        $enrollments = $query->latest()->paginate(20);

        // Calculate grades for each enrollment
        $gradesData = $enrollments->getCollection()->map(function ($enrollment) {
            $grade = $this->gradebookService->getCourseGrade($enrollment->user, $enrollment->course);
            return [
                'enrollment' => $enrollment,
                'grade' => $grade,
                'letter' => $this->gradeMatrixService->getLetterGrade($grade),
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
     * Báo cáo hoàn thành từng hoạt động theo từng khóa học (Activity Completion Report by Course).
     */
    public function completions(Request $request)
    {
        $courses = Course::withCount(['enrollments' => function ($q) {
            $q->whereIn('status', ['active', 'completed']);
        }])->orderBy('title')->get();

        $selectedCourseId = $request->input('course_id');
        if (!$selectedCourseId && $courses->isNotEmpty()) {
            $selectedCourseId = $courses->first()->id;
        }

        $selectedCourse = $selectedCourseId
            ? Course::with([
                'lessons' => fn($q) => $q->orderBy('order'),
                'lessons.activities' => fn($q) => $q->orderBy('order')
            ])->find($selectedCourseId)
            : null;

        $totalEnrolled = 0;
        $totalActivities = 0;
        $overallCourseCompletionRate = 0;
        $fullyCompletedStudents = 0;
        $bottleneckActivity = null;
        $topActivity = null;
        $activityStats = collect();
        $learnerMatrix = collect();
        $detailedCompletions = collect();
        $availableLessons = collect();

        if ($selectedCourse) {
            $availableLessons = $selectedCourse->lessons;

            $completionData = $this->completionReportService->buildCourseCompletionStats(
                $selectedCourse,
                $request->input('matrix_search')
            );

            $totalEnrolled = $completionData['total_enrolled'];
            $totalActivities = $completionData['total_activities'];
            $overallCourseCompletionRate = $completionData['overall_completion_rate'];
            $fullyCompletedStudents = $completionData['fully_completed_students'];
            $topActivity = $completionData['top_activity'];
            $bottleneckActivity = $completionData['bottleneck_activity'];
            $activityStats = $completionData['activity_stats'];
            $learnerMatrix = $completionData['learner_matrix'];

            // Detailed log query with pagination
            $courseActivities = $selectedCourse->lessons->flatMap->activities;
            $detailQuery = ActivityCompletion::with(['user', 'activity.lesson', 'lesson'])
                ->whereIn('activity_id', $courseActivities->pluck('id'));

            if ($request->filled('lesson_id')) {
                $detailQuery->where('lesson_id', $request->lesson_id);
            }

            if ($request->filled('activity_type')) {
                $detailQuery->whereHas('activity', fn($q) => $q->where('type', $request->activity_type));
            }

            if ($request->filled('status')) {
                if ($request->status === 'completed') {
                    $detailQuery->whereNotNull('completed_at');
                } elseif ($request->status === 'incomplete') {
                    $detailQuery->whereNull('completed_at');
                }
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $detailQuery->where(function ($q) use ($s) {
                    $q->whereHas('user', fn($uq) => $uq->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
                      ->orWhereHas('activity', fn($aq) => $aq->where('title', 'like', "%{$s}%"));
                });
            }

            $detailedCompletions = $detailQuery->latest('completed_at')->paginate(20)->withQueryString();
        }

        $activeTab = $request->get('tab', 'overview');
        $activityTypes = Activity::$typeRegistry;

        return view('admin.reports.completions', compact(
            'courses',
            'selectedCourseId',
            'selectedCourse',
            'totalEnrolled',
            'totalActivities',
            'overallCourseCompletionRate',
            'fullyCompletedStudents',
            'bottleneckActivity',
            'topActivity',
            'activityStats',
            'learnerMatrix',
            'detailedCompletions',
            'availableLessons',
            'activeTab',
            'activityTypes'
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
            'enrollments' => $this->csvExportService->exportEnrollmentsCsv($request),
            'grades' => $this->csvExportService->exportGradesCsv($request, $this->gradebookService),
            'activity-grades', 'activity_grades' => $this->csvExportService->exportActivityGradesCsv($request),
            'completions', 'course-completions' => $this->csvExportService->exportCompletionsCsv($request),
            default => abort(404),
        };

        return Response::stream($callback, 200, $headers);
    }
}
