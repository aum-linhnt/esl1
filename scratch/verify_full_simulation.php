<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\QuestionBank;
use App\Models\ExamSet;
use App\Services\AdaptiveTestingService;
use Illuminate\Support\Facades\View;

echo "=== 1. CHECK ADAPTIVE TESTING SERVICE ===\n";
$service = $app->make(AdaptiveTestingService::class);
echo "TOTAL_TEST_QUESTIONS = " . AdaptiveTestingService::TOTAL_TEST_QUESTIONS . "\n";

$skillSteps = [
    1 => 'listening',
    12 => 'listening',
    13 => 'reading',
    26 => 'reading',
    27 => 'writing',
    33 => 'writing',
    34 => 'speaking',
    40 => 'speaking',
];

$allStepsOk = true;
foreach ($skillSteps as $step => $expectedSkill) {
    // reflection to call protected getSkillForStep
    $ref = new ReflectionMethod(AdaptiveTestingService::class, 'getSkillForStep');
    $ref->setAccessible(true);
    $skill = $ref->invoke($service, $step);
    if ($skill !== $expectedSkill) {
        echo "FAIL at step $step: expected $expectedSkill, got $skill\n";
        $allStepsOk = false;
    }
}
if ($allStepsOk) {
    echo "SUCCESS: getSkillForStep mapped correctly for all step boundaries (1-12 L, 13-26 R, 27-33 W, 34-40 S)!\n";
}

echo "\n=== 2. CHECK QUESTION BANK INVENTORY ===\n";
$counts = QuestionBank::select('skill', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
    ->groupBy('skill')
    ->pluck('total', 'skill')
    ->toArray();
print_r($counts);
echo "Total questions in bank: " . QuestionBank::count() . "\n";

echo "\n=== 3. CHECK EXAM SETS ===\n";
$sets = ExamSet::where('key', 'like', 'adaptive_%')->get();
foreach ($sets as $s) {
    echo "Set: {$s->key} | title: {$s->title} | question_count: {$s->question_count} | duration: {$s->duration_minutes}m\n";
}

echo "\n=== 4. CHECK BLADE VIEWS COMPILATION ===\n";
try {
    $user = \App\Models\User::first();
    if (!$user) {
        $user = new \App\Models\User(['id' => 1, 'name' => 'Demo', 'email' => 'demo@esl.test', 'avatar' => null]);
    }
    auth()->login($user);
    $request = \Illuminate\Http\Request::create('/practice', 'GET');
    $request->setUserResolver(fn() => $user);

    $controller = $app->make(\App\Http\Controllers\PracticeController::class);
    $response = $controller->index($request);
    $response->render();
    echo "SUCCESS: PracticeController@index rendered successfully!\n";
} catch (\Throwable $e) {
    echo "ERROR in PracticeController@index: " . $e->getMessage() . "\n";
}

try {
    // Check practice.player
    $dummySession = (object)[
        'id' => 999,
        'exam_set_id' => $sets->first()->id,
        'examSet' => $sets->first(),
        'ability_theta' => 0.5,
        'current_step' => 1,
        'status' => 'in_progress',
        'started_at' => now(),
        'created_at' => now(),
        'duration_minutes' => 60,
    ];
    $firstQuestion = QuestionBank::first();
    View::make('practice.player', [
        'session' => $dummySession,
        'examSet' => $sets->first(),
        'firstQuestion' => $firstQuestion,
        'initialTheta' => 0.5,
        'totalQuestions' => 40,
        'currentStep' => 1,
    ])->render();
    echo "SUCCESS: practice.player compiles without error.\n";
} catch (\Throwable $e) {
    echo "ERROR in practice.player: " . $e->getMessage() . "\n";
}

try {
    // Check practice.adaptive_scorecard
    $existingSession = \App\Models\AdaptiveTestSession::latest()->first();
    if ($existingSession) {
        $req = \Illuminate\Http\Request::create('/practice/adaptive/' . $existingSession->id . '/scorecard', 'GET');
        $sessionUser = \App\Models\User::find($existingSession->user_id) ?? \App\Models\User::first();
        $req->setUserResolver(fn() => $sessionUser);
        $res = $controller->adaptiveScorecard($req, $existingSession->id);
        $res->render();
        echo "SUCCESS: PracticeController@adaptiveScorecard rendered successfully with session #{$existingSession->id}!\n";
    } else {
        echo "NOTE: No existing session found to test scorecard.\n";
    }
} catch (\Throwable $e) {
    echo "ERROR in PracticeController@scorecard: " . $e->getMessage() . "\n";
}

echo "\n=== ALL CHECKS FINISHED ===\n";
