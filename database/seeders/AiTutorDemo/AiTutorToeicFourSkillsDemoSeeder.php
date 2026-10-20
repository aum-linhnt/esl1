<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\User;
use Ramsey\Uuid\Uuid;

class AiTutorToeicFourSkillsDemoSeeder extends AiTutorManagementDemoSeeder
{
    public const SLUG = 'demo-ai-tutor-toeic-four-skills';

    protected function user(string $key, string $name, string $role): User
    {
        return parent::user('toeic-four-skills-'.$key, $name, $role);
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-toeic-four-skills-demo:v1:'.$key)->toString();
    }

    protected function requestPrefix(): string
    {
        return 'demo:toeic-four-skills';
    }

    protected function definition(): array
    {
        return [
            'title' => '[DEMO] TOEIC — Luyện 4 kỹ năng', 'marker' => 'AI_TUTOR_TOEIC_FOUR_SKILLS_DEMO_V1', 'level' => 'B1',
            'lessons' => ['TOEIC Listening — Hội thoại và thông báo', 'TOEIC Reading — Email và tài liệu công việc',
                'TOEIC Speaking — Mô tả và trình bày ý kiến', 'TOEIC Writing — Viết câu và email'],
            'quiz_questions' => [
                ['question' => '[DEMO transcript] “Please meet the client in the lobby at ten.” Where should the listener meet the client?', 'options' => ['In the lobby', 'At the airport', 'In the kitchen', 'At the station'], 'answer' => 0],
                ['question' => 'Email: “Please confirm your attendance by Friday so we can reserve a seat.” What should the recipient do?', 'options' => ['Buy a ticket', 'Confirm attendance', 'Change the venue', 'Send a report'], 'answer' => 1],
                ['question' => 'Which sentence clearly introduces an opinion about teamwork?', 'options' => ['Yesterday at nine.', 'On the left of the room.', 'I believe teamwork helps us solve problems.', 'Thank you for your email.'], 'answer' => 2],
                ['question' => 'Which sentence politely requests information in a business email?', 'options' => ['Send it now!', 'I went home yesterday.', 'The office is large.', 'Could you please send me the schedule?'], 'answer' => 3],
            ],
            'assignments' => [
                '[DEMO Listening — transcript, chưa có audio] Đọc hội thoại mẫu, ghi địa điểm gặp khách hàng, thời gian và hành động cần làm.',
                '[DEMO Reading] Đọc email mời họp mẫu, xác định mục đích, hạn xác nhận và thông tin cần phản hồi.',
                '[DEMO Speaking — dàn ý dạng văn bản, chưa có bản ghi âm] Chuẩn bị dàn ý mô tả một văn phòng và trình bày ý kiến về làm việc nhóm, kèm lý do và ví dụ.',
                '[DEMO Writing] Viết năm câu mô tả nơi làm việc và một email lịch sự hỏi lịch họp, có lời chào, yêu cầu rõ ràng và lời kết.',
            ],
            'submission_prefix' => '[DEMO] Bài luyện TOEIC 4 kỹ năng của ',
            'questions' => ['Cách nhận biết thông tin chính trong TOEIC Listening?', 'Làm sao tìm mục đích và hạn phản hồi trong email TOEIC Reading?',
                'Cách sắp xếp ý khi mô tả trong TOEIC Speaking?', 'Làm sao trình bày ý kiến và ví dụ khi luyện Speaking?', 'Cách viết email lịch sự trong TOEIC Writing?'],
            'reply' => '[DEMO TOEIC 4 kỹ năng] Hãy xác định yêu cầu của bài: tìm từ khóa khi nghe và đọc; dùng ý chính, lý do và ví dụ khi nói; kiểm tra ngữ pháp và cách diễn đạt lịch sự khi viết. Đây là phản hồi mô phỏng để xem giao diện.',
        ];
    }
}
