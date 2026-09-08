<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$session = App\Models\AdaptiveTestSession::latest()->first();
$user = App\Models\User::first();

try {
    $html = view('practice.player', [
        'session' => $session,
        'user' => $user
    ])->render();
    echo "RENDER SUCCESSFUL! HTML length: " . strlen($html) . " bytes\n";
} catch (\Throwable $e) {
    echo "RENDER ERROR: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
