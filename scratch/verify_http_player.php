<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$service = app(App\Services\AdaptiveTestingService::class);
$session = $service->initializeSession($user->id, 'B1');

auth()->login($user);
$request = Illuminate\Http\Request::create("/practice/player/{$session->id}", 'GET');
$request->setUserResolver(fn() => $user);

$response = $app->handle($request);
echo "HTTP Status: " . $response->getStatusCode() . "\n";
echo "Content length: " . strlen($response->getContent()) . " bytes\n";
