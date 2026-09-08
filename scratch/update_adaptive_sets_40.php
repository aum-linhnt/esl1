<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ExamSet;

$sections40 = [
    'listening' => ['name' => 'Nghe hiểu thích ứng', 'count' => 12],
    'reading' => ['name' => 'Đọc hiểu thích ứng', 'count' => 14],
    'writing' => ['name' => 'Viết luận & thư tín', 'count' => 7],
    'speaking' => ['name' => 'Nói phản xạ & thuyết trình', 'count' => 7]
];

$adaptiveSets = [
    'adaptive_cefr_placement' => [
        'title' => 'Thi Thích Ứng CEFR - Khảo Thí Toàn Diện 4 Kỹ Năng (Full Simulation)',
        'description' => 'Khảo thí Toàn diện (Full Simulation CAT) gồm 40 câu hỏi đa tầng chuẩn quốc tế trong 60 phút: 12 Nghe, 14 Đọc, 7 Viết, 7 Nói. Thuật toán AI tự động phân nhánh độ khó liên tục từ A1 đến C1.',
        'difficulty' => 'Mixed (A1-C1)',
        'question_count' => 40,
        'duration_minutes' => 60,
        'reward_coins' => 50,
        'sections' => $sections40,
    ],
    'adaptive_vstep_4skills' => [
        'title' => 'Thi Thích Ứng VSTEP 4 Kỹ Năng - Mô Phỏng Khảo Thí Bậc 3 đến Bậc 5',
        'description' => 'Mô phỏng kỳ thi VSTEP toàn diện chuẩn Bộ GD&ĐT gồm 40 câu hỏi (60 phút). Tự động phân nhánh linh hoạt để chẩn đoán chính xác bậc năng lực Bậc 3 (B1), Bậc 4 (B2) hoặc Bậc 5 (C1).',
        'difficulty' => 'B1 - C1',
        'question_count' => 40,
        'duration_minutes' => 60,
        'reward_coins' => 60,
        'sections' => $sections40,
    ],
    'adaptive_ielts_fasttrack' => [
        'title' => 'Thi Thích Ứng IELTS on Computer - Full Simulation Band 4.0 - 8.5',
        'description' => 'Khảo thí thích ứng học thuật toàn diện 40 câu hỏi mô phỏng format IELTS Academic on Computer (60 phút) giúp định vị chính xác điểm mạnh và điểm cần cải thiện cho từng kỹ năng.',
        'difficulty' => 'Band 4.0 - 8.5',
        'question_count' => 40,
        'duration_minutes' => 60,
        'reward_coins' => 60,
        'sections' => $sections40,
    ],
    'adaptive_business_pro' => [
        'title' => 'Thi Thích Ứng Tiếng Anh Thương Mại & Công Sở (Business English CAT)',
        'description' => 'Đánh giá toàn diện 4 kỹ năng trong môi trường doanh nghiệp quốc tế (40 câu / 60 phút): nghe hội nghị, phân tích báo cáo thương mại, viết email đối tác và thuyết trình giải pháp kinh doanh.',
        'difficulty' => 'B1 - C1',
        'question_count' => 40,
        'duration_minutes' => 60,
        'reward_coins' => 50,
        'sections' => $sections40,
    ]
];

foreach ($adaptiveSets as $key => $data) {
    ExamSet::updateOrCreate(
        ['key' => $key],
        array_merge($data, [
            'skill' => 'adaptive',
            'is_published' => true,
        ])
    );
}

echo "Successfully updated 4 Adaptive Exam Sets to Full Simulation standard (40 questions, 60 minutes)!\n";
