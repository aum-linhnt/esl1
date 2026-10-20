<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\TeacherTutorPolicyRequest;
use App\Models\Lesson;
use App\Services\Learning\TeacherTutorOverview;
use App\Services\Learning\TeacherLearnerSupport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class TutorDashboardController extends Controller
{
    public function index(Request $request, TeacherTutorOverview $overview, TeacherLearnerSupport $support)
    {
        $data = $request->validate(['course_id' => 'sometimes|integer|min:1', 'days' => ['sometimes', 'integer', Rule::in([7, 30, 90])],
            'error_code' => ['nullable', 'string', 'max:64', 'regex:/^AI_[A-Z0-9_]+$/'], 'error_lesson_id' => 'nullable|integer|min:1',
            'lesson_id' => 'sometimes|integer|min:1', 'section' => ['sometimes', Rule::in(['questions', 'errors', 'learners'])], 'learner_id' => 'sometimes|integer|min:1', 'export' => ['sometimes', Rule::in(['csv'])]]);
        $user = $request->user();
        abort_unless($user->isActive(), 403);
        $courses = $overview->courses($user);
        $course = isset($data['course_id']) ? $courses->firstWhere('id', $data['course_id']) : $courses->first();
        if (isset($data['course_id'])) abort_unless($course, 403);
        abort_unless($courses->isNotEmpty() || $user->isTeacher() || $user->isAdmin(), 403);
        $days = (int) ($data['days'] ?? 30);
        $report = $course ? $overview->report($course, $days) : null;
        if (isset($data['export'])) {
            abort_unless($report, 404);
            return response()->streamDownload(function () use ($report) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                $currencies = $report['costs']->pluck('currency')->all();
                fputcsv($out, ['Ngày (Việt Nam)', 'Lượt hỏi', ...array_map(fn ($currency) => 'Chi phí ước tính '.$currency.' (đã có giá)', $currencies), 'Lượt chưa có giá'], ',', '"', '');
                foreach ($report['daily'] as $day) {
                    $costs = array_map(fn ($currency) => isset($day['costs'][$currency]) ? number_format($day['costs'][$currency], 6, '.', '') : '', $currencies);
                    fputcsv($out, [$day['date'], $day['questions'], ...$costs, $day['unpriced']], ',', '"', '');
                }
                fclose($out);
            }, 'ai-tutor-course-'.$course->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
        }
        $policyLesson = $report ? (isset($data['lesson_id']) ? $report['lessons']->firstWhere('id', $data['lesson_id']) : $report['lessons']->first()) : null;
        if (isset($data['lesson_id'])) abort_unless($policyLesson, 404);
        $errorCode = $data['error_code'] ?? null;
        $errorLessonId = isset($data['error_lesson_id']) ? (int) $data['error_lesson_id'] : null;
        if ($errorLessonId !== null) abort_unless($report && $report['lessons']->contains('id', $errorLessonId), 404);
        $section = $data['section'] ?? null;
        $detail = $course && $section ? $overview->detail($course, $report, $section, isset($data['learner_id']) ? (string) $data['learner_id'] : null, $errorCode, $errorLessonId) : null;
        $learnerSupport = $section === 'learners' && isset($data['learner_id']) && $detail?->first()
            ? $support->profile($course, $detail->first(), $report) : null;
        return response()->view('teacher.tutor-dashboard', compact('user', 'courses', 'course', 'days', 'report', 'section', 'detail', 'policyLesson', 'errorCode', 'errorLessonId', 'learnerSupport'))->header('Cache-Control', 'no-store');
    }
    public function updatePolicy(TeacherTutorPolicyRequest $request, string $courseId, string $lessonId)
    {
        $lesson = Lesson::where('course_id', $courseId)->findOrFail($lessonId);
        $lesson->update($request->safe()->only(['ai_answer_policy', 'ai_teacher_solution_allowed', 'ai_exam_mode']));

        return redirect()->to(route('teacher.ai-tutor.index', [
            'course_id' => $courseId, 'lesson_id' => $lessonId, 'days' => (int) ($request->validated('days') ?? 30),
        ]).'#course-config')->with('tutor-policy-saved', 'Đã lưu chính sách Gia sư AI cho '.$lesson->title.'.');
    }
}
