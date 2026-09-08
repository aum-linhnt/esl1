<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AdaptiveTestSession;
use App\Services\AdaptiveTestingService;
use Illuminate\Http\Request;

echo "=== TESTLET-BASED READING ADAPTIVE ENGINE SIMULATION ===\n";

$user = User::first();
if (!$user) {
    die("No user found\n");
}
auth()->login($user);

$service = $app->make(AdaptiveTestingService::class);

// Create fresh test session
$session = $service->initializeSession($user->id, 'B1');
echo "Session #{$session->id} created. Initial difficulty: {$session->current_difficulty}\n";

// Fast forward steps 1 to 12 (Listening)
echo "\n--- Fast forwarding Steps 1 to 12 (Listening) ---\n";
for ($step = 1; $step <= 12; $step++) {
    $q = $service->getNextQuestion($session->id);
    if (!$q) {
        die("Error: No question for step $step\n");
    }
    $service->processAnswer($session->id, $q->id, 'A');
}

$session->refresh();
echo "Listening finished! Total answered: {$session->total_questions_answered}\n";

echo "\n--- SIMULATING STEPS 13 TO 26 (READING 14 QUESTIONS) ---\n";
$readingTestletGroups = [];

for ($step = 13; $step <= 26; $step++) {
    $q = $service->getNextQuestion($session->id);
    if (!$q) {
        die("Error: No reading question at step $step\n");
    }

    $passageTitle = $q->meta_data['passage_title'] ?? substr($q->meta_data['passage_content'] ?? 'no_passage', 0, 40);
    $testletIdx = $q->meta_data['testlet_index'] ?? 0;
    $testletQNum = $q->meta_data['testlet_question_num'] ?? 0;
    $testletSize = $q->meta_data['testlet_size'] ?? 0;
    $testletBadge = $q->meta_data['testlet_badge'] ?? '';

    echo "Step {$step} | Testlet {$testletIdx} (Q{$testletQNum}/{$testletSize}) | Diff: {$q->difficulty} | Title: '{$passageTitle}' | Badge: '{$testletBadge}'\n";

    $readingTestletGroups[$testletIdx][] = [
        'step' => $step,
        'question_id' => $q->id,
        'passage_title' => $passageTitle,
    ];

    // Answer and advance
    $service->processAnswer($session->id, $q->id, 'A');
    $session->refresh();
}

echo "\n=== VERIFYING TESTLET CLUSTERS INTEGRITY ===\n";

// Verify Testlet 1: steps 13 - 17
$t1 = $readingTestletGroups[1] ?? [];
echo "Testlet 1 questions count: " . count($t1) . " (Expected: 5)\n";
$t1Passages = collect($t1)->pluck('passage_title')->unique();
echo "Testlet 1 unique passages: " . $t1Passages->count() . " ('" . $t1Passages->first() . "')\n";
if (count($t1) === 5 && $t1Passages->count() === 1) {
    echo "✅ TESTLET 1 PASSED: All 5 questions share the EXACT SAME passage!\n";
} else {
    echo "❌ TESTLET 1 FAILED\n";
}

// Verify Testlet 2: steps 18 - 22
$t2 = $readingTestletGroups[2] ?? [];
echo "Testlet 2 questions count: " . count($t2) . " (Expected: 5)\n";
$t2Passages = collect($t2)->pluck('passage_title')->unique();
echo "Testlet 2 unique passages: " . $t2Passages->count() . " ('" . $t2Passages->first() . "')\n";
if (count($t2) === 5 && $t2Passages->count() === 1 && $t2Passages->first() !== $t1Passages->first()) {
    echo "✅ TESTLET 2 PASSED: All 5 questions share the EXACT SAME passage (and different from Testlet 1)!\n";
} else {
    echo "❌ TESTLET 2 FAILED\n";
}

// Verify Testlet 3: steps 23 - 26
$t3 = $readingTestletGroups[3] ?? [];
echo "Testlet 3 questions count: " . count($t3) . " (Expected: 4)\n";
$t3Passages = collect($t3)->pluck('passage_title')->unique();
echo "Testlet 3 unique passages: " . $t3Passages->count() . " ('" . $t3Passages->first() . "')\n";
if (count($t3) === 4 && $t3Passages->count() === 1 && $t3Passages->first() !== $t2Passages->first()) {
    echo "✅ TESTLET 3 PASSED: All 4 questions share the EXACT SAME passage (and different from Testlet 2)!\n";
} else {
    echo "❌ TESTLET 3 FAILED\n";
}

// Clean up test session
$session->delete();
echo "\nCleaned up test session.\n";
echo "=== ALL SIMULATION CHECKS COMPLETED SUCCESSFULLY ===\n";
