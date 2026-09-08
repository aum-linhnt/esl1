<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$skills = ['listening', 'reading', 'writing', 'speaking'];
echo "Total questions: " . App\Models\QuestionBank::count() . "\n";
foreach ($skills as $s) {
    echo "- Skill: {$s} (Total: " . App\Models\QuestionBank::where('skill', $s)->count() . ")\n";
    $diffs = App\Models\QuestionBank::where('skill', $s)
        ->select('difficulty', \DB::raw('count(*) as count'))
        ->groupBy('difficulty')
        ->pluck('count', 'difficulty');
    foreach ($diffs as $d => $c) {
        echo "    * {$d}: {$c}\n";
    }
}
