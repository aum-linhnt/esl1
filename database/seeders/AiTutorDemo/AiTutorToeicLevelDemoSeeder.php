<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\{Course, User};
use Ramsey\Uuid\Uuid;

abstract class AiTutorToeicLevelDemoSeeder extends AiTutorManagementDemoSeeder
{
    protected const TIER = '';

    public function run(): void
    {
        parent::run();
        $this->updateCourseTargets();
    }

    public function updateCourseTargets(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeder chỉ chạy ở môi trường local hoặc testing.');
        }
        $demo = $this->definition();
        $course = Course::where('slug', static::SLUG)->firstOrFail();
        $teacher = User::where('email', 'ai-tutor-demo-toeic-'.static::TIER.'-teacher@example.test')->firstOrFail();
        if ((string) $course->created_by !== (string) $teacher->id || ! str_contains($course->description ?? '', $demo['marker'])) {
            throw new \RuntimeException('Slug demo đã được dùng bởi khóa học khác; seeder không ghi đè.');
        }
        $course->update(['title' => $demo['title']]);
        $this->command?->info('Đã cập nhật 3 mục tiêu khóa DEMO #'.$course->id.': '.url('/teacher/ai-tutor?course_id='.$course->id));
    }

    public function prepareDemoTeacher(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeder chỉ chạy ở môi trường local hoặc testing.');
        }
        $this->user('teacher', 'Nguyễn Minh Anh · Demo', 'teacher');
    }

    protected function user(string $key, string $name, string $role): User
    {
        return parent::user('toeic-'.static::TIER.'-'.$key, $name, $role);
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-toeic-'.static::TIER.'-demo:v1:'.$key)->toString();
    }

    protected function requestPrefix(): string
    {
        return 'demo:toeic:'.static::TIER;
    }

    protected function definition(): array
    {
        // Course levels are internal teaching labels, not an official TOEIC/CEFR conversion.
        $tiers = [
            'starter' => [
                'name' => 'Starter — Nhập môn', 'target' => 350, 'speaking' => 80, 'writing' => 80, 'level' => 'A1',
                'topics' => ['Câu ngắn và từ vựng cơ bản', 'Thông báo đơn giản', 'Giới thiệu và câu đơn', 'Viết câu cơ bản'],
                'quizzes' => [
                    ['[DEMO transcript] “The office opens at nine.” When does the office open?', ['At seven', 'At eight', 'At nine', 'At ten'], 2],
                    ['Notice: “Please close the door.” What should you do?', ['Close the door', 'Open a window', 'Call a manager', 'Buy a ticket'], 0],
                    ['Which sentence introduces yourself?', ['The bus is late.', 'My name is Linh.', 'Close the door.', 'It is raining.'], 1],
                    ['She ___ in an office.', ['work', 'working', 'worked yesterday', 'works'], 3],
                ],
                'tasks' => ['Đọc transcript ba câu ngắn, ghi thời gian và từ khóa.', 'Đọc thông báo đóng cửa văn phòng, xác định hành động cần làm.', 'Chuẩn bị năm câu giới thiệu bản thân và công việc.', 'Viết năm câu đơn về một ngày làm việc.'],
            ],
            'foundation' => [
                'name' => 'Foundation — Nền tảng', 'target' => 500, 'speaking' => 110, 'writing' => 110, 'level' => 'A2',
                'topics' => ['Hội thoại công sở', 'Ngữ pháp và email ngắn', 'Mô tả tình huống', 'Email ngắn'],
                'quizzes' => [
                    ['[DEMO transcript] “Could you bring the files to the meeting room?” What does the speaker need?', ['A taxi', 'The files', 'A lunch menu', 'A ticket'], 1],
                    ['Please ___ your attendance by Monday.', ['confirm', 'confirms', 'confirming', 'confirmation'], 0],
                    ['Which sentence describes an office scene?', ['I disagree with that policy.', 'Thank you for your reply.', 'Two colleagues are discussing a document.', 'Could you send the invoice?'], 2],
                    ['Which sentence is a polite email opening?', ['Send this immediately!', 'Why are you late?', 'I do not care.', 'Dear Ms. Tran, thank you for your message.'], 3],
                ],
                'tasks' => ['Đọc transcript yêu cầu công việc, ghi người nói và yêu cầu chính.', 'Đọc email mời họp ngắn, tìm thời gian và nơi họp.', 'Chuẩn bị mô tả văn phòng bằng sáu câu rõ ràng.', 'Viết email ngắn xác nhận tham dự cuộc họp.'],
            ],
            'intermediate' => [
                'name' => 'Intermediate — Trung cấp', 'target' => 650, 'speaking' => 140, 'writing' => 140, 'level' => 'B1',
                'topics' => ['Hội thoại dài và thay đổi lịch', 'Email và thông tin chi tiết', 'Trình bày ý kiến', 'Viết phản hồi'],
                'quizzes' => [
                    ['[DEMO transcript] “The supplier is delayed, so we will move the launch to next week.” Why was the launch moved?', ['The office closed', 'A client canceled', 'The supplier is delayed', 'The team finished early'], 2],
                    ['Email: “Attach your receipt when requesting reimbursement.” What must be attached?', ['A receipt', 'A map', 'A timetable', 'A photograph'], 0],
                    ['Which sentence supports an opinion with a reason?', ['There is a desk.', 'I prefer online meetings because they reduce travel time.', 'Good morning.', 'Please find the attachment.'], 1],
                    ['Which reply proposes an alternative meeting time?', ['I enjoyed the event.', 'The report has three pages.', 'Thank you for the gift.', 'Could we meet on Thursday instead?'], 3],
                ],
                'tasks' => ['Đọc transcript cuộc trao đổi đổi lịch, ghi nguyên nhân và lịch mới.', 'Đọc email hoàn phí và xác định các giấy tờ cần gửi.', 'Chuẩn bị ý kiến về họp trực tuyến với hai lý do và một ví dụ.', 'Viết email phản hồi đề nghị đổi lịch họp.'],
            ],
            'advanced' => [
                'name' => 'Advanced — Nâng cao', 'target' => 800, 'speaking' => 160, 'writing' => 160, 'level' => 'B2',
                'topics' => ['Nghe suy luận', 'Đọc nhiều tài liệu', 'Giải thích quan điểm', 'Email chi tiết'],
                'quizzes' => [
                    ['[DEMO transcript] “The room only seats ten, and twelve people confirmed. Let us look for another space.” What is implied?', ['The meeting needs a larger room', 'Nobody confirmed', 'The event was canceled', 'There are too many chairs'], 0],
                    ['Notice: “Delivery is Friday.” Update: “The shipment will arrive two days later.” When is the revised delivery?', ['Thursday', 'Sunday', 'Monday', 'Tuesday'], 1],
                    ['Which sentence acknowledges a drawback before supporting a proposal?', ['The office is upstairs.', 'Thank you for attending.', 'Although training takes time, it can improve service quality.', 'Where is the printer?'], 2],
                    ['Which email sentence clearly states the next action?', ['The weather was nice.', 'I used to work here.', 'Many people visited.', 'Please review the revised contract and send your comments by Friday.'], 3],
                ],
                'tasks' => ['Đọc transcript thảo luận địa điểm họp, suy luận vấn đề và dẫn chứng.', 'Đối chiếu thông báo giao hàng và email cập nhật, ghi các thay đổi.', 'Chuẩn bị quan điểm về đào tạo nhân viên, nêu lợi ích và hạn chế.', 'Viết email chi tiết giải thích thay đổi dự án và bước tiếp theo.'],
            ],
            'intensive' => [
                'name' => 'Intensive — Chuyên sâu', 'target' => 900, 'speaking' => 180, 'writing' => 180, 'level' => 'C1',
                'topics' => ['Tốc độ và thông tin ngầm', 'Tổng hợp tài liệu phức tạp', 'Lập luận mạch lạc', 'Viết lập luận rõ ràng'],
                'quizzes' => [
                    ['[DEMO transcript] “We can approve the proposal provided the maintenance budget is revised.” What condition is required?', ['Hire another team', 'Cancel the proposal', 'Revise the maintenance budget', 'Move the office'], 2],
                    ['Policy: “Approval is required above $500.” Invoice: “Total: $620.” Which action is needed?', ['Seek approval', 'Ignore the policy', 'Pay only $500', 'Cancel all purchases'], 0],
                    ['Which structure best organizes a reasoned recommendation?', ['Greetings only', 'Recommendation, reasons, example and conclusion', 'Unrelated facts', 'A list of names'], 1],
                    ['Which sentence presents a recommendation and a qualification?', ['The office has windows.', 'We met last year.', 'Please call me.', 'I recommend a pilot program, provided that its results are reviewed monthly.'], 3],
                ],
                'tasks' => ['Đọc transcript phê duyệt có điều kiện, ghi điều kiện và phân biệt ý chính với chi tiết.', 'Tổng hợp quy định, hóa đơn và email để đề xuất hành động có dẫn chứng.', 'Chuẩn bị đề xuất cải tiến quy trình, có phản biện và kết luận mạch lạc.', 'Viết lập luận về chương trình thử nghiệm, có lợi ích, rủi ro và điều kiện đánh giá.'],
            ],
        ];
        $tier = $tiers[static::TIER];
        $skills = ['Listening', 'Reading', 'Speaking', 'Writing'];
        $lessons = $questions = $assignments = [];
        foreach ($skills as $i => $skill) {
            $lessons[] = 'TOEIC '.$skill.' — '.$tier['topics'][$i];
            [$question, $options, $answer] = $tier['quizzes'][$i];
            $questions[] = compact('question', 'options', 'answer');
            $label = match ($skill) {
                'Listening' => 'Listening — transcript, chưa có audio',
                'Speaking' => 'Speaking — dàn ý văn bản, chưa có bản ghi âm',
                default => $skill,
            };
            $assignments[] = '[DEMO '.$label.'] '.$tier['tasks'][$i];
        }

        return [
            'title' => '[DEMO] TOEIC '.$tier['name'].' — 4 kỹ năng — L&R '.$tier['target'].'+ / Speaking '.$tier['speaking'].'+ / Writing '.$tier['writing'].'+',
            'marker' => 'AI_TUTOR_TOEIC_'.strtoupper(static::TIER).'_DEMO_V1', 'level' => $tier['level'],
            'lessons' => $lessons, 'quiz_questions' => $questions, 'assignments' => $assignments,
            'submission_prefix' => '[DEMO TOEIC '.$tier['name'].'] Bài luyện của ',
            'questions' => [
                'Cách luyện Listening: '.$tier['topics'][0].'?', 'Cách luyện Reading: '.$tier['topics'][1].'?',
                'Cách luyện Speaking: '.$tier['topics'][2].'?', 'Cách luyện Writing: '.$tier['topics'][3].'?',
                'Nên phân bổ thời gian ôn TOEIC '.$tier['name'].' như thế nào?',
            ],
            'reply' => '[DEMO TOEIC '.$tier['name'].'] Hãy luyện theo yêu cầu của từng kỹ năng, giải thích lựa chọn và kiểm tra lại ví dụ. Mục tiêu riêng: tổng Listening & Reading '.$tier['target'].'+, Speaking '.$tier['speaking'].'+, Writing '.$tier['writing'].'+. Đây là mục tiêu khóa học; phản hồi và điểm luyện tập đều là mô phỏng.',
        ];
    }
}
