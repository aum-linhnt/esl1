<?php

namespace Database\Seeders\AiTutorDemo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiTutorToeicTargetsDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Update existing course titles without refreshing demo grades or conversations.
        DB::transaction(function () {
            foreach (AiTutorToeicLevelsDemoSeeder::SEEDERS as $seeder) {
                $instance = app($seeder);
                if ($this->command) $instance->setCommand($this->command);
                $instance->updateCourseTargets();
            }
        });
    }
}
