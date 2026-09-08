<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$invalid = App\Models\AdaptiveTestSession::whereRaw('correct_count > total_questions_answered')->get();
echo "Found " . $invalid->count() . " sessions with correct_count > total_questions_answered\n";
foreach ($invalid as $inv) {
    echo "Fixing session #{$inv->id}: was correct_count={$inv->correct_count}\n";
    $actualCorrect = !empty($inv->answers_history) ? collect($inv->answers_history)->where('is_correct', true)->count() : 0;
    $inv->update(['correct_count' => $actualCorrect]);
    echo "  Fixed to {$actualCorrect}\n";
}
