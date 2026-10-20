<?php

namespace Database\Seeders\AiTutorDemo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiTutorToeicSwLevelsDemoSeeder extends Seeder
{
    public const SEEDERS = [
        AiTutorToeicSwStarterDemoSeeder::class,
        AiTutorToeicSwFoundationDemoSeeder::class,
        AiTutorToeicSwIntermediateDemoSeeder::class,
        AiTutorToeicSwAdvancedDemoSeeder::class,
        AiTutorToeicSwIntensiveDemoSeeder::class,
    ];

    public function run(): void
    {
        DB::transaction(function () {
            // Prepare every teacher before granting manager access to each course.
            foreach (self::SEEDERS as $seeder) {
                app($seeder)->prepareDemoTeacher();
            }
            $this->call(self::SEEDERS);
        });
    }
}
