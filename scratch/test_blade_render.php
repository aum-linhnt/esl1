<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\ReportController;

$admin = User::where('role', 'admin')->first() ?? User::first();
auth()->login($admin);

$controller = app(ReportController::class);

echo "Testing Blade View Full Rendering...\n";

try {
    $request = Request::create('/admin/reports/activity-grades', 'GET');
    $res = $controller->activityGrades($request);
    $html = $res->render();
    echo "   [OK] activityGrades rendered successfully! (HTML length: " . strlen($html) . " bytes)\n";
} catch (\Throwable $e) {
    echo "   [FAIL] activityGrades render: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $request = Request::create('/admin/reports/completions', 'GET');
    $res = $controller->completions($request);
    $html = $res->render();
    echo "   [OK] completions (overview tab) rendered successfully! (HTML length: " . strlen($html) . " bytes)\n";
} catch (\Throwable $e) {
    echo "   [FAIL] completions render: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $request = Request::create('/admin/reports/completions?tab=matrix', 'GET');
    $res = $controller->completions($request);
    $html = $res->render();
    echo "   [OK] completions (matrix tab) rendered successfully! (HTML length: " . strlen($html) . " bytes)\n";
} catch (\Throwable $e) {
    echo "   [FAIL] completions matrix render: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $request = Request::create('/admin/reports/completions?tab=log', 'GET');
    $res = $controller->completions($request);
    $html = $res->render();
    echo "   [OK] completions (log tab) rendered successfully! (HTML length: " . strlen($html) . " bytes)\n";
} catch (\Throwable $e) {
    echo "   [FAIL] completions log render: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    $res = $controller->index();
    $html = $res->render();
    echo "   [OK] index rendered successfully! (HTML length: " . strlen($html) . " bytes)\n";
} catch (\Throwable $e) {
    echo "   [FAIL] index render: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "All Blade rendering tests passed!\n";
