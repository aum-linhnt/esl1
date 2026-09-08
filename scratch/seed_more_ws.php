<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\QuestionBank;

$extraQuestions = [
    // Writing A1
    [
        'skill' => 'writing',
        'difficulty' => 'A1',
        'question_type' => 'essay_writing',
        'question_text' => "TASK 1: Postcard to a Friend (At least 40 words)\n\nYou are on vacation at the beach. Write a short message to your friend Alex.\nIn your message, you should:\n- Tell Alex where you are\n- Describe the weather\n- Say what activities you did today",
        'options' => [],
        'correct_answer' => 'A completed postcard meeting all 3 bullet points with at least 40 words.',
        'explanation' => 'Đảm bảo viết đủ 3 ý: địa điểm kỳ nghỉ, thời tiết và các hoạt động đã làm.',
        'meta_data' => ['part' => 1, 'min_words' => 40, 'task_type' => 'Postcard / Short Message'],
    ],
    // Writing A2
    [
        'skill' => 'writing',
        'difficulty' => 'A2',
        'question_type' => 'essay_writing',
        'question_text' => "TASK 1: Email of Apology & Rescheduling (At least 80 words)\n\nYou promised to attend your friend's birthday party this Saturday, but you cannot come because of an unexpected exam.\nWrite an email to your friend:\n- Apologize for not being able to come\n- Explain why you are busy\n- Suggest another day to meet up next week",
        'options' => [],
        'correct_answer' => 'A well-formatted email with apology, reason, and alternative meeting suggestion.',
        'explanation' => 'Bố cục thư gồm: lời xin lỗi chân thành, lý do cụ thể và đề xuất cuộc hẹn bù vào tuần sau.',
        'meta_data' => ['part' => 1, 'min_words' => 80, 'task_type' => 'Friendly Email'],
    ],
    // Writing B1
    [
        'skill' => 'writing',
        'difficulty' => 'B1',
        'question_type' => 'essay_writing',
        'question_text' => "TASK 1: Formal Request Letter (At least 120 words)\n\nYou are planning a study tour for your class to a local science museum. Write a letter to the museum manager:\n- Introduce yourself and your class\n- Inquire about ticket discounts for groups\n- Ask about guided tour schedules and special exhibitions",
        'options' => [],
        'correct_answer' => 'A formal inquiry letter with proper opening, 3 clear bullet points, and professional closing.',
        'explanation' => 'Sử dụng văn phong trang trọng (formal tone), diễn đạt rõ ràng các yêu cầu hỏi thông tin.',
        'meta_data' => ['part' => 1, 'min_words' => 120, 'task_type' => 'Formal Letter'],
    ],
    // Writing B2
    [
        'skill' => 'writing',
        'difficulty' => 'B2',
        'question_type' => 'essay_writing',
        'question_text' => "TASK 2: Opinion Essay (At least 250 words)\n\nSome educators argue that students should be taught practical life skills (such as personal finance and cooking) at school alongside academic subjects. Others believe schools should focus solely on academic curriculum.\n\nDiscuss both views and give your own opinion. Provide reasons and examples from your knowledge or experience.",
        'options' => [],
        'correct_answer' => 'A well-structured 4-paragraph essay discussing both sides with relevant examples and clear thesis.',
        'explanation' => 'Bài luận cần có Mở bài (dẫn dắt + thesis), 2 Thân bài (phân tích 2 quan điểm đối lập), và Kết luận khẳng định quan điểm bản thân.',
        'meta_data' => ['part' => 2, 'min_words' => 250, 'task_type' => 'Discussion Essay'],
    ],
    // Writing C1
    [
        'skill' => 'writing',
        'difficulty' => 'C1',
        'question_type' => 'essay_writing',
        'question_text' => "TASK 2: Critical Analytical Essay (At least 280 words)\n\nIn the era of artificial intelligence and automated decision-making algorithms, human critical judgment is more critical than ever. However, excessive reliance on AI systems might atrophy our cognitive faculties.\n\nEvaluate the ethical and intellectual implications of algorithmic dependence in contemporary higher education and professional workplaces. Propose sustainable frameworks for human-AI collaboration.",
        'options' => [],
        'correct_answer' => 'An insightful, advanced analytical essay with sophisticated vocabulary, nuanced argumentation, and cohesive transitions.',
        'explanation' => 'Yêu cầu lập luận đa chiều, từ vựng học thuật cao cấp C1 (e.g., algorithmic bias, cognitive atrophy, ethical governance).',
        'meta_data' => ['part' => 2, 'min_words' => 280, 'task_type' => 'Analytical Essay'],
    ],

    // Speaking A1
    [
        'skill' => 'speaking',
        'difficulty' => 'A1',
        'question_type' => 'audio_recording',
        'question_text' => "PART 1: My Family and Home (2 Minutes)\n\nPlease answer the following questions:\n1. Where do you live? Do you live in a house or an apartment?\n2. How many people are there in your family?\n3. What do you like doing together with your family on weekends?",
        'options' => [],
        'correct_answer' => 'audio_recording',
        'explanation' => 'Nói to rõ, trả lời đầy đủ 3 câu hỏi với câu hoàn chỉnh.',
        'meta_data' => ['part' => 1, 'prep_time' => 30, 'duration' => 120, 'task_type' => 'Social Interaction'],
    ],
    // Speaking A2
    [
        'skill' => 'speaking',
        'difficulty' => 'A2',
        'question_type' => 'audio_recording',
        'question_text' => "PART 1: Shopping and Favorite Food (2 - 3 Minutes)\n\nPlease talk about your shopping habits and eating preferences:\n1. Do you prefer shopping online or in traditional stores? Why?\n2. What is your favorite dish and how often do you eat it?\n3. Do you enjoy cooking at home or eating out with friends?",
        'options' => [],
        'correct_answer' => 'audio_recording',
        'explanation' => 'Sử dụng các liên từ nối đơn giản (because, and, but, so) để phát triển câu trả lời.',
        'meta_data' => ['part' => 1, 'prep_time' => 45, 'duration' => 180, 'task_type' => 'Social Interaction'],
    ],
    // Speaking B1
    [
        'skill' => 'speaking',
        'difficulty' => 'B1',
        'question_type' => 'audio_recording',
        'question_text' => "PART 2: Solution Discussion - Weekend Class Activity (3 - 4 Minutes)\n\nSituation: Your English class is planning an outdoor bonding activity for next Sunday. Three options are proposed:\n- Option 1: A picnic in the city central park\n- Option 2: A volunteer trip to an animal shelter\n- Option 3: A museum visit followed by a coffee workshop\n\nWhich option do you think is best for the class? Explain your choice and state why you rejected the other two options.",
        'options' => [],
        'correct_answer' => 'audio_recording',
        'explanation' => 'Cấu trúc bài nói: Chọn 1 phương án tối ưu, đưa ra 2 lý do ủng hộ và nêu lý do từ chối 2 phương án còn lại.',
        'meta_data' => ['part' => 2, 'prep_time' => 60, 'duration' => 180, 'task_type' => 'Solution Discussion'],
    ],
    // Speaking B2
    [
        'skill' => 'speaking',
        'difficulty' => 'B2',
        'question_type' => 'audio_recording',
        'question_text' => "PART 3: Topic Development - Public Transportation in Smart Cities (4 - 5 Minutes)\n\nTopic: Enhancing public transit networks yields significant societal benefits.\n- Environmental preservation (emission reduction)\n- Economic efficiency (less traffic congestion)\n- Social equity (affordable mobility for all citizens)\n\nPresent your ideas clearly, expand on the suggested points, and answer: How can governments encourage more private car owners to switch to public transit?",
        'options' => [],
        'correct_answer' => 'audio_recording',
        'explanation' => 'Thuyết trình có cấu trúc: Giới thiệu chủ đề, phân tích từng luận điểm với ví dụ thực tế và giải quyết câu hỏi mở rộng.',
        'meta_data' => ['part' => 3, 'prep_time' => 60, 'duration' => 240, 'task_type' => 'Topic Development'],
    ],
    // Speaking C1
    [
        'skill' => 'speaking',
        'difficulty' => 'C1',
        'question_type' => 'audio_recording',
        'question_text' => "PART 3: In-depth Oral Discourse - The Ethics of Climate Engineering (4 - 5 Minutes)\n\nTopic: Geoengineering technologies (such as solar radiation management and ocean fertilization) are being advocated to counteract global warming.\n\nElaborate on the scientific promises versus systemic risks of climate intervention. In your discourse, evaluate:\n- Geopolitical governance and moral hazard\n- Unintended ecological consequences\n- The imperative of equitable international consensus",
        'options' => [],
        'correct_answer' => 'audio_recording',
        'explanation' => 'Bài nói học thuật nâng cao C1: lập luận sâu sắc, mạch lạc, sử dụng các cấu trúc câu phức và thuật ngữ chuyên sâu.',
        'meta_data' => ['part' => 3, 'prep_time' => 60, 'duration' => 240, 'task_type' => 'Topic Development'],
    ],
];

foreach ($extraQuestions as $qData) {
    QuestionBank::create($qData);
}

echo "Successfully seeded " . count($extraQuestions) . " rich Writing and Speaking questions into QuestionBank!\n";
