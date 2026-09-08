<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use App\Models\AdaptiveTestSession;
use Illuminate\Support\Facades\View;

$user = User::first();
if (!$user) {
    echo "No user found!\n";
    exit(1);
}
\Illuminate\Support\Facades\Auth::login($user);

// 1. Test Practice Index view rendering
$controller = $app->make(App\Http\Controllers\PracticeController::class);
$request = Illuminate\Http\Request::create('/practice?skill=adaptive', 'GET');
$request->setUserResolver(fn() => $user);

try {
    $res = $controller->index($request);
    echo "practice.index view rendered successfully! Length: " . strlen($res->render()) . "\n";
} catch (\Throwable $e) {
    echo "ERROR in practice.index: " . $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile() . "\n";
}

// 2. Test Player view rendering
$session = AdaptiveTestSession::where('user_id', $user->id)->latest()->first();
if ($session) {
    try {
        $reqPlayer = Illuminate\Http\Request::create('/practice/player/' . $session->id, 'GET');
        $reqPlayer->setUserResolver(fn() => $user);
        $resPlayer = $controller->player($reqPlayer, $session->id);
        echo "practice.player view rendered successfully! Length: " . strlen($resPlayer->render()) . "\n";
    } catch (\Throwable $e) {
        echo "ERROR in practice.player: " . $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile() . "\n";
    }

    // 3. Test Scorecard view rendering
    try {
        $reqScore = Illuminate\Http\Request::create('/practice/adaptive/' . $session->id . '/scorecard', 'GET');
        $reqScore->setUserResolver(fn() => $user);
        $resScore = $controller->adaptiveScorecard($reqScore, $session->id);
        echo "practice.adaptive_scorecard view rendered successfully! Length: " . strlen($resScore->render()) . "\n";
    } catch (\Throwable $e) {
        echo "ERROR in practice.adaptive_scorecard: " . $e->getMessage() . " on line " . $e->getLine() . " of " . $e->getFile() . "\n";
    }
} else {
    echo "No adaptive session found for user.\n";
}
