<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanOldLogs extends Command
{
    protected $signature = 'telefleet:clean-logs {--days=14}';

    protected $description = 'Delete log files older than the specified number of days.';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $days = $days > 0 ? $days : 14;

        $logDir = storage_path('logs');
        if (! File::exists($logDir)) {
            $this->info('Log directory does not exist.');
            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days)->getTimestamp();
        $deleted = 0;

        foreach (File::files($logDir) as $file) {
            if (! str_ends_with($file->getFilename(), '.log')) {
                continue;
            }
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getRealPath());
                $deleted++;
            }
        }

        Log::channel('telefleet')->info('system.logs_cleaned', [
            'days' => $days,
            'deleted' => $deleted,
        ]);

        $this->info("Deleted {$deleted} log file(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
