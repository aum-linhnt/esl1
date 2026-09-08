<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- WRITING ---\n";
$wq = App\Models\QuestionBank::where('skill', 'writing')->get();
foreach ($wq as $q) {
    echo "ID {$q->id} | Diff: {$q->difficulty} | Title/Text: " . substr($q->question_text, 0, 70) . "\n";
    echo "  Meta: " . json_encode($q->meta_data) . "\n";
}

echo "\n--- SPEAKING ---\n";
$sq = App\Models\QuestionBank::where('skill', 'speaking')->get();
foreach ($sq as $q) {
    echo "ID {$q->id} | Diff: {$q->difficulty} | Title/Text: " . substr($q->question_text, 0, 70) . "\n";
    echo "  Meta: " . json_encode($q->meta_data) . "\n";
}
