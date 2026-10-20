<?php

namespace Database\Seeders\AiTutorDemo;

use App\Models\User;
use Ramsey\Uuid\Uuid;

abstract class AiTutorCefrLevelDemoSeeder extends AiTutorManagementDemoSeeder
{
    protected const LEVEL = '';

    public function prepareDemoTeacher(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeder chỉ chạy ở môi trường local hoặc testing.');
        }
        $this->user('teacher', 'Nguyễn Minh Anh · Demo', 'teacher');
    }

    protected function user(string $key, string $name, string $role): User
    {
        return parent::user('cefr-'.strtolower(static::LEVEL).'-'.$key, $name, $role);
    }

    protected function uuid(string $key): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_URL, 'esl:ai-tutor-cefr-'.strtolower(static::LEVEL).'-demo:v1:'.$key)->toString();
    }

    protected function requestPrefix(): string
    {
        return 'demo:cefr:'.strtolower(static::LEVEL);
    }

    protected function definition(): array
    {
        $levels = [
            'A1' => [
                'name' => 'Nhập môn',
                'topics' => ['Thông tin cá nhân và thời gian', 'Thông báo ngắn', 'Giới thiệu bản thân', 'Câu đơn và biểu mẫu'],
                'quizzes' => [
                    ['[DEMO transcript] “My name is Anna. I am from Hanoi.” Where is Anna from?', ['Hanoi', 'London', 'Paris', 'Tokyo'], 0],
                    ['Notice: “Open from nine to five.” When does the shop open?', ['At five', 'At nine', 'At six', 'At ten'], 1],
                    ['Which answer matches “What is your name?”', ['At school.', 'By bus.', 'My name is Minh.', 'On Monday.'], 2],
                    ['I ___ a student.', ['is', 'are', 'be', 'am'], 3],
                ],
                'tasks' => ['Đọc transcript: “My name is Anna. I am from Hanoi. My class starts at nine.” Ghi tên, nơi ở và giờ học.', 'Đọc thông báo cửa hàng mở từ 9 giờ đến 17 giờ; tìm giờ mở và đóng cửa.', 'Chuẩn bị năm câu giới thiệu tên, nơi ở, công việc và sở thích.', 'Viết năm câu đơn về bản thân và điền biểu mẫu tên, thành phố, nghề nghiệp.'],
            ],
            'A2' => [
                'name' => 'Sơ cấp',
                'topics' => ['Mua sắm và đi lại', 'Lịch trình hằng ngày', 'Giao tiếp tình huống quen thuộc', 'Email ngắn'],
                'quizzes' => [
                    ['[DEMO transcript] “The bus to the museum leaves at ten.” Where is the bus going?', ['The airport', 'The station', 'The museum', 'The hotel'], 2],
                    ['Schedule: “Lunch at twelve, class at one.” What happens at one?', ['Lunch', 'Class', 'Shopping', 'Dinner'], 1],
                    ['Which sentence asks for a price?', ['How much is this shirt?', 'Where do you live?', 'What time is it?', 'Who is your teacher?'], 0],
                    ['Which sentence fits an invitation email?', ['The box is heavy.', 'It rained yesterday.', 'The window is open.', 'Would you like to join us for lunch?'], 3],
                ],
                'tasks' => ['Đọc transcript: “The bus to the museum leaves at ten from stop two.” Ghi điểm đến, giờ và điểm đón.', 'Đọc lịch “Lunch at twelve, class at one, shopping at four”; ghi ba hoạt động.', 'Soạn hội thoại ngắn hỏi giá áo và chọn kích cỡ.', 'Viết email mời bạn ăn trưa, nêu thời gian, địa điểm và đề nghị xác nhận.'],
            ],
            'B1' => [
                'name' => 'Trung cấp',
                'topics' => ['Trải nghiệm và kế hoạch', 'Ý chính trong bài viết quen thuộc', 'Kể chuyện và giải thích ý kiến', 'Đoạn văn và email công việc'],
                'quizzes' => [
                    ['[DEMO transcript] “I planned to travel on Friday, but I changed it to Saturday because of work.” When will the speaker travel?', ['Thursday', 'Friday', 'Saturday', 'Sunday'], 2],
                    ['“Joining a local club helps newcomers meet people and practise a language.” What is the main idea?', ['Benefits of joining a club', 'Travel costs', 'Office design', 'Weather forecasts'], 0],
                    ['Which answer explains a preference?', ['Books.', 'I enjoy reading because it helps me understand different lives.', 'Maybe.', 'Last night.'], 1],
                    ['Which phrase links a reason to a result?', ['On the left,', 'Dear Sir,', 'At seven,', 'As a result,'], 3],
                ],
                'tasks' => ['Đọc transcript thay đổi chuyến đi từ thứ Sáu sang thứ Bảy do công việc; ghi lịch mới và nguyên nhân.', 'Đọc đoạn về câu lạc bộ địa phương giúp người mới kết bạn và luyện ngôn ngữ; viết lại ý chính.', 'Chuẩn bị kể một trải nghiệm học tập, nêu sự việc, cảm nhận và lý do.', 'Viết email giải thích thay đổi kế hoạch công việc, đề nghị thời gian thay thế.'],
            ],
            'B2' => [
                'name' => 'Trung cao cấp',
                'topics' => ['Theo dõi thảo luận và lập luận', 'So sánh quan điểm', 'Trình bày và phản biện', 'Bài viết có cấu trúc'],
                'quizzes' => [
                    ['[DEMO transcript] “The plan costs more initially, yet its maintenance savings may justify the investment.” What supports the plan?', ['A shorter meeting', 'A new office', 'Lower maintenance costs', 'Fewer clients'], 2],
                    ['“Remote work offers flexibility, whereas office work supports spontaneous collaboration.” What is contrasted?', ['Two working arrangements', 'Two train routes', 'Two prices', 'Two seasons'], 0],
                    ['Which sentence acknowledges another perspective?', ['That is impossible.', 'Everything is identical.', 'Nobody thinks that.', 'I see your point, although there may be another solution.'], 3],
                    ['Which sentence introduces a clear argument?', ['The chart is green.', 'Flexible hours can improve productivity by reducing commuting stress.', 'The office opens at nine.', 'Here is my address.'], 1],
                ],
                'tasks' => ['Đọc transcript về đầu tư có chi phí ban đầu cao nhưng tiết kiệm bảo trì; xác định lập luận ủng hộ.', 'Đọc hai ý kiến về làm việc từ xa và làm tại văn phòng; so sánh ưu điểm và hạn chế.', 'Chuẩn bị trình bày về giờ làm linh hoạt, có phản biện và phản hồi.', 'Viết bài có mở bài, hai luận điểm, ví dụ và kết luận về cân bằng công việc và cuộc sống.'],
            ],
            'C1' => [
                'name' => 'Cao cấp',
                'topics' => ['Lập trường và điều kiện ngầm', 'Tổng hợp nội dung học thuật và công việc', 'Diễn đạt linh hoạt', 'Lập luận sâu và sắc thái'],
                'quizzes' => [
                    ['[DEMO transcript] “The proposal is promising, provided access remains equitable.” What condition is emphasized?', ['Lower rent', 'A shorter report', 'Earlier delivery', 'Equitable access'], 3],
                    ['“The evidence points to a benefit, but the narrow sample limits generalization.” What is the author’s stance?', ['Complete certainty', 'Cautious support', 'Total rejection', 'No opinion'], 1],
                    ['Which phrase politely reformulates a point?', ['Let me put that another way.', 'You must agree.', 'It is obvious to everyone.', 'No more discussion.'], 0],
                    ['Which sentence offers a nuanced conclusion?', ['This works in every case.', 'There are no limitations.', 'The approach is useful when its assumptions match the context.', 'Evidence is unnecessary.'], 2],
                ],
                'tasks' => ['Đọc transcript đề xuất có điều kiện tiếp cận công bằng; phân tích lập trường và điều kiện.', 'Tổng hợp hai nhận xét: nghiên cứu cho thấy lợi ích nhưng mẫu hẹp; phân biệt phát hiện và giới hạn kết luận.', 'Chuẩn bị giải thích lại một đề xuất bằng hai cách diễn đạt, phù hợp đồng nghiệp và khách hàng.', 'Viết lập luận về công nghệ giáo dục, cân nhắc hiệu quả, tiếp cận và điều kiện áp dụng.'],
            ],
            'C2' => [
                'name' => 'Thành thạo',
                'topics' => ['Sắc thái và hàm ý', 'Đánh giá lập luận phức tạp', 'Diễn đạt chính xác theo ngữ cảnh', 'Tổng hợp và phản biện tinh tế'],
                'quizzes' => [
                    ['[DEMO transcript] “Calling the transition effortless might be a little optimistic.” What is implied?', ['The transition is complete', 'There is no transition', 'The transition may be more difficult than claimed', 'Optimism is prohibited'], 2],
                    ['“The correlation is robust; treating it as proof of causation, however, would overstate the evidence.” What is cautioned against?', ['Assuming causation from correlation', 'Collecting more data', 'Reporting the findings', 'Comparing groups'], 0],
                    ['Which wording expresses a subtle reservation?', ['Everything is wrong.', 'Everyone agrees.', 'There is no doubt.', 'The proposal has merit, though its scope may warrant reconsideration.'], 3],
                    ['Which statement separates evidence from interpretation?', ['All interpretations are facts.', 'The data show an increase; its cause remains uncertain.', 'No data are needed.', 'The cause is always obvious.'], 1],
                ],
                'tasks' => ['Đọc transcript: “Calling the transition effortless might be a little optimistic.” Phân tích hàm ý và viết lại bằng cách trực tiếp.', 'Đọc nhận xét về tương quan và quan hệ nhân quả; xác định điều được chứng minh và điều còn chưa chắc chắn.', 'Chuẩn bị trình bày cùng một phản biện bằng văn phong trang trọng và hội thoại, giữ sắc thái thận trọng.', 'Viết bài tổng hợp nhiều quan điểm về một thay đổi tổ chức, tách bằng chứng, diễn giải và khuyến nghị.'],
            ],
        ];
        $level = $levels[static::LEVEL];
        $lessons = $quizzes = $assignments = $questions = [];
        foreach (['Listening', 'Reading', 'Speaking', 'Writing'] as $i => $skill) {
            $lessons[] = 'English '.static::LEVEL.' '.$skill.' — '.$level['topics'][$i];
            [$question, $options, $answer] = $level['quizzes'][$i];
            $quizzes[] = compact('question', 'options', 'answer');
            $label = match ($skill) {
                'Listening' => 'Listening — transcript, chưa có audio',
                'Speaking' => 'Speaking — dàn ý văn bản, chưa có bản ghi âm',
                default => $skill,
            };
            $assignments[] = '[DEMO '.$label.'] '.$level['tasks'][$i];
            $questions[] = 'Cách luyện English '.static::LEVEL.' '.$skill.': '.$level['topics'][$i].'?';
        }
        $questions[] = 'Cách sắp xếp kế hoạch luyện 4 kỹ năng cho khóa CEFR '.static::LEVEL.'?';

        return [
            'title' => '[DEMO] English '.static::LEVEL.' — '.$level['name'].' — CEFR 4 kỹ năng',
            'marker' => 'AI_TUTOR_CEFR_'.static::LEVEL.'_DEMO_V1', 'level' => static::LEVEL,
            'lessons' => $lessons, 'quiz_questions' => $quizzes, 'assignments' => $assignments,
            'submission_prefix' => '[DEMO CEFR '.static::LEVEL.'] Bài luyện của ', 'questions' => $questions,
            'reply' => '[DEMO CEFR '.static::LEVEL.'] Hãy luyện theo yêu cầu của bài, giải thích lựa chọn và phát triển ý bằng ví dụ. '.static::LEVEL.' là mục tiêu của khóa học; phản hồi và điểm phần trăm đều là mô phỏng, không xác nhận trình độ CEFR thực tế.',
        ];
    }
}
