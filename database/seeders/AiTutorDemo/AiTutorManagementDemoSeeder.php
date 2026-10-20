<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\{Activity, ActivityCompletion, AssignmentSubmission, Course, Enrollment, Lesson, QuizAttempt, User, UserProgress};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

class AiTutorManagementDemoSeeder extends Seeder
{
    public const SLUG = 'demo-ai-tutor-management';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeder chỉ chạy ở môi trường local hoặc testing.');
        }
        foreach (['tutor_ai_requests', 'tutor_ai_usage_records', 'tutor_ai_conversations', 'tutor_ai_conversation_messages', 'tutor_ai_message_feedback'] as $table) {
            if (! Schema::hasTable($table)) throw new \RuntimeException('Thiếu bảng '.$table.'. Hãy chạy migration AI Tutor trước.');
        }
        DB::transaction(function () {
            $demo = $this->definition();
            $teacher = $this->user('teacher', 'Nguyễn Minh Anh · Demo', 'teacher');
            $course = Course::firstOrCreate(['slug' => static::SLUG], [
                'title' => $demo['title'], 'description' => $demo['marker'].' — Dữ liệu mô phỏng để xem giao diện, không phải kết quả học thật.',
                'created_by' => $teacher->id, 'level' => $demo['level'], 'passing_grade' => 50, 'grading_scale' => 'scale_100',
                'is_published' => false, 'allow_self_enrollment' => false,
            ]);
            if ((string) $course->created_by !== (string) $teacher->id || ! str_contains($course->description ?? '', $demo['marker'])) {
                throw new \RuntimeException('Slug demo đã được dùng bởi khóa học khác; seeder không ghi đè.');
            }
            // Existing teachers can inspect the demo without changing their profile, password or real course access.
            foreach (User::where('role', 'teacher')->where('status', 'active')->get() as $viewer) {
                Enrollment::firstOrCreate(['course_id' => $course->id, 'user_id' => $viewer->id], [
                    'course_role' => 'manager', 'status' => 'active', 'enrolled_at' => now(),
                ]);
            }
            $titles = $demo['lessons'];
            $lessons = []; $quizzes = []; $assignments = [];
            foreach ($titles as $i => $title) {
                $lesson = Lesson::firstOrCreate(['course_id' => $course->id, 'order' => $i + 1], [
                    'title' => $title, 'is_visible' => true, 'estimated_minutes' => 15, 'unlock_condition_score' => 0,
                    'ai_answer_policy' => 'teacher_controlled', 'ai_teacher_solution_allowed' => $i !== 1, 'ai_exam_mode' => $i === 1,
                ]);
                $lessons[] = $lesson;
                $quizzes[] = Activity::firstOrCreate(['lesson_id' => $lesson->id, 'order' => 1], [
                    'title' => 'Quiz: '.$title, 'type' => 'quiz', 'is_visible' => true, 'completion_type' => 'auto_grade', 'passing_grade' => 50,
                    'content' => ['questions' => [$demo['quiz_questions'][$i]]],
                ]);
                $assignments[] = Activity::firstOrCreate(['lesson_id' => $lesson->id, 'order' => 2], [
                    'title' => 'Bài tập: '.$title, 'type' => 'assignment', 'is_visible' => true, 'completion_type' => 'auto_submit',
                    'content' => ['instructions' => $demo['assignments'][$i]],
                ]);
            }
            $names = ['Trần Hoàng Nam', 'Lê Thị Mai', 'Phạm Minh Quân', 'Nguyễn Thảo Vy', 'Hoàng Đức Anh', 'Đỗ Gia Hân', 'Bùi Minh Khang', 'Vũ Ngọc Lan'];
            $grades = [45, 52, 68, 70, 73, 88, null, 91]; $students = [];
            foreach ($names as $i => $name) {
                $student = $this->user('student-'.($i + 1), $name.' · Demo', 'student'); $students[] = $student;
                Enrollment::updateOrCreate(['course_id' => $course->id, 'user_id' => $student->id], [
                    'course_role' => 'student', 'status' => 'active', 'enrolled_at' => now()->subDays(65), 'final_grade' => $grades[$i],
                    'progress_percentage' => $i === 6 ? 0 : 40 + $i * 7,
                ]);
                foreach ($lessons as $j => $lesson) {
                    $score = $i === 6 ? null : min(100, max(0, (int) $grades[$i] + ($j - 2) * 5));
                    $at = now()->subDays(count($lessons) - $j)->startOfDay()->addHours(9);
                    UserProgress::updateOrCreate(['user_id' => $student->id, 'lesson_id' => $lesson->id], [
                        'completed' => $score !== null && $j < intdiv($i + 2, 2), 'score' => $score ?? 0,
                        'completed_at' => $score !== null && $j < intdiv($i + 2, 2) ? $at : null,
                    ]);
                    if ($score !== null) {
                        QuizAttempt::updateOrCreate(['user_id' => $student->id, 'activity_id' => $quizzes[$j]->id, 'attempt_number' => 1], [
                            'status' => 'completed', 'score' => $score, 'max_score' => 100, 'percentage' => $score, 'is_passed' => $score >= 50,
                            'started_at' => $at->copy()->subMinutes(8), 'completed_at' => $at, 'time_spent_seconds' => 480,
                        ]);
                        if ($score >= 50) ActivityCompletion::updateOrCreate(['user_id' => $student->id, 'activity_id' => $quizzes[$j]->id], [
                            'lesson_id' => $lesson->id, 'score' => $score, 'max_score' => 100, 'completed_at' => $at,
                        ]);
                    }
                    $pending = $i < 3 && $j === 0;
                    AssignmentSubmission::updateOrCreate(['user_id' => $student->id, 'activity_id' => $assignments[$j]->id, 'attempt_number' => 1], [
                        'status' => $pending || $score === null ? 'submitted' : 'graded', 'grade' => $pending || $score === null ? null : $score,
                        'submitted_at' => $at, 'graded_at' => $pending || $score === null ? null : $at->copy()->addHour(),
                        'graded_by' => $pending || $score === null ? null : $teacher->id,
                        'text_content' => $demo['submission_prefix'].$name.'.',
                    ]);
                }
            }
            $this->conversations($course, $lessons, $students);
            $this->command?->info('Đã seed khóa học DEMO #'.$course->id.': '.route('teacher.ai-tutor.index', ['course_id' => $course->id]));
        });
    }

    protected function requestPrefix(): string
    {
        return 'demo';
    }

    protected function definition(): array
    {
        return [
            'title' => '[DEMO] Kỹ năng quản lý thời gian', 'marker' => 'AI_TUTOR_MANAGEMENT_DEMO_V1', 'level' => 'B1',
            'lessons' => ['Lập kế hoạch cá nhân', 'Ma trận Eisenhower', 'Duy trì thói quen', 'Quản lý xao nhãng', 'Ưu tiên công việc'],
            'quiz_questions' => array_fill(0, 5, ['question' => 'Bước đầu tiên khi lập kế hoạch là gì?', 'options' => ['Xác định mục tiêu', 'Làm mọi việc ngay', 'Bỏ qua ưu tiên', 'Chờ đến hạn'], 'answer' => 0]),
            'assignments' => array_fill(0, 5, 'Viết kế hoạch học tập một tuần. Đây là hoạt động minh họa của khóa học DEMO.'),
            'submission_prefix' => '[DEMO] Kế hoạch học tập mẫu của ',
            'questions' => ['Làm thế nào để lập kế hoạch hiệu quả?', 'Cách xử lý xao nhãng khi học?', 'Ma trận Eisenhower là gì?', 'Làm sao để duy trì thói quen?', 'Phân biệt việc quan trọng và khẩn cấp?'],
            'reply' => '[DEMO] Hãy chia mục tiêu thành các bước nhỏ, xác định ưu tiên và dành thời gian cố định để thực hiện.',
        ];
    }

    protected function user(string $key, string $name, string $role): User
    {
        $email = 'ai-tutor-demo-'.$key.'@example.test';
        $user = User::firstOrCreate(['email' => $email], [
            'username' => 'ai_tutor_demo_'.str_replace('-', '_', $key), 'name' => $name,
            'role' => $role, 'status' => 'active', 'password' => Str::password(32),
            'email_verified_at' => now(), 'current_level' => 'B1',
        ]);
        if ($user->username !== 'ai_tutor_demo_'.str_replace('-', '_', $key) || $user->role !== $role) {
            throw new \RuntimeException('Định danh demo trùng tài khoản khác; seeder không ghi đè.');
        }
        return $user;
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-management-demo:v1:'.$key)->toString();
    }

    private function conversations(Course $course, array $lessons, array $students): void
    {
        $conversations = []; $messages = []; $requests = []; $usage = []; $feedback = [];
        $demo = $this->definition();
        $questions = $demo['questions'];
        $errors = ['AI_CREDIT_INSUFFICIENT', 'AI_PROVIDER_UNAVAILABLE', 'AI_SOURCE_NOT_FOUND', 'AI_DAILY_LIMIT_REACHED'];
        for ($day = 0; $day < 60; $day++) {
            $count = $day < 30 ? 24 + ($day * 7 % 29) : 15 + ($day * 3 % 15);
            for ($turn = 0; $turn < $count; $turn++) {
                $key = $day.'-'.$turn; $student = $students[($day + $turn) % count($students)]; $lesson = $lessons[$turn % count($lessons)];
                $at = now()->subDays($day)->startOfDay()->addMinutes(5 + $turn * 3);
                if ($at->isFuture()) $at = now()->subSeconds($turn + 1);
                $conv = $this->uuid('conversation-'.$key); $message = $this->uuid('message-'.$key); $request = $this->uuid('request-'.$key);
                $failed = $day < 12 && $turn === 0;
                $error = $failed ? $errors[$day % count($errors)] : null;
                $conversations[] = ['id' => $conv, 'user_id' => (string) $student->id, 'course_id' => (string) $course->id, 'lesson_id' => (string) $lesson->id,
                    'teaching_mode' => 'hints_first', 'created_at' => $at, 'updated_at' => $at];
                $messages[] = ['id' => $message, 'conversation_id' => $conv, 'idempotency_key' => $this->requestPrefix().':tutor:'.$key, 'fingerprint' => hash('sha256', 'demo:'.$key),
                    'request_id' => $request, 'embedding_request_id' => $this->uuid('embedding-'.$key), 'user_content' => $questions[($turn + $day) % count($questions)],
                    'content' => $failed ? null : $demo['reply'],
                    'status' => $failed ? 'failed' : 'completed', 'error_code' => $error,
                    'metadata' => json_encode(['demo' => true, 'provider' => 'demo', 'model' => 'demo-tutor-v1']), 'created_at' => $at, 'updated_at' => $at];
                $requests[] = ['request_id' => $request, 'idempotency_key' => $this->requestPrefix().':request:'.$key, 'fingerprint' => hash('sha256', 'demo:'.$key),
                    'user_id' => (string) $student->id, 'feature' => 'tutor_message', 'billing_mode' => 'customer_key', 'provider' => 'demo', 'model' => 'demo-tutor-v1',
                    'status' => $failed ? 'failed' : 'completed', 'error_code' => $error, 'started_at' => $at,
                    'completed_at' => $failed ? null : $at, 'failed_at' => $failed ? $at : null, 'created_at' => $at, 'updated_at' => $at];
                if (! $failed) {
                    $usage[] = ['request_id' => $request, 'user_id' => (string) $student->id, 'feature' => 'tutor_message', 'billing_mode' => 'customer_key',
                        'provider' => 'demo', 'model' => 'demo-tutor-v1', 'input_tokens' => 320 + $turn * 8, 'output_tokens' => 120 + $turn * 4,
                        'cached_tokens' => 0, 'credit_units' => 1, 'estimated_cost' => $turn % 6 === 0 ? null : ($turn % 5 === 0 ? (string) (650 + $day * 5) : '0.001250'),
                        'currency' => $turn % 5 === 0 ? 'VND' : 'USD', 'status' => 'completed', 'latency_ms' => 650 + $turn * 20,
                        'metadata' => json_encode(['demo' => true]), 'created_at' => $at, 'updated_at' => $at];
                    $feedback[] = ['message_id' => $message, 'user_id' => (string) $student->id,
                        'rating' => $turn % ($day < 30 ? 8 : 6) === 1 ? 'unhelpful' : 'helpful', 'created_at' => $at, 'updated_at' => $at];
                }
            }
        }
        $this->upsert('tutor_ai_requests', $requests, ['request_id']);
        $this->upsert('tutor_ai_conversations', $conversations, ['id']);
        $this->upsert('tutor_ai_conversation_messages', $messages, ['id']);
        $this->upsert('tutor_ai_usage_records', $usage, ['request_id']);
        $this->upsert('tutor_ai_message_feedback', $feedback, ['message_id', 'user_id']);
    }

    private function upsert(string $table, array $rows, array $identity): void
    {
        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table($table)->upsert($chunk, $identity, array_values(array_diff(array_keys($chunk[0]), $identity)));
        }
    }
}
