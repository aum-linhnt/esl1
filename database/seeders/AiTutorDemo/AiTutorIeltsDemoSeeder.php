<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\User;
use Ramsey\Uuid\Uuid;

class AiTutorIeltsDemoSeeder extends AiTutorManagementDemoSeeder
{
    public const SLUG = 'demo-ai-tutor-ielts';

    protected function user(string $key, string $name, string $role): User
    {
        return parent::user('ielts-'.$key, $name, $role);
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-ielts-demo:v1:'.$key)->toString();
    }

    protected function requestPrefix(): string
    {
        return 'demo:ielts';
    }

    protected function definition(): array
    {
        return [
            'title' => '[DEMO] IELTS 6.5 — Luyện 4 kỹ năng', 'marker' => 'AI_TUTOR_IELTS_DEMO_V1', 'level' => 'B2',
            'lessons' => ['IELTS Listening — Ghi chú và từ khóa', 'IELTS Reading — Skimming và Scanning',
                'IELTS Writing Task 1 — Mô tả biểu đồ', 'IELTS Writing Task 2 — Opinion Essay', 'IELTS Speaking — Phát triển câu trả lời'],
            'quiz_questions' => [
                ['question' => 'You hear: “The class starts at half past nine.” What is the start time?', 'options' => ['09:00', '09:30', '10:30', '08:30'], 'answer' => 1],
                ['question' => 'Read: “Libraries offer free access to books and quiet study spaces.” What does the sentence describe?', 'options' => ['Library fees', 'Benefits of libraries', 'Book prices', 'Travel plans'], 'answer' => 1],
                ['question' => 'Which phrase describes an increase?', 'options' => ['fell sharply', 'remained stable', 'rose steadily', 'reached a low point'], 'answer' => 2],
                ['question' => 'Which sentence clearly states an opinion?', 'options' => ['There are many schools.', 'In my view, public transport should be improved.', 'The chart shows three cities.', 'The study was published yesterday.'], 'answer' => 1],
                ['question' => 'Which answer adds a reason?', 'options' => ['Yes.', 'I like reading because it helps me relax.', 'Reading.', 'Sometimes.'], 'answer' => 1],
            ],
            'assignments' => [
                '[DEMO] Viết đoạn hội thoại đặt lịch học và ghi lại thời gian, địa điểm, tên người đăng ký.',
                '[DEMO] Đọc một bài viết về giáo dục, ghi ý chính và năm từ khóa.',
                '[DEMO] Mô tả biểu đồ mẫu: số học viên của ba lớp A/B/C tăng từ 20/30/40 lên 35/45/50.',
                '[DEMO] Viết bài nêu quan điểm về việc học trực tuyến; đưa lý do và ví dụ.',
                '[DEMO] Soạn câu trả lời về một sở thích, gồm mô tả, lý do và ví dụ cá nhân.',
            ],
            'submission_prefix' => '[DEMO] Bài luyện IELTS mẫu của ',
            'questions' => ['Làm sao nhận diện từ khóa khi luyện IELTS Listening?', 'Skimming và scanning khác nhau thế nào?',
                'Cách viết overview cho Writing Task 1?', 'Làm sao phát triển ý cho Opinion Essay?', 'Cách mở rộng câu trả lời IELTS Speaking?'],
            'reply' => '[DEMO IELTS] Hãy xác định yêu cầu của bài, chọn từ khóa và thử một ví dụ ngắn. Giải thích lựa chọn của bạn trước khi xem gợi ý tiếp theo.',
        ];
    }
}
