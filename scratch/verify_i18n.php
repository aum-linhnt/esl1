<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;

echo "=== START VERIFYING LANGUAGE SYSTEM ===\n\n";

// 1. Check Route
$hasRoute = Route::has('language.switch');
echo "1. Route 'language.switch': " . ($hasRoute ? "FOUND (PASS)" : "MISSING (FAIL)") . "\n";
assert($hasRoute, "Route 'language.switch' must exist");

// 2. Test English Translations
App::setLocale('en');
$enDashboard = __('messages.nav.dashboard');
$enStreak = __('messages.common.streak');
echo "2. Locale 'en':\n";
echo "   - nav.dashboard: {$enDashboard}\n";
echo "   - common.streak: {$enStreak}\n";
assert($enDashboard === 'Dashboard', "English dashboard translation mismatch");

// 3. Test Vietnamese Translations
App::setLocale('vi');
$viDashboard = __('messages.nav.dashboard');
$viStreak = __('messages.common.streak');
echo "3. Locale 'vi':\n";
echo "   - nav.dashboard: {$viDashboard}\n";
echo "   - common.streak: {$viStreak}\n";
assert($viDashboard === 'Trang chủ', "Vietnamese dashboard translation mismatch");

// 4. Test View Compilation
try {
    $rendered = view('components.language-switcher')->render();
    echo "4. Render <x-language-switcher />: SUCCESS (" . strlen($rendered) . " bytes)\n";
} catch (\Throwable $e) {
    echo "4. Render <x-language-switcher />: ERROR - " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== ALL LANGUAGE CHECKS PASSED 100%! ===\n";
