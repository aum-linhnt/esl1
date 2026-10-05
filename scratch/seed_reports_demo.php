<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Activity;
use App\Models\ActivityCompletion;
use App\Models\QuizAttempt;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

echo "Seeding demo learners and completion/grades data...\n";

// 1. Create demo students if not existing
$demoStudents = [
    ['name' => 'Trần Mai Anh', 'email' => 'maianh.tran@gmail.com', 'username' => 'maianh_tran'],
    ['name' => 'Lê Hoàng Nam', 'email' => 'hoangnam.le@gmail.com', 'username' => 'hoangnam_le'],
    ['name' => 'Phạm Thu Trang', 'email' => 'thutrang.pham@gmail.com', 'username' => 'thutrang_pham'],
    ['name' => 'Đỗ Minh Khang', 'email' => 'minhkhang.do@gmail.com', 'username' => 'minhkhang_do'],
    ['name' => 'Vũ Phương Thảo', 'email' => 'phuongthao.vu@gmail.com', 'username' => 'phuongthao_vu'],
];

$studentUsers = [];
foreach ($demoStudents as $data) {
    $user = User::firstOrCreate(
        ['email' => $data['email']],
        [
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => Hash::make('password123'),
            'role' => 'student',
            'email_verified_at' => now(),
            'coins' => rand(50, 200),
            'xp' => rand(300, 1500),
        ]
    );
    $studentUsers[] = $user;
}

// Include Tuấn Linh
$tuanLinh = User::where('email', 'tuanlinh@fsel.vn')->first();
if ($tuanLinh) {
    $studentUsers[] = $tuanLinh;
}

$courses = Course::with('lessons.activities')->get();

foreach ($courses as $course) {
    echo "Processing course: {$course->title}\n";
    $activities = $course->lessons->flatMap->activities;

    foreach ($studentUsers as $idx => $user) {
        // Enroll student
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'status' => 'active',
                'enrolled_at' => Carbon::now()->subDays(rand(10, 30)),
                'progress_percentage' => 0,
            ]
        );

        // Completion probability varies per student to make data realistic
        $prob = match($idx % 4) {
            0 => 0.95, // high achiever
            1 => 0.75, // medium
            2 => 0.50, // moderate
            default => 0.30 // drop-out or beginner
        };

        $completedCount = 0;

        foreach ($activities as $actIdx => $act) {
            // Drop-off rate increases as activity index increases
            $actProb = $prob - ($actIdx * 0.03);
            $shouldComplete = (mt_rand(1, 100) / 100) <= $actProb;

            if ($shouldComplete) {
                $completedCount++;
                $timeSpent = rand(120, 900); // 2 to 15 mins
                $score = match($act->type) {
                    'quiz' => rand(65, 100),
                    'assignment' => rand(70, 98),
                    'ai_speaking' => rand(60, 95),
                    'ai_writing' => rand(65, 95),
                    default => 100,
                };
                $completedAt = Carbon::now()->subDays(rand(1, 25))->subHours(rand(1, 12));

                ActivityCompletion::updateOrCreate(
                    ['user_id' => $user->id, 'activity_id' => $act->id],
                    [
                        'lesson_id' => $act->lesson_id,
                        'score' => $score,
                        'max_score' => 100,
                        'time_spent_seconds' => $timeSpent,
                        'completed_at' => $completedAt,
                    ]
                );

                // If quiz, also create QuizAttempt
                if ($act->type === 'quiz') {
                    QuizAttempt::updateOrCreate(
                        ['user_id' => $user->id, 'activity_id' => $act->id, 'attempt_number' => 1],
                        [
                            'status' => 'completed',
                            'score' => $score,
                            'max_score' => 100,
                            'percentage' => $score,
                            'is_passed' => $score >= ($act->passing_grade ?? 80),
                            'time_spent_seconds' => $timeSpent,
                            'started_at' => $completedAt->copy()->subSeconds($timeSpent),
                            'completed_at' => $completedAt,
                        ]
                    );
                }
            }
        }

        $progressPct = count($activities) > 0 ? round(($completedCount / count($activities)) * 100) : 0;
        $enrollment->update([
            'progress_percentage' => $progressPct,
            'status' => $progressPct >= 100 ? 'completed' : 'active',
            'completed_at' => $progressPct >= 100 ? Carbon::now() : null,
        ]);
    }
}

echo "Done! ActivityCompletions count: " . ActivityCompletion::count() . "\n";
echo "QuizAttempts count: " . QuizAttempt::count() . "\n";
echo "Enrollments count: " . Enrollment::count() . "\n";
