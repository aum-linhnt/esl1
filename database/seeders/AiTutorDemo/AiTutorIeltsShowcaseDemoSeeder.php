<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\{Activity, AssignmentSubmission, Course, User};
use Illuminate\Support\Facades\DB;

class AiTutorIeltsShowcaseDemoSeeder extends AiTutorIeltsDemoSeeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeder chỉ chạy ở môi trường local hoặc testing.');
        }
        DB::transaction(function () {
            if (! Course::where('slug', self::SLUG)->exists()) parent::run();
            $course = Course::where('slug', self::SLUG)->firstOrFail();
            $teacher = User::where('email', 'ai-tutor-demo-ielts-teacher@example.test')->firstOrFail();
            if ((string) $course->created_by !== (string) $teacher->id || ! str_contains($course->description ?? '', 'AI_TUTOR_IELTS_DEMO_V1')) {
                throw new \RuntimeException('Khóa IELTS mẫu không đúng định danh demo; không ghi đè.');
            }
            $lessons = $course->lessons()->orderBy('order')->get();
            if ($lessons->count() !== 5) throw new \RuntimeException('Khóa IELTS mẫu phải có đúng 5 bài học trước khi chuẩn hóa.');
            $units = require __DIR__.'/data/ielts-65-showcase.php';
            foreach ($lessons as $i => $lesson) {
                $unit = $units[$i];
                $quiz = $lesson->activities()->where('order', 1)->where('type', 'quiz')->firstOrFail();
                $assignment = $lesson->activities()->where('order', 2)->where('type', 'assignment')->firstOrFail();
                $lesson->update(['estimated_minutes' => 30, 'summary' => $unit['objective'], 'description' => $unit['objective']]);
                $material = Activity::firstOrCreate(['lesson_id' => $lesson->id, 'order' => 0], [
                    'title' => 'Tài liệu: '.$lesson->title, 'type' => 'text_page', 'is_visible' => true,
                    'estimated_minutes' => 10, 'completion_type' => 'manual',
                    'content' => ['demo_showcase' => 'IELTS_65_V1', 'body' => $unit['body']],
                ]);
                if ($material->type !== 'text_page' || ($material->content['demo_showcase'] ?? null) !== 'IELTS_65_V1') {
                    throw new \RuntimeException('Hoạt động tài liệu đã tồn tại với nội dung khác; không ghi đè.');
                }
                $quiz->update(['estimated_minutes' => 5, 'content' => [
                    'demo_showcase' => 'IELTS_65_V1', 'source_mode' => 'inline',
                    'questions' => array_map(fn ($q) => ['question' => $q[0], 'options' => $q[1], 'answer' => $q[2], 'explanation' => $q[3]], $unit['quizzes']),
                ]]);
                $assignment->update(['estimated_minutes' => 15, 'content' => [
                    'demo_showcase' => 'IELTS_65_V1', 'instructions' => $unit['assignment'],
                ]]);
                // Replace only untouched synthetic placeholders, never a customer's test submission.
                $samples = AssignmentSubmission::where('activity_id', $assignment->id)->where('attempt_number', 1)->with('user')->get();
                foreach ($samples as $sample) {
                    if (! $sample->user || ! str_starts_with($sample->user->email, 'ai-tutor-demo-ielts-student-')) continue;
                    $name = str_replace(' · Demo', '', $sample->user->name);
                    if ($sample->text_content !== '[DEMO] Bài luyện IELTS mẫu của '.$name.'.') continue;
                    $sample->update(['text_content' => $unit['sample'], 'feedback' => $sample->status === 'graded' ? '[DEMO] '.$unit['feedback'] : null]);
                }
            }
            // Open only the selected showcase course; enrollment still requires staff assignment.
            $course->update(['is_published' => true, 'allow_self_enrollment' => false,
                'target_audience' => 'Demo IELTS Academic 6.5: luyện 4 kỹ năng, bài luyện rút gọn và dữ liệu thống kê mô phỏng.']);
            $this->command?->info('Khóa IELTS mẫu #'.$course->id.': '.url('/courses/'.$course->id));
            $this->command?->info('Quản lý Gia sư AI: '.route('teacher.ai-tutor.index', ['course_id' => $course->id]));
        });
    }
}
