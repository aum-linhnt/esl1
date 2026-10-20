<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\User;
use Ramsey\Uuid\Uuid;

abstract class AiTutorIeltsLevelDemoSeeder extends AiTutorManagementDemoSeeder
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
        return parent::user('ielts-'.static::TIER.'-'.$key, $name, $role);
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-ielts-'.static::TIER.'-demo:v1:'.$key)->toString();
    }

    protected function requestPrefix(): string
    {
        return 'demo:ielts:'.static::TIER;
    }

    protected function definition(): array
    {
        // CEFR values organize internal courses; they are not a band conversion.
        $tiers = [
            'foundation' => [
                'name' => 'Foundation — Nền tảng', 'level' => 'A2',
                'topics' => ['Số, thời gian và từ khóa', 'Câu đơn và ý chính', 'Từ vựng mô tả số liệu', 'Câu nêu ý kiến', 'Giới thiệu bản thân'],
                'quizzes' => [
                    ['[DEMO transcript] “My lesson starts at eight.” When does the lesson start?', ['At seven', 'At eight', 'At nine', 'At ten'], 1],
                    ['“The library is open every morning.” What is this sentence about?', ['Library opening times', 'Book prices', 'Bus tickets', 'Food choices'], 0],
                    ['Which word describes a higher number?', ['lower', 'unchanged', 'increased', 'empty'], 2],
                    ['Which sentence gives an opinion?', ['There are ten students.', 'The class starts at nine.', 'This is a table.', 'I think reading is useful.'], 3],
                    ['Which answer introduces a hobby?', ['At nine o’clock.', 'I enjoy drawing in my free time.', 'On the second floor.', 'It costs five dollars.'], 1],
                ],
                'tasks' => ['Đọc transcript: “My lesson starts at eight in room five.” Ghi thời gian và phòng học.', 'Đọc đoạn: “The library opens every morning. Students can borrow books for free.” Ghi ý chính.', 'Viết ba câu mô tả số học viên tăng từ 10 lên 15 rồi 20.', 'Viết năm câu nêu ý kiến về lợi ích của việc đọc sách.', 'Chuẩn bị câu giới thiệu bản thân, nơi ở và sở thích.'],
            ],
            'band45' => [
                'name' => '4.5 — Làm quen dạng bài', 'level' => 'B1',
                'topics' => ['Ghi thông tin chi tiết', 'Tìm thông tin trực tiếp', 'Mô tả thay đổi đơn giản', 'Đoạn văn có lý do', 'Trả lời kèm lý do'],
                'quizzes' => [
                    ['[DEMO transcript] “The registration fee is twenty pounds.” What is the fee?', ['Ten pounds', 'Fifteen pounds', 'Twenty pounds', 'Thirty pounds'], 2],
                    ['“The museum closes on Mondays but opens at weekends.” When is it closed?', ['Monday', 'Saturday', 'Sunday', 'Friday'], 0],
                    ['Which phrase describes no change?', ['rose rapidly', 'remained stable', 'fell slightly', 'doubled'], 1],
                    ['Which sentence provides a reason for learning online?', ['There are many courses.', 'I studied yesterday.', 'The room is quiet.', 'Online lessons save travel time.'], 3],
                    ['Which answer develops a preference?', ['Coffee.', 'Yes.', 'I prefer tea because I find it relaxing.', 'Yesterday.'], 2],
                ],
                'tasks' => ['Đọc transcript: “Registration is twenty pounds. Please arrive at ten on Saturday.” Ghi phí và lịch hẹn.', 'Đọc thông báo: “The museum closes on Mondays but opens at weekends.” Xác định ngày đóng cửa.', 'Mô tả số khách của cửa hàng A từ 100 lên 120, cửa hàng B giữ ở 90.', 'Viết đoạn ý kiến về học trực tuyến, kèm một lý do và ví dụ.', 'Chuẩn bị câu trả lời về đồ uống yêu thích, có lý do và ví dụ.'],
            ],
            'band55' => [
                'name' => '5.5 — Củng cố kỹ năng', 'level' => 'B1',
                'topics' => ['Nhận diện diễn đạt tương đương', 'Ý chính và chi tiết', 'Overview và so sánh', 'Phát triển đoạn văn', 'Mở rộng câu trả lời'],
                'quizzes' => [
                    ['[DEMO transcript] “The course is inexpensive.” Which phrase has a similar meaning?', ['Costs little', 'Takes longer', 'Is unavailable', 'Needs equipment'], 0],
                    ['“Cycling reduces traffic and gives commuters exercise.” What is the main idea?', ['Bicycle prices', 'Benefits of cycling', 'Weather changes', 'Parking fees'], 1],
                    ['Which sentence summarizes two upward trends?', ['A has ten members.', 'The chart has two lines.', 'Both groups grew over the period.', 'B was measured in May.'], 2],
                    ['Which sentence gives an example supporting flexible study?', ['The city has a library.', 'Students have different names.', 'The chart is blue.', 'For example, workers can watch recorded lessons after work.'], 3],
                    ['Which phrase introduces a personal example?', ['In my experience,', 'On the third floor,', 'At a cost of,', 'According to the timetable,'], 0],
                ],
                'tasks' => ['Đọc transcript: “The course is inexpensive and offers flexible hours.” Viết lại hai ý bằng cách diễn đạt tương đương.', 'Đọc đoạn về lợi ích đạp xe đối với sức khỏe và giao thông; ghi ý chính và dẫn chứng.', 'Mô tả dữ liệu: nhóm A tăng 20→35→50, nhóm B tăng 30→40→55. Viết overview và so sánh.', 'Viết hai đoạn về học linh hoạt, mỗi đoạn có ý chính, giải thích và ví dụ.', 'Chuẩn bị câu trả lời về một trải nghiệm học tập, kèm chi tiết và cảm nhận.'],
            ],
            'band70' => [
                'name' => '7.0 — Nâng cao', 'level' => 'B2',
                'topics' => ['Theo dõi lập luận và đổi ý', 'Suy luận có dẫn chứng', 'Chọn đặc điểm nổi bật', 'Lập luận và phản biện', 'Diễn đạt linh hoạt'],
                'quizzes' => [
                    ['[DEMO transcript] “I first chose the morning class, but the evening one fits my work schedule better.” Which class is preferred now?', ['Morning', 'Weekend', 'Online only', 'Evening'], 3],
                    ['“Despite early doubts, the trial attracted twice the expected participants.” What can be inferred?', ['Nobody attended', 'Participation exceeded expectations', 'The trial was canceled', 'The schedule was unchanged'], 1],
                    ['Which sentence highlights contrasting trends?', ['A grew while B declined.', 'The chart has labels.', 'The study was published.', 'A was measured first.'], 0],
                    ['Which phrase introduces a counterargument?', ['For example,', 'In 2020,', 'Some may argue that', 'On the left,'], 2],
                    ['Which answer balances two perspectives?', ['Always.', 'Never.', 'Maybe tomorrow.', 'It depends: remote work saves time, but office work can improve collaboration.'], 3],
                ],
                'tasks' => ['Đọc transcript lựa chọn lớp sáng rồi đổi sang lớp tối do lịch làm việc; ghi lựa chọn cuối và lý do.', 'Đọc đoạn: “Despite early doubts, the trial attracted twice the expected participants.” Nêu suy luận và dẫn chứng.', 'Mô tả bảng: A 50→70→90, B 80→65→40, C 30→30→32. Chọn xu hướng nổi bật và so sánh.', 'Viết lập luận về làm việc từ xa, có phản biện và phản hồi.', 'Chuẩn bị thảo luận lợi ích và hạn chế của làm việc từ xa, dùng ví dụ cụ thể.'],
            ],
            'band75' => [
                'name' => '7.5+ — Chuyên sâu', 'level' => 'C1',
                'topics' => ['Quan điểm và điều kiện ngầm', 'Lập trường và giới hạn kết luận', 'Tổng hợp dữ liệu phức tạp', 'Lập luận có sắc thái', 'Thảo luận ý tưởng trừu tượng'],
                'quizzes' => [
                    ['[DEMO transcript] “The scheme may work, provided long-term funding is secured.” What does the speaker emphasize?', ['The scheme already failed', 'Everyone agrees', 'Success depends on funding', 'Funding is unnecessary'], 2],
                    ['“The small sample suggests a benefit, although broader studies are needed.” Which conclusion is justified?', ['The finding is promising but limited', 'The benefit is proven for everyone', 'No further study is needed', 'The sample was very large'], 0],
                    ['Which overview captures differing patterns?', ['There are three categories.', 'A climbed steadily, B fluctuated, and C was largely unchanged.', 'The table uses percentages.', 'Data came from a survey.'], 1],
                    ['Which statement presents a qualified position?', ['This is always perfect.', 'Everyone must agree.', 'There is no possible drawback.', 'The policy can be beneficial if access remains equitable.'], 3],
                    ['Which phrase helps discuss an abstract idea cautiously?', ['My house is nearby.', 'The bus leaves at six.', 'One possible explanation is that', 'This pen is blue.'], 2],
                ],
                'tasks' => ['Đọc transcript: “The scheme may work, provided long-term funding is secured.” Phân tích lập trường và điều kiện.', 'Đọc nhận xét về nghiên cứu mẫu nhỏ; phân biệt phát hiện sơ bộ với kết luận chắc chắn.', 'Tổng hợp dữ liệu A 20→40→60→80, B 50→30→65→45, C 35→36→35→37; chọn so sánh và tránh suy diễn nguyên nhân.', 'Viết bài về tiếp cận công nghệ giáo dục, cân nhắc lợi ích, bất bình đẳng và điều kiện triển khai.', 'Chuẩn bị thảo luận vai trò của công nghệ trong cơ hội học tập, nêu nhiều góc nhìn và giới hạn.'],
            ],
        ];
        $tier = $tiers[static::TIER];
        $skills = ['Listening', 'Reading', 'Writing Task 1', 'Writing Task 2', 'Speaking'];
        $lessons = $quizzes = $assignments = $questions = [];
        foreach ($skills as $i => $skill) {
            $lessons[] = 'IELTS '.$skill.' — '.$tier['topics'][$i];
            [$question, $options, $answer] = $tier['quizzes'][$i];
            $quizzes[] = compact('question', 'options', 'answer');
            $label = match ($skill) {
                'Listening' => 'Listening — transcript, chưa có audio',
                'Speaking' => 'Speaking — dàn ý văn bản, chưa có bản ghi âm',
                default => $skill,
            };
            $assignments[] = '[DEMO '.$label.'] '.$tier['tasks'][$i];
            $questions[] = 'Cách luyện IELTS '.$skill.': '.$tier['topics'][$i].'?';
        }

        return [
            'title' => '[DEMO] IELTS '.$tier['name'].' — Luyện 4 kỹ năng',
            'marker' => 'AI_TUTOR_IELTS_'.strtoupper(static::TIER).'_DEMO_V1', 'level' => $tier['level'],
            'lessons' => $lessons, 'quiz_questions' => $quizzes, 'assignments' => $assignments,
            'submission_prefix' => '[DEMO IELTS '.$tier['name'].'] Bài luyện của ', 'questions' => $questions,
            'reply' => '[DEMO IELTS '.$tier['name'].'] Hãy xác định yêu cầu, chọn dẫn chứng và giải thích lựa chọn. Khi nói và viết, phát triển ý bằng lý do và ví dụ. Đây là phản hồi mô phỏng, điểm phần trăm không phải band IELTS.',
        ];
    }
}
