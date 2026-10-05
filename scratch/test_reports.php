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

echo "Testing ReportController methods...\n";

try {
    echo "1. Testing index()...\n";
    $res = $controller->index();
    echo "   [OK] index returned view: " . $res->name() . "\n";
} catch (\Throwable $e) {
    echo "   [FAIL] index: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "2. Testing activityGrades()...\n";
    $request = Request::create('/admin/reports/activity-grades', 'GET');
    $res = $controller->activityGrades($request);
    echo "   [OK] activityGrades returned view: " . $res->name() . "\n";
    echo "   Total graded: " . $res->getData()['totalGraded'] . "\n";
} catch (\Throwable $e) {
    echo "   [FAIL] activityGrades: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "3. Testing grades()...\n";
    $request = Request::create('/admin/reports/grades', 'GET');
    $res = $controller->grades($request);
    echo "   [OK] grades returned view: " . $res->name() . "\n";
} catch (\Throwable $e) {
    echo "   [FAIL] grades: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "4. Testing completions()...\n";
    $request = Request::create('/admin/reports/completions', 'GET');
    $res = $controller->completions($request);
    echo "   [OK] completions returned view: " . $res->name() . "\n";
    $data = $res->getData();
    echo "   Selected Course: " . ($data['selectedCourse']?->title ?? 'None') . "\n";
    echo "   Total Enrolled: " . $data['totalEnrolled'] . "\n";
    echo "   Total Activities: " . $data['totalActivities'] . "\n";
    echo "   Course Completion Rate: " . $data['overallCourseCompletionRate'] . "%\n";
    echo "   Matrix rows: " . $data['learnerMatrix']->count() . "\n";
} catch (\Throwable $e) {
    echo "   [FAIL] completions: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "5. Testing exportCsv('activity-grades')...\n";
    $request = Request::create('/admin/reports/export/activity-grades', 'GET');
    $res = $controller->exportCsv($request, 'activity-grades');
    echo "   [OK] exportCsv('activity-grades') status: " . $res->getStatusCode() . "\n";

    echo "5b. Testing exportCsv('activity-grades') matrix format...\n";
    $requestMatrix = Request::create('/admin/reports/export/activity-grades?format=matrix&course_id=1', 'GET');
    $resMatrix = $controller->exportCsv($requestMatrix, 'activity-grades');
    echo "   [OK] exportCsv('activity-grades', format=matrix) status: " . $resMatrix->getStatusCode() . "\n";
} catch (\Throwable $e) {
    echo "   [FAIL] exportCsv('activity-grades'): " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

try {
    echo "6. Testing exportCsv('completions')...\n";
    $request = Request::create('/admin/reports/export/completions', 'GET');
    $res = $controller->exportCsv($request, 'completions');
    echo "   [OK] exportCsv('completions') status: " . $res->getStatusCode() . "\n";
} catch (\Throwable $e) {
    echo "   [FAIL] exportCsv('completions'): " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "All tests finished.\n";
