<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$skills = ['listening', 'reading', 'writing', 'speaking'];
foreach ($skills as $sk) {
    $count = App\Models\QuestionBank::where('skill', $sk)->count();
    $diffs = App\Models\QuestionBank::where('skill', $sk)->selectRaw('difficulty, count(*) as c')->groupBy('difficulty')->pluck('c', 'difficulty')->toArray();
    echo "Skill: {$sk} | Total: {$count} | Diffs: " . json_encode($diffs) . "\n";
    $samples = App\Models\QuestionBank::where('skill', $sk)->take(2)->get(['id', 'question_text', 'question_type', 'difficulty', 'options', 'correct_answer']);
    foreach ($samples as $s) {
        echo "  [#{$s->id}] [{$s->difficulty}] [{$s->question_type}] " . substr($s->question_text, 0, 60) . "...\n";
    }
}
