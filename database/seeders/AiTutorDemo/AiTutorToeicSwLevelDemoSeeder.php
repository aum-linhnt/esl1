<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\User;
use Ramsey\Uuid\Uuid;

abstract class AiTutorToeicSwLevelDemoSeeder extends AiTutorManagementDemoSeeder
{
    protected const TIER = '';

    public function prepareDemoTeacher(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeder chỉ chạy ở môi trường local hoặc testing.');
        }
        $this->user('teacher', 'Nguyễn Minh Anh · Demo', 'teacher');
    }

    protected function user(string $key, string $name, string $role): User
    {
        return parent::user('toeic-sw-'.static::TIER.'-'.$key, $name, $role);
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-toeic-sw-'.static::TIER.'-demo:v1:'.$key)->toString();
    }

    protected function requestPrefix(): string
    {
        return 'demo:toeic-sw:'.static::TIER;
    }

    protected function definition(): array
    {
        // Targets are internal course goals, not official proficiency levels or score conversions.
        $tiers = [
            'starter' => [
                'name' => 'Starter — Nhập môn', 'speaking' => 80, 'writing' => 80, 'level' => 'A1',
                'topics' => ['Phát âm và câu ngắn', 'Trả lời thông tin cá nhân', 'Viết câu đơn', 'Lời chào và yêu cầu cơ bản'],
                'quizzes' => [
                    ['Which sentence is a complete introduction?', ['My name is Nam.', 'In office.', 'Yesterday morning.', 'Very nice.'], 0],
                    ['Which answer matches “Where do you work?”', ['At nine.', 'In a bookstore.', 'By bus.', 'With pleasure.'], 1],
                    ['The manager ___ in the office.', ['are', 'be', 'is', 'am'], 2],
                    ['Which sentence politely requests a document?', ['Give it now!', 'This is a chair.', 'I went home.', 'Please send me the file.'], 3],
                ],
                'tasks' => ['Chuẩn bị đọc thành tiếng: “My name is Nam. I work in a bookstore.” Đánh dấu từ cần luyện phát âm.', 'Soạn câu trả lời ngắn về nơi làm việc, giờ bắt đầu và phương tiện đi lại.', 'Viết năm câu đơn về người và đồ vật trong văn phòng.', 'Viết lời chào và hai câu yêu cầu gửi tài liệu lịch sự.'],
            ],
            'foundation' => [
                'name' => 'Foundation — Nền tảng', 'speaking' => 110, 'writing' => 110, 'level' => 'A2',
                'topics' => ['Mô tả tình huống công sở', 'Trả lời câu hỏi thường gặp', 'Câu mô tả rõ ràng', 'Email ngắn'],
                'quizzes' => [
                    ['Which sentence describes an office activity?', ['The room was built in 1990.', 'I like coffee.', 'Two colleagues are reviewing a report.', 'Thank you for calling.'], 2],
                    ['Which answer gives the time of a meeting?', ['In room three.', 'At ten tomorrow morning.', 'With the sales team.', 'About the budget.'], 1],
                    ['Choose the correct sentence.', ['A woman is typing an email.', 'A woman typing are email.', 'A woman type an emails.', 'A woman is type email.'], 0],
                    ['Which ending fits a polite short email?', ['Do it!', 'No more words.', 'Whatever.', 'Thank you for your help.'], 3],
                ],
                'tasks' => ['Từ mô tả “Hai đồng nghiệp đang xem báo cáo bên bàn họp”, chuẩn bị sáu câu mô tả bằng tiếng Anh.', 'Soạn câu trả lời về lịch họp: 10 giờ sáng mai, phòng 3, nhóm kinh doanh.', 'Viết năm câu về tình huống “Một nhân viên đang gõ email trong văn phòng”.', 'Viết email ngắn xác nhận đã nhận tài liệu và cảm ơn người gửi.'],
            ],
            'intermediate' => [
                'name' => 'Intermediate — Trung cấp', 'speaking' => 140, 'writing' => 140, 'level' => 'B1',
                'topics' => ['Phát triển câu trả lời', 'Giải thích lựa chọn', 'Liên kết câu và lý do', 'Email phản hồi'],
                'quizzes' => [
                    ['Which answer adds a reason to a preference?', ['Online meetings.', 'Sometimes.', 'At work.', 'I prefer online meetings because they save travel time.'], 3],
                    ['Which phrase introduces an example?', ['At nine o’clock,', 'For example,', 'On the desk,', 'Dear Ms. Le,'], 1],
                    ['Choose the sentence with a clear cause and result.', ['The shipment was late, so we changed the schedule.', 'Shipment schedule office.', 'We changed because.', 'Late was the it.'], 0],
                    ['Which response suggests another appointment?', ['I received a package.', 'The office is bright.', 'Could we meet on Thursday instead?', 'It was built last year.'], 2],
                ],
                'tasks' => ['Soạn câu trả lời về hình thức họp yêu thích, có ý chính, lý do và ví dụ.', 'Chuẩn bị giải thích lựa chọn xe buýt đi làm, nêu chi phí và thuận tiện.', 'Viết đoạn ngắn giải thích việc giao hàng trễ dẫn đến đổi lịch.', 'Viết email phản hồi đề nghị đổi cuộc hẹn sang thứ Năm, kèm lý do và lời cảm ơn.'],
            ],
            'advanced' => [
                'name' => 'Advanced — Nâng cao', 'speaking' => 160, 'writing' => 160, 'level' => 'B2',
                'topics' => ['Trình bày quan điểm', 'Lập luận và phản hồi', 'Email có cấu trúc', 'Bài viết nêu ý kiến'],
                'quizzes' => [
                    ['Which sentence clearly states a workplace opinion?', ['The room has windows.', 'I believe flexible hours can improve productivity.', 'It is Tuesday.', 'Please see page two.'], 1],
                    ['Which sentence acknowledges a drawback?', ['We meet at nine.', 'The file is attached.', 'Although training takes time, it improves service quality.', 'Thank you for attending.'], 2],
                    ['Which email sentence defines a next step?', ['Please review the revised plan and reply by Friday.', 'The weather is pleasant.', 'There are many buildings.', 'I visited yesterday.'], 0],
                    ['Which structure supports an opinion essay?', ['Names and addresses only', 'Unrelated sentences', 'A greeting only', 'Position, reasons, examples and conclusion'], 3],
                ],
                'tasks' => ['Chuẩn bị quan điểm về giờ làm linh hoạt, có hai lý do và ví dụ.', 'Soạn phản hồi ý kiến “Đào tạo nhân viên làm mất thời gian”, cân nhắc lợi ích và hạn chế.', 'Viết email đề nghị duyệt kế hoạch sửa đổi, nêu các thay đổi và hạn phản hồi.', 'Viết bài nêu ý kiến về đào tạo tại nơi làm việc, có mở bài, lập luận và kết luận.'],
            ],
            'intensive' => [
                'name' => 'Intensive — Chuyên sâu', 'speaking' => 180, 'writing' => 180, 'level' => 'C1',
                'topics' => ['Diễn đạt linh hoạt và chính xác', 'Phản biện có sắc thái', 'Email xử lý tình huống phức tạp', 'Lập luận sâu và điều kiện'],
                'quizzes' => [
                    ['Which statement presents a qualified recommendation?', ['This is always perfect.', 'Nobody can disagree.', 'I recommend a pilot, provided its results are reviewed monthly.', 'Every option is identical.'], 2],
                    ['Which reply balances competing priorities?', ['We should protect service quality while finding ways to reduce costs.', 'Costs do not matter.', 'Quality never matters.', 'We should ignore both.'], 0],
                    ['Which sentence clarifies responsibility and timing?', ['Someone should do something.', 'The finance team will confirm the revised budget by Friday.', 'It may happen somewhere.', 'The office looks nice.'], 1],
                    ['Which sentence connects evidence to a cautious conclusion?', ['Everyone agrees with me.', 'This proves everything forever.', 'No evidence is necessary.', 'The pilot suggests a benefit, though a longer trial is needed.'], 3],
                ],
                'tasks' => ['Chuẩn bị đề xuất chương trình thử nghiệm, nêu lợi ích, điều kiện và cách đánh giá.', 'Soạn lập luận cân bằng giữa giảm chi phí và giữ chất lượng dịch vụ, kèm phản biện.', 'Viết email xử lý thay đổi ngân sách: giải thích lý do, phân công trách nhiệm và hạn xác nhận.', 'Viết bài về cải tiến quy trình, phân tích bằng chứng sơ bộ, hạn chế và điều kiện mở rộng.'],
            ],
        ];
        $tier = $tiers[static::TIER];
        $skills = ['Speaking', 'Speaking', 'Writing', 'Writing'];
        $lessons = $quizzes = $assignments = $questions = [];
        foreach ($skills as $i => $skill) {
            $lessons[] = 'TOEIC '.$skill.' — '.$tier['topics'][$i];
            [$question, $options, $answer] = $tier['quizzes'][$i];
            $quizzes[] = compact('question', 'options', 'answer');
            $label = $skill === 'Speaking' ? 'Speaking — dàn ý văn bản, chưa có bản ghi âm' : 'Writing';
            $assignments[] = '[DEMO '.$label.'] '.$tier['tasks'][$i];
            $questions[] = 'Cách luyện TOEIC '.$skill.': '.$tier['topics'][$i].'?';
        }
        $questions[] = 'Cách phân bổ thời gian luyện TOEIC Speaking & Writing '.$tier['name'].'?';

        return [
            'title' => '[DEMO] TOEIC Speaking & Writing '.$tier['name'].' — Speaking '.$tier['speaking'].'+ / Writing '.$tier['writing'].'+',
            'marker' => 'AI_TUTOR_TOEIC_SW_'.strtoupper(static::TIER).'_DEMO_V1', 'level' => $tier['level'],
            'lessons' => $lessons, 'quiz_questions' => $quizzes, 'assignments' => $assignments,
            'submission_prefix' => '[DEMO TOEIC Speaking & Writing '.$tier['name'].'] Bài luyện của ', 'questions' => $questions,
            'reply' => '[DEMO TOEIC Speaking & Writing '.$tier['name'].'] Hãy phát triển ý bằng lý do và ví dụ; kiểm tra độ rõ ràng, ngữ pháp và cách diễn đạt. Mục tiêu riêng: Speaking '.$tier['speaking'].'+, Writing '.$tier['writing'].'+. Phản hồi và điểm phần trăm đều là mô phỏng, không phải điểm TOEIC chính thức.',
        ];
    }
}
