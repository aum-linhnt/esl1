<?php

namespace Database\Seeders\AiTutorDemo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiTutorToeicLevelsDemoSeeder extends Seeder
{
    public const SEEDERS = [
        AiTutorToeicStarterDemoSeeder::class,
        AiTutorToeicFoundationDemoSeeder::class,
        AiTutorToeicIntermediateDemoSeeder::class,
        AiTutorToeicAdvancedDemoSeeder::class,
        AiTutorToeicIntensiveDemoSeeder::class,
    ];

    public function run(): void
    {
        DB::transaction(function () {
            // All demo teachers must exist before courses grant manager access.
            // Otherwise a second run adds later teachers to the earlier courses.
            foreach (self::SEEDERS as $seeder) {
                app($seeder)->prepareDemoTeacher();
            }
            $this->call(self::SEEDERS);
        });
    }
}
