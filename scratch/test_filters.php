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

echo "Testing filters...\n";

// Filter activityGrades by result=passed
$req = Request::create('/admin/reports/activity-grades?result=passed', 'GET');
$res = $controller->activityGrades($req);
echo "1. Filter result=passed: " . $res->getData()['activityGrades']->total() . " items\n";

// Filter activityGrades by result=failed
$req = Request::create('/admin/reports/activity-grades?result=failed', 'GET');
$res = $controller->activityGrades($req);
echo "2. Filter result=failed: " . $res->getData()['activityGrades']->total() . " items\n";

// Filter activityGrades by course_id=1
$req = Request::create('/admin/reports/activity-grades?course_id=1', 'GET');
$res = $controller->activityGrades($req);
echo "3. Filter course_id=1: " . $res->getData()['activityGrades']->total() . " items\n";

// Filter completions by course_id=2
$req = Request::create('/admin/reports/completions?course_id=2', 'GET');
$res = $controller->completions($req);
$d = $res->getData();
echo "4. Completions course_id=2: Course " . $d['selectedCourse']->title . ", Enrolled: " . $d['totalEnrolled'] . ", Activities: " . $d['totalActivities'] . "\n";

echo "Filter tests passed!\n";
