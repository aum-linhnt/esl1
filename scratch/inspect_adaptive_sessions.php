<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sessions = App\Models\AdaptiveTestSession::latest()->take(10)->get();
foreach ($sessions as $s) {
    echo "ID: {$s->id} | User: {$s->user_id} | Status: {$s->status} | Answered: {$s->total_questions_answered} | Correct: {$s->correct_count} | Score: {$s->score} | Level: {$s->final_level}\n";
    if (!empty($s->answers_history)) {
        $actualCorrect = collect($s->answers_history)->where('is_correct', true)->count();
        $totalH = count($s->answers_history);
        echo "   -> In history: {$actualCorrect}/{$totalH} correct\n";
    }
}
