<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$controller = app(App\Http\Controllers\PracticeController::class);
$service = app(App\Services\AdaptiveTestingService::class);

$session = $service->initializeSession($user->id, 'B1');
echo "Initialized session #{$session->id} at level {$session->current_difficulty}\n";

for ($step = 1; $step <= 10; $step++) {
    // 1. Fetch next question
    $req = new Illuminate\Http\Request(['session_id' => $session->id]);
    $req->setUserResolver(fn() => $user);
    $res = $controller->fetchNextQuestion($req);
    $data = json_decode($res->getContent(), true);

    if ($data['finished']) {
        echo "Finished early at step {$step}\n";
        break;
    }

    $q = $data['question'];
    echo "Step {$step}: [{$q['skill']}] [Diff: {$q['difficulty']}] [Type: {$q['question_type']}] ID {$q['id']}\n";
    if ($q['skill'] === 'reading') {
        echo "   -> Passage: " . substr($q['passage'] ?? '', 0, 50) . "... Title: {$q['passage_title']}\n";
    } elseif ($q['skill'] === 'listening') {
        echo "   -> Audio: {$q['audio_url']}\n";
    } elseif ($q['skill'] === 'writing') {
        echo "   -> Min words: {$q['min_words']}\n";
    } elseif ($q['skill'] === 'speaking') {
        echo "   -> Task type: {$q['task_type']} | Duration: {$q['duration']}\n";
    }

    // 2. Submit answer
    $answer = match($q['skill']) {
        'writing' => 'This is a sample student essay response with more than fifty words written to thoroughly answer all prompt questions and fulfill the exam criteria accurately and logically.',
        'speaking' => 'audio_recording_35s',
        default => ($q['options'][0] ?? 'A')
    };

    $subReq = new Illuminate\Http\Request([
        'session_id' => $session->id,
        'question_id' => $q['id'],
        'answer' => $answer
    ]);
    $subReq->setUserResolver(fn() => $user);
    $subRes = $controller->submitAdaptiveAnswer($subReq);
    $subData = json_decode($subRes->getContent(), true);

    echo "   -> Result: " . ($subData['is_correct'] ? 'Correct' : 'Wrong') . " | Score: {$subData['score']} | Correct Count: {$subData['correct_count']} | Diff: {$subData['current_difficulty']}\n";

    if ($subData['is_finished']) {
        echo "\n=== EXAM FINISHED ===\n";
        echo "Final Level: {$subData['final_level']}\n";
        echo "Correct Count: {$subData['correct_count']}/10\n";
        echo "Total Score: {$subData['score']}\n";
        echo "Skill Matrix:\n";
        foreach ($subData['skill_matrix'] as $sk => $m) {
            echo "   [{$sk}] Level: {$m['assessed_level']} | Correct: {$m['correct']}/{$m['total']} | Mastery: {$m['mastery_score']}%\n";
        }
        break;
    }
}
