<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$exams = App\Models\ExamSet::all();
foreach ($exams as $e) {
    echo "ID: {$e->id} | Key: {$e->key} | Title: {$e->title} | Skill: {$e->skill} | QCount: {$e->question_count} | QIds: " . json_encode($e->question_ids) . "\n";
}
