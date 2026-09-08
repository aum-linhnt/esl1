<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$exams = App\Models\ExamSet::where('skill', 'adaptive')->get();
foreach ($exams as $e) {
    echo "=== {$e->key} ===\n";
    echo "Title: {$e->title}\n";
    echo "Sections: " . json_encode($e->sections, JSON_UNESCAPED_UNICODE) . "\n";
    echo "Desc: {$e->description}\n";
}
