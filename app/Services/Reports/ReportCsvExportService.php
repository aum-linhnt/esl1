<?php

namespace App\Services\Reports;

use App\Models\ActivityCompletion;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\LMS\GradebookService;
use Illuminate\Http\Request;

class ReportCsvExportService
{
    public function __construct(
        protected GradeMatrixService $gradeMatrixService
    ) {}

    /**
     * Generate CSV callback for Enrollments report.
     */
    public function exportEnrollmentsCsv(Request $request): \Closure
    {
        return function () use ($request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($handle, ['Học viên', 'Email', 'Khóa học', 'Trạng thái', 'Ngày ghi danh', 'Tiến độ (%)', 'Điểm cuối']);

            $query = Enrollment::with(['user', 'course']);
            if ($request->filled('course_id')) {
                $query->where('course_id', $request->course_id);
            }

            $query->chunk(200, function ($enrollments) use ($handle) {
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

    /**
     * Generate CSV callback for overall Course Grades report.
     */
    public function exportGradesCsv(Request $request, GradebookService $gradebookService): \Closure
    {
        return function () use ($request, $gradebookService) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Học viên', 'Email', 'Khóa học', 'Điểm số', 'Xếp loại', 'Trạng thái']);

            $query = Enrollment::with(['user', 'course'])
                ->whereIn('status', ['active', 'completed']);

            if ($request->filled('course_id')) {
                $query->where('course_id', $request->course_id);
            }

            $query->chunk(200, function ($enrollments) use ($handle, $gradebookService) {
                foreach ($enrollments as $e) {
                    $grade = $gradebookService->getCourseGrade($e->user, $e->course);
                    fputcsv($handle, [
                        $e->user->name ?? '',
                        $e->user->email ?? '',
                        $e->course->title ?? '',
                        $grade,
                        $this->gradeMatrixService->getLetterGrade($grade),
                        $e->status,
                    ]);
                }
            });

            fclose($handle);
        };
    }

    /**
     * Generate CSV callback for Activity Grades (detailed log or matrix format).
     */
    public function exportActivityGradesCsv(Request $request): \Closure
    {
        return function () use ($request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Export Matrix format
            if ($request->get('format') === 'matrix' && $request->filled('course_id')) {
                $course = Course::with(['lessons.activities' => fn($q) => $q->orderBy('order')])->find($request->course_id);
                if ($course) {
                    $matrixData = $this->gradeMatrixService->buildCourseGradeMatrix($course);
                    $activities = $matrixData['course_activities'];
                    $gradeMatrix = $matrixData['grade_matrix'];

                    // Header row
                    $headers = ['Học viên', 'Email', 'Điểm TB', 'Xếp loại', 'Số HĐ hoàn thành'];
                    foreach ($activities as $act) {
                        $headers[] = $act->title . " (Sàn: " . ($act->passing_grade ?? 50) . "đ)";
                    }
                    fputcsv($handle, $headers);

                    // Student rows
                    foreach ($gradeMatrix as $row) {
                        $csvRow = [
                            $row['user']->name,
                            $row['user']->email,
                            $row['avg_score'] ?? '-',
                            $row['letter_grade'],
                            $row['completed_count'] . '/' . count($activities),
                        ];
                        foreach ($activities as $act) {
                            $scoreData = $row['activities_scores'][$act->id] ?? null;
                            $csvRow[] = ($scoreData && $scoreData['has_score']) ? $scoreData['score'] : '-';
                        }
                        fputcsv($handle, $csvRow);
                    }

                    fclose($handle);
                    return;
                }
            }

            fputcsv($handle, ['Học viên', 'Email', 'Khóa học', 'Bài học', 'Hoạt động', 'Loại hoạt động', 'Điểm', 'Điểm tối đa', 'Phần trăm (%)', 'Điểm đạt yêu cầu', 'Kết quả', 'Thời gian (giây)', 'Ngày làm bài']);

            $query = ActivityCompletion::with(['user', 'activity.lesson.course', 'lesson']);

            if ($request->filled('course_id')) {
                $query->whereHas('lesson', fn($q) => $q->where('course_id', $request->course_id));
            }
            if ($request->filled('lesson_id')) {
                $query->where('lesson_id', $request->lesson_id);
            }
            if ($request->filled('activity_type')) {
                $query->whereHas('activity', fn($q) => $q->where('type', $request->activity_type));
            }

            $query->chunk(200, function ($completions) use ($handle) {
                foreach ($completions as $c) {
                    $passGrade = $c->activity->passing_grade ?? 50;
                    $pct = $c->max_score > 0 ? round(($c->score / $c->max_score) * 100, 1) : 0;
                    $isPassed = $c->score >= $passGrade;

                    fputcsv($handle, [
                        $c->user->name ?? '',
                        $c->user->email ?? '',
                        $c->lesson->course->title ?? '',
                        $c->lesson->title ?? '',
                        $c->activity->title ?? '',
                        $c->activity->type ?? '',
                        $c->score,
                        $c->max_score,
                        $pct . '%',
                        $passGrade,
                        $isPassed ? 'Đạt' : 'Chưa đạt',
                        $c->time_spent_seconds,
                        $c->completed_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        };
    }

    /**
     * Generate CSV callback for Completion report.
     */
    public function exportCompletionsCsv(Request $request): \Closure
    {
        return function () use ($request) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['Khóa học', 'Học viên', 'Email', 'Bài học', 'Hoạt động', 'Loại', 'Trạng thái', 'Điểm số', 'Thời gian (giây)', 'Ngày hoàn thành']);

            $query = ActivityCompletion::with(['user', 'activity.lesson.course', 'lesson']);

            if ($request->filled('course_id')) {
                $query->whereHas('lesson', fn($q) => $q->where('course_id', $request->course_id));
            }
            if ($request->filled('lesson_id')) {
                $query->where('lesson_id', $request->lesson_id);
            }
            if ($request->filled('activity_type')) {
                $query->whereHas('activity', fn($q) => $q->where('type', $request->activity_type));
            }

            $query->chunk(200, function ($completions) use ($handle) {
                foreach ($completions as $c) {
                    fputcsv($handle, [
                        $c->lesson->course->title ?? '',
                        $c->user->name ?? '',
                        $c->user->email ?? '',
                        $c->lesson->title ?? '',
                        $c->activity->title ?? '',
                        $c->activity->type ?? '',
                        $c->completed_at ? 'Đã hoàn thành' : 'Chưa hoàn thành',
                        $c->score,
                        $c->time_spent_seconds,
                        $c->completed_at?->format('Y-m-d H:i'),
                    ]);
                }
            });

            fclose($handle);
        };
    }
}
