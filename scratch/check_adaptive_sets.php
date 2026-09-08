<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$exams = App\Models\ExamSet::where('skill', 'adaptive')->get();
echo "Adaptive exams count: " . $exams->count() . "\n";
foreach ($exams as $e) {
    echo "- ID: {$e->id}, Key: {$e->key}, Title: {$e->title}, Diff: {$e->difficulty}, Duration: {$e->duration_minutes}m, Questions: {$e->question_count}, Published: {$e->is_published}\n";
}

$allExamsCount = App\Models\ExamSet::count();
echo "Total exam sets in db: {$allExamsCount}\n";
