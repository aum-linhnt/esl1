<?php

namespace Database\Seeders\AiTutorDemo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiTutorIeltsLevelsDemoSeeder extends Seeder
{
    public const SEEDERS = [
        AiTutorIeltsFoundationDemoSeeder::class,
        AiTutorIeltsBand45DemoSeeder::class,
        AiTutorIeltsBand55DemoSeeder::class,
        AiTutorIeltsBand70DemoSeeder::class,
        AiTutorIeltsBand75DemoSeeder::class,
    ];

    public function run(): void
    {
        DB::transaction(function () {
            // Prepare every teacher before courses grant manager access.
            foreach (self::SEEDERS as $seeder) {
                app($seeder)->prepareDemoTeacher();
            }
            $this->call(self::SEEDERS);
        });
    }
}
