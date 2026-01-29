<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class RunDatabaseBackup extends Command
{
    protected $signature = 'telefleet:backup-database';

    protected $description = 'Create a database backup and cleanup old backups.';

    public function handle(DatabaseBackupService $backupService): int
    {
        $keep = (int) env('BACKUP_KEEP_COUNT', 7);

        try {
            $path = $backupService->createBackup();
            $deleted = $backupService->cleanup($keep);
            Log::channel('telefleet')->info('system.backup_scheduled', [
                'file' => basename($path),
                'deleted' => $deleted,
                'keep' => $keep,
            ]);
            $this->info('Backup created: ' . basename($path));
            if ($deleted > 0) {
                $this->info("Cleaned up {$deleted} old backups.");
            }
        } catch (Throwable $exception) {
            Log::channel('telefleet')->error('system.backup_failed', [
                'error' => $exception->getMessage(),
            ]);
            $this->error('Backup failed: ' . $exception->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
