<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\QuestionBank;
use App\Models\ExamSet;

echo "--- Check QuestionBank Columns ---\n";
$columns = \Illuminate\Support\Facades\Schema::getColumnListing('question_banks');
print_r($columns);

echo "\n--- Find 'Renewable Energy' in QuestionBank ---\n";
$q36 = QuestionBank::find(36);
echo "ID 36 meta_data:\n" . json_encode($q36->meta_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
echo "ID 36 question_text: " . $q36->question_text . "\n";

echo "\n--- Check other Reading questions with passages ---\n";
$readingWithPassages = QuestionBank::where('skill', 'reading')
    ->get()
    ->filter(fn($q) => !empty($q->meta_data['passage']) || !empty($q->meta_data['passage_title']))
    ->groupBy(fn($q) => $q->meta_data['passage_title'] ?? substr($q->meta_data['passage'] ?? 'no_passage', 0, 50));

echo "Unique passage groups: " . $readingWithPassages->count() . "\n";
foreach ($readingWithPassages as $key => $group) {
    echo "- Group '$key': " . $group->count() . " questions (IDs: " . $group->pluck('id')->implode(', ') . "), diffs: " . $group->pluck('difficulty')->unique()->implode(', ') . "\n";
}

echo "\n--- Find 'Renewable Energy' in ExamSets ---\n";
$sets = ExamSet::where('sections', 'like', '%Renewable Energy%')->get();
echo "Count in ExamSets: " . $sets->count() . "\n";
foreach ($sets as $s) {
    echo "ExamSet ID: {$s->id}, key: {$s->key}, title: {$s->title}\n";
    if (is_array($s->sections)) {
        echo "Sections count: " . count($s->sections) . "\n";
        foreach ($s->sections as $idx => $sec) {
            echo "  Sec $idx: " . ($sec['title'] ?? $sec['name'] ?? 'no title') . " (questions: " . count($sec['questions'] ?? []) . ")\n";
        }
    }
}
