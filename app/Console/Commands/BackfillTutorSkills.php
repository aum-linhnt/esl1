<?php

namespace App\Console\Commands;

use App\Services\Learning\SkillSnapshots;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class BackfillTutorSkills extends Command
{
    protected $signature = 'ai-tutor:backfill-skills';
    protected $description = 'Import completed AI Tutor Writing results into learner skill history without AI requests.';

    public function handle(SkillSnapshots $snapshots): int
    {
        if (! $snapshots->ready()) { $this->error('Run the learner skill snapshot migration first.'); return self::FAILURE; }
        if (! Schema::hasTable('tutor_ai_writing_submissions')) return self::SUCCESS;
        DB::table('tutor_ai_writing_submissions')->where('status', 'completed')->orderBy('id')->chunkById(100, function ($rows) use ($snapshots) {
            foreach ($rows as $row) $snapshots->writing($row->id);
        });
        $this->info('Completed Writing results imported; existing snapshots were preserved.');
        return self::SUCCESS;
    }
}
