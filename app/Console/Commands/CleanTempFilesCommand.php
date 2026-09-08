<?php

namespace App\Console\Commands;

use App\Services\Storage\FileStorageService;
use Illuminate\Console\Command;

class CleanTempFilesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:clean-temp {--hours=24 : Delete temporary files older than this number of hours}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired or abandoned temporary uploaded files from storage and database';

    /**
     * Execute the console command.
     */
    public function handle(FileStorageService $fileStorageService): int
    {
        $hours = (int) $this->option('hours');
        $this->info("Scanning and cleaning temporary files older than {$hours} hours...");

        $deletedCount = $fileStorageService->cleanupTemp($hours);

        $this->info("Cleaned up {$deletedCount} temporary file(s) successfully.");

        return Command::SUCCESS;
    }
}
