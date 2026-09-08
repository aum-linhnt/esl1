<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ExamSet;

$adaptiveSets = [
    [
        'key' => 'adaptive_cefr_placement',
        'title' => 'Thi Thích Ứng CEFR - Khảo Thí Định Vị Năng Lực Đầu Vào 4 Kỹ Năng',
        'skill' => 'adaptive',
        'difficulty' => 'Mixed (A1-C1)',
        'question_count' => 10,
        'duration_minutes' => 20,
        'reward_coins' => 20,
        'description' => 'Bài thi thích ứng định vị năng lực chuẩn khung Châu Âu CEFR. Thuật toán CAT tự động phân nhánh độ khó theo từng câu trả lời thực tế của bạn, bao quát trọn vẹn cả 4 kỹ năng Nghe, Đọc, Viết, Nói.',
        'sections' => [
            'listening' => ['name' => 'Nghe hiểu', 'count' => 3],
            'reading' => ['name' => 'Đọc hiểu', 'count' => 3],
            'writing' => ['name' => 'Viết luận/thư', 'count' => 2],
            'speaking' => ['name' => 'Nói trực tiếp', 'count' => 2],
        ],
        'is_published' => true,
    ],
    [
        'key' => 'adaptive_vstep_4skills',
        'title' => 'Thi Thích Ứng VSTEP 4 Kỹ Năng - Mô Phỏng Khảo Thí Bậc 3 đến Bậc 5',
        'skill' => 'adaptive',
        'difficulty' => 'B1 - C1',
        'question_count' => 10,
        'duration_minutes' => 25,
        'reward_coins' => 25,
        'description' => 'Mô phỏng cấu trúc khảo thí VSTEP theo chuẩn đề thi của Bộ Giáo dục & Đào tạo. Tự động thích ứng để xác định chính xác bạn đạt Bậc 3 (B1), Bậc 4 (B2) hay Bậc 5 (C1).',
        'sections' => [
            'listening' => ['name' => 'Nghe VSTEP', 'count' => 3],
            'reading' => ['name' => 'Đọc VSTEP', 'count' => 3],
            'writing' => ['name' => 'Viết VSTEP', 'count' => 2],
            'speaking' => ['name' => 'Nói VSTEP', 'count' => 2],
        ],
        'is_published' => true,
    ],
    [
        'key' => 'adaptive_ielts_fasttrack',
        'title' => 'Thi Thích Ứng IELTS on Computer - Fast-Track Diagnostic Band 4.0 - 8.5',
        'skill' => 'adaptive',
        'difficulty' => 'Band 4.0 - 8.5',
        'question_count' => 10,
        'duration_minutes' => 25,
        'reward_coins' => 30,
        'description' => 'Bộ đề thi thích ứng ứng dụng chuẩn đề IELTS Academic. Chẩn đoán nhanh điểm mạnh và điểm cần cải thiện của cả 4 kỹ năng trong thời gian tối ưu 25 phút.',
        'sections' => [
            'listening' => ['name' => 'IELTS Listening', 'count' => 3],
            'reading' => ['name' => 'IELTS Reading', 'count' => 3],
            'writing' => ['name' => 'IELTS Writing', 'count' => 2],
            'speaking' => ['name' => 'IELTS Speaking', 'count' => 2],
        ],
        'is_published' => true,
    ],
    [
        'key' => 'adaptive_business_pro',
        'title' => 'Thi Thích Ứng Tiếng Anh Thương Mại & Công Sở (Business English CAT)',
        'skill' => 'adaptive',
        'difficulty' => 'B1 - C1',
        'question_count' => 10,
        'duration_minutes' => 20,
        'reward_coins' => 20,
        'description' => 'Đánh giá năng lực tiếng Anh trong môi trường làm việc chuyên nghiệp: nghe thông báo công sở, đọc báo cáo kinh doanh, viết email thương mại và thuyết trình giải pháp.',
        'sections' => [
            'listening' => ['name' => 'Business Listening', 'count' => 3],
            'reading' => ['name' => 'Business Reading', 'count' => 3],
            'writing' => ['name' => 'Business Writing', 'count' => 2],
            'speaking' => ['name' => 'Business Speaking', 'count' => 2],
        ],
        'is_published' => true,
    ],
];

foreach ($adaptiveSets as $set) {
    ExamSet::updateOrCreate(['key' => $set['key']], $set);
}

echo "Successfully seeded 4 standard Adaptive Exam Sets!\n";
