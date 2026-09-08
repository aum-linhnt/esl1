<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$service = app(App\Services\AdaptiveTestingService::class);
$session = $service->initializeSession($user->id, 'B1');

$controller = app(App\Http\Controllers\PracticeController::class);
$req = new Illuminate\Http\Request(['session_id' => $session->id]);
$req->setUserResolver(fn() => $user);

$res = $controller->fetchNextQuestion($req);
$data = json_decode($res->getContent(), true);
echo "Fetched Q: ID {$data['question']['id']} | Skill: {$data['question']['skill']} | Audio: {$data['question']['audio_url']}\n";

// Let's also test a reading question
$qReading = App\Models\QuestionBank::where('skill', 'reading')->first();
$session->update(['question_history' => [1, 2, 3]]); // step 4 = reading
$resR = $controller->fetchNextQuestion($req);
$dataR = json_decode($resR->getContent(), true);
echo "Fetched Reading Q: ID {$dataR['question']['id']} | Skill: {$dataR['question']['skill']} | Passage Title: {$dataR['question']['passage_title']} | Passage len: " . strlen($dataR['question']['passage'] ?? '') . "\n";
