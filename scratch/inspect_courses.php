<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$courses = App\Models\Course::where('is_published', true)->get(['id', 'title', 'level', 'thumbnail']);
echo "Published courses:\n";
foreach ($courses as $c) {
    echo "ID {$c->id} | Level: {$c->level} | Title: {$c->title}\n";
}
