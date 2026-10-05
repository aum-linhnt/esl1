<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$courses = App\Models\Course::with('lessons.activities')->get();
foreach ($courses as $c) {
    echo "Course {$c->id}: {$c->title}\n";
    foreach ($c->lessons as $l) {
        echo "  Lesson {$l->id}: {$l->title} (Activities: {$l->activities->count()})\n";
        foreach ($l->activities as $a) {
            echo "    Act {$a->id} [{$a->type}]: {$a->title} (passing: {$a->passing_grade}, comp_type: {$a->completion_type})\n";
        }
    }
}

$users = App\Models\User::all();
echo "Users count: " . $users->count() . "\n";
foreach ($users as $u) {
    echo "User {$u->id}: {$u->name} ({$u->email})\n";
}
