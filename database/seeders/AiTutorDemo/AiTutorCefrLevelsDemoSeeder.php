<?php

namespace Database\Seeders\AiTutorDemo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiTutorCefrLevelsDemoSeeder extends Seeder
{
    public const SEEDERS = [
        AiTutorCefrA1DemoSeeder::class,
        AiTutorCefrA2DemoSeeder::class,
        AiTutorCefrB1DemoSeeder::class,
        AiTutorCefrB2DemoSeeder::class,
        AiTutorCefrC1DemoSeeder::class,
        AiTutorCefrC2DemoSeeder::class,
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::SEEDERS as $seeder) {
                app($seeder)->prepareDemoTeacher();
            }
            $this->call(self::SEEDERS);
        });
    }
}
