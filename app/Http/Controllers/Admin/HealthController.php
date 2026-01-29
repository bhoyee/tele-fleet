<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function index(): View
    {
        $payload = $this->buildHealthPayload();

        return view('admin.health', $payload);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->buildHealthPayload());
    }

    private function checkReverb(): array
    {
        $host = config('reverb.servers.reverb.hostname')
            ?? config('reverb.servers.reverb.host')
            ?? config('broadcasting.connections.reverb.options.host');
        $port = config('reverb.servers.reverb.port') ?? config('broadcasting.connections.reverb.options.port');

        if (! $host || ! $port) {
            return [
                'label' => 'Reverb',
                'status' => 'warning',
                'detail' => 'Not configured',
            ];
        }

        $timeout = 1;
        $connected = false;
        try {
            $socket = @fsockopen($host, (int) $port, $errno, $errstr, $timeout);
            if ($socket) {
                $connected = true;
                fclose($socket);
            }
        } catch (\Throwable $error) {
            $connected = false;
        }

        return [
            'label' => 'Reverb',
            'status' => $connected ? 'ok' : 'warning',
            'detail' => $connected ? 'Reachable' : 'Not reachable',
        ];
    }

    private function buildHealthPayload(): array
    {
        $checks = [
            'app' => [
                'label' => 'Application',
                'status' => 'ok',
                'detail' => 'Running',
            ],
        ];

        try {
            DB::select('select 1');
            $checks['database'] = [
                'label' => 'Database',
                'status' => 'ok',
                'detail' => 'Connected',
            ];
        } catch (\Throwable $error) {
            $checks['database'] = [
                'label' => 'Database',
                'status' => 'error',
                'detail' => 'Connection failed',
            ];
        }

        try {
            Cache::put('telefleet_health_check', 'ok', now()->addMinutes(2));
            $cacheOk = Cache::get('telefleet_health_check') === 'ok';
            $checks['cache'] = [
                'label' => 'Cache',
                'status' => $cacheOk ? 'ok' : 'warning',
                'detail' => $cacheOk ? 'Readable/Writable' : 'Not responding',
            ];
        } catch (\Throwable $error) {
            $checks['cache'] = [
                'label' => 'Cache',
                'status' => 'error',
                'detail' => 'Unavailable',
            ];
        }

        $queueStats = [
            'pending' => null,
            'failed' => null,
        ];
        try {
            $queueStats['pending'] = DB::table('jobs')->count();
        } catch (\Throwable $error) {
            $queueStats['pending'] = null;
        }
        try {
            $queueStats['failed'] = DB::table('failed_jobs')->count();
        } catch (\Throwable $error) {
            $queueStats['failed'] = null;
        }
        $checks['queue'] = [
            'label' => 'Queue',
            'status' => ($queueStats['failed'] ?? 0) > 0 ? 'warning' : 'ok',
            'detail' => 'Pending: ' . ($queueStats['pending'] ?? 'N/A') . ' | Failed: ' . ($queueStats['failed'] ?? 'N/A'),
        ];

        $lastHeartbeat = Cache::get('telefleet.scheduler_heartbeat');
        $checks['scheduler'] = [
            'label' => 'Scheduler',
            'status' => $lastHeartbeat ? 'ok' : 'warning',
            'detail' => $lastHeartbeat ? 'Last run: ' . $lastHeartbeat : 'No recent heartbeat',
        ];

        $checks['reverb'] = $this->checkReverb();

        $mailHost = config('mail.mailers.smtp.host');
        $mailFrom = config('mail.from.address');
        $checks['mail'] = [
            'label' => 'Mail',
            'status' => ($mailHost && $mailFrom) ? 'ok' : 'warning',
            'detail' => ($mailHost && $mailFrom) ? 'Configured' : 'Missing SMTP settings',
        ];

        $lastMailSent = Cache::get('telefleet.mail_last_sent_at');
        $checks['mail_last_sent'] = [
            'label' => 'Last Email Sent',
            'status' => $lastMailSent ? 'ok' : 'warning',
            'detail' => $lastMailSent ? $lastMailSent : 'No email logged yet',
        ];

        $storageOk = is_writable(storage_path()) && is_writable(storage_path('logs'));
        $checks['storage'] = [
            'label' => 'Storage',
            'status' => $storageOk ? 'ok' : 'warning',
            'detail' => $storageOk ? 'Writable' : 'Check permissions',
        ];

        $diskTotal = @disk_total_space(base_path());
        $diskFree = @disk_free_space(base_path());
        if ($diskTotal && $diskFree) {
            $diskUsed = $diskTotal - $diskFree;
            $diskPct = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;
            $checks['disk'] = [
                'label' => 'Disk Usage',
                'status' => $diskPct >= 90 ? 'warning' : 'ok',
                'detail' => $diskPct . '% used (' . $this->formatBytes($diskUsed) . ' / ' . $this->formatBytes($diskTotal) . ')',
            ];
        } else {
            $checks['disk'] = [
                'label' => 'Disk Usage',
                'status' => 'warning',
                'detail' => 'Unable to read disk stats',
            ];
        }

        $load = function_exists('sys_getloadavg') ? sys_getloadavg() : null;
        if (is_array($load)) {
            $checks['cpu'] = [
                'label' => 'CPU Load',
                'status' => 'ok',
                'detail' => sprintf('1m: %.2f, 5m: %.2f, 15m: %.2f', $load[0], $load[1], $load[2]),
            ];
        } else {
            $checks['cpu'] = [
                'label' => 'CPU Load',
                'status' => 'warning',
                'detail' => 'Not supported on this OS',
            ];
        }

        return [
            'checks' => $checks,
            'queueStats' => $queueStats,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $bytes;
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return sprintf('%.1f %s', $size, $units[$unit]);
    }
}
