<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$readingQuestions = App\Models\QuestionBank::where('skill', 'reading')->get();
$hasPassage = 0;
$noPassage = 0;
foreach ($readingQuestions as $q) {
    $meta = $q->meta_data;
    $p = $meta['passage_content'] ?? $meta['passage'] ?? null;
    if ($p) {
        $hasPassage++;
    } else {
        $noPassage++;
        echo "No passage: ID {$q->id} - Diff: {$q->difficulty} - Text: {$q->question_text}\n";
    }
}
echo "Total Reading: {$readingQuestions->count()} | With passage: {$hasPassage} | Without passage: {$noPassage}\n";
