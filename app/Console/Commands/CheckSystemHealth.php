<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\SystemHealthAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;

class CheckSystemHealth extends Command
{
    protected $signature = 'telefleet:check-system-health';

    protected $description = 'Check scheduler heartbeat and queue health and notify super admins when issues exist.';

    public function handle(): int
    {
        $issues = [];
        $now = now();

        $heartbeat = Cache::get('telefleet.scheduler_heartbeat');
        if (! $heartbeat) {
            $issues[] = [
                'key' => 'scheduler_stale',
                'title' => 'Scheduler heartbeat missing',
                'detail' => 'The scheduler heartbeat has not been updated in the last 10 minutes.',
                'context' => ['last_seen' => 'never'],
                'cooldown' => 30,
            ];
        }

        if ($heartbeat) {
            try {
                $lastSeen = \Illuminate\Support\Carbon::parse($heartbeat);
                if ($lastSeen->lt($now->copy()->subMinutes(10))) {
                    $issues[] = [
                        'key' => 'scheduler_stale',
                        'title' => 'Scheduler heartbeat stale',
                        'detail' => 'The scheduler heartbeat is older than 10 minutes.',
                        'context' => ['last_seen' => $lastSeen->toDateTimeString()],
                        'cooldown' => 30,
                    ];
                }
            } catch (\Throwable $exception) {
                $issues[] = [
                    'key' => 'scheduler_invalid',
                    'title' => 'Scheduler heartbeat invalid',
                    'detail' => 'The scheduler heartbeat value could not be parsed.',
                    'context' => ['last_seen' => (string) $heartbeat],
                    'cooldown' => 30,
                ];
            }
        }

        if (Schema::hasTable('failed_jobs')) {
            $failedCount = (int) DB::table('failed_jobs')->count();
            if ($failedCount > 0) {
                $issues[] = [
                    'key' => 'queue_failed',
                    'title' => 'Queue failures detected',
                    'detail' => 'There are failed jobs waiting in the queue.',
                    'context' => ['failed_jobs' => $failedCount],
                    'cooldown' => 30,
                ];
            }
        }

        if (Schema::hasTable('jobs')) {
            $pendingCount = (int) DB::table('jobs')->count();
            if ($pendingCount > 25) {
                $issues[] = [
                    'key' => 'queue_backlog',
                    'title' => 'Queue backlog detected',
                    'detail' => 'Pending jobs are building up in the queue.',
                    'context' => ['pending_jobs' => $pendingCount],
                    'cooldown' => 30,
                ];
            }
        }

        if (empty($issues)) {
            $this->info('System health OK.');
            return self::SUCCESS;
        }

        $recipients = User::where('role', User::ROLE_SUPER_ADMIN)->get();
        foreach ($issues as $issue) {
            $cacheKey = 'telefleet.health_alert.' . $issue['key'];
            if (Cache::has($cacheKey)) {
                continue;
            }

            Notification::sendNow($recipients, new SystemHealthAlert(
                $issue['title'],
                $issue['detail'],
                $issue['context']
            ));

            Cache::put($cacheKey, true, now()->addMinutes((int) $issue['cooldown']));
            Log::channel('telefleet')->warning('system.health_alert', $issue);
        }

        $this->warn('System health issues detected. Notifications sent.');
        return self::SUCCESS;
    }
}
