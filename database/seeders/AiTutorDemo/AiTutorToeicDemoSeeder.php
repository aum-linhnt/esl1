<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\User;
use Ramsey\Uuid\Uuid;

class AiTutorToeicDemoSeeder extends AiTutorManagementDemoSeeder
{
    public const SLUG = 'demo-ai-tutor-toeic';

    protected function user(string $key, string $name, string $role): User
    {
        return parent::user('toeic-'.$key, $name, $role);
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-toeic-demo:v1:'.$key)->toString();
    }

    protected function requestPrefix(): string
    {
        return 'demo:toeic';
    }

    protected function definition(): array
    {
        return [
            'title' => '[DEMO] TOEIC 750+ — Listening & Reading', 'marker' => 'AI_TUTOR_TOEIC_DEMO_V1', 'level' => 'B1',
            'lessons' => ['TOEIC Listening — Hội thoại công sở', 'TOEIC Listening — Thông báo và lịch hẹn',
                'TOEIC Reading — Ngữ pháp và từ vựng công việc', 'TOEIC Reading — Email và thông báo'],
            'quiz_questions' => [
                ['question' => 'Transcript: “Could you send me the report before lunch?” What does the speaker request?', 'options' => ['Book a hotel', 'Send a report', 'Cancel a meeting', 'Order lunch'], 'answer' => 1],
                ['question' => 'Transcript: “The meeting has moved from Tuesday to Thursday.” When is the meeting now?', 'options' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday'], 'answer' => 3],
                ['question' => 'Please ___ the attached document before the meeting.', 'options' => ['review', 'reviews', 'reviewing', 'reviewed'], 'answer' => 0],
                ['question' => 'Email: “Your order will arrive on Friday. Please contact us if your address has changed.” What should the customer do if they move?', 'options' => ['Cancel the order', 'Wait until Monday', 'Contact the sender', 'Order another item'], 'answer' => 2],
            ],
            'assignments' => [
                '[DEMO Listening] Đọc transcript hội thoại mẫu, xác định người nói, yêu cầu chính và các từ khóa.',
                '[DEMO Listening] Từ transcript thông báo mẫu, ghi thời gian, địa điểm và thay đổi lịch hẹn.',
                '[DEMO Reading] Hoàn thành năm câu ngữ pháp về công việc và giải thích lựa chọn.',
                '[DEMO Reading] Đọc email giao hàng mẫu, xác định người nhận, ngày giao và hành động cần làm.',
            ],
            'submission_prefix' => '[DEMO] Bài luyện TOEIC Listening & Reading của ',
            'questions' => ['Làm sao xác định ý chính trong hội thoại TOEIC Listening?', 'Cách ghi nhớ thời gian và địa điểm trong thông báo?',
                'Làm sao chọn đúng dạng từ trong câu TOEIC Reading?', 'Cách tìm thông tin nhanh trong email công việc?', 'Cách ôn từ vựng công sở cho TOEIC Listening và Reading?'],
            'reply' => '[DEMO TOEIC] Hãy xác định tình huống, gạch chân từ khóa và đối chiếu từng lựa chọn với transcript hoặc đoạn đọc. Thử giải thích vì sao bạn chọn đáp án đó.',
        ];
    }
}
