<?php

namespace App\Console\Commands;

use App\Services\Backup\DatabaseBackupException;
use App\Services\Backup\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'db:backup {--connection= : Database connection; defaults to the application connection}';

    protected $description = 'Create a consistent, compressed MySQL backup in private storage';

    public function handle(DatabaseBackupService $backups): int
    {
        try {
            $path = $backups->create($this->option('connection'));
            $this->info('Backup thành công: '.$path);

            return self::SUCCESS;
        } catch (Throwable $error) {
            // Database errors may contain credentials or row contents: do not print them.
            Log::error('Database backup failed', ['exception_type' => $error::class]);
            $this->error($error instanceof DatabaseBackupException ? $error->getMessage() : 'Backup thất bại; không tạo file hoàn chỉnh. Kiểm tra kết nối và quyền ghi thư mục.');

            return self::FAILURE;
        }
    }
}
