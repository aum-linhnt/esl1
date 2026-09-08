<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

Illuminate\Support\Facades\DB::statement("ALTER TABLE `adaptive_test_sessions` MODIFY COLUMN `current_difficulty` VARCHAR(10) NOT NULL DEFAULT 'A1'");
echo "Successfully altered current_difficulty column in adaptive_test_sessions to VARCHAR(10)!\n";
