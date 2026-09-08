<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = app(App\Services\AdaptiveTestingService::class);
$session = $service->initializeSession(1, 'A1');
echo "Session initialized: {$session->id}\n";

for ($step = 1; $step <= 10; $step++) {
    $q = $service->getNextQuestion($session->id);
    if (!$q) {
        echo "Step {$step}: NULL question!\n";
        break;
    }
    $targetSkill = $service->getSkillForStep($step);
    echo "Step {$step} (Target: {$targetSkill}): ID {$q->id} | Skill: {$q->skill} | Diff: {$q->difficulty} | Type: {$q->question_type} | Text: " . substr($q->question_text, 0, 50) . "...\n";
    // Simulate answer
    $service->processAnswer($session->id, $q->id, 'A');
    $session->refresh();
}
