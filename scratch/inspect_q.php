<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$q = App\Models\QuestionBank::find(70);
echo "Q70:\n";
print_r($q->toArray());

$q36 = App\Models\QuestionBank::find(36);
echo "\nQ36:\n";
print_r($q36->toArray());
