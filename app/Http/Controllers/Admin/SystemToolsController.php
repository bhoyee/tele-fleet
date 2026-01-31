<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class SystemToolsController extends Controller
{
    public function backups(Request $request): View
    {
        $disk = Storage::disk('local');
        $files = collect($disk->files('backups/db'))
            ->filter(fn (string $path): bool => str_ends_with($path, '.sql'))
            ->map(fn (string $path): array => [
                'path' => $path,
                'name' => basename($path),
                'size' => $disk->size($path),
                'last_modified' => $disk->lastModified($path),
            ])
            ->sortByDesc('last_modified')
            ->values();

        $lastBackup = $files->first();

        return view('admin.system.backups', compact('files', 'lastBackup'));
    }

    public function runBackup(Request $request, AuditLogService $auditLog): RedirectResponse
    {
        $connection = DB::connection()->getConfig();
        if (($connection['driver'] ?? null) !== 'mysql') {
            return redirect()
                ->route('system.backups')
                ->with('error', 'Database backup is only supported for MySQL connections.');
        }

        try {
            $dumpPath = $this->createMysqlDump($connection);
        } catch (Throwable $exception) {
            return redirect()
                ->route('system.backups')
                ->with('error', 'Backup failed: ' . $exception->getMessage());
        }

        $auditLog->log('system.backup_created', null, [], [
            'file' => basename($dumpPath),
        ]);

        return redirect()
            ->route('system.backups')
            ->with('success', 'Backup created successfully.');
    }

    public function downloadBackup(string $filename)
    {
        $path = 'backups/db/' . basename($filename);
        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->download($path);
    }

    public function deleteBackup(string $filename, AuditLogService $auditLog): RedirectResponse
    {
        $path = 'backups/db/' . basename($filename);
        if (! Storage::disk('local')->exists($path)) {
            return redirect()
                ->route('system.backups')
                ->with('error', 'Backup file not found.');
        }

        Storage::disk('local')->delete($path);

        $auditLog->log('system.backup_deleted', null, [], [
            'file' => basename($filename),
        ]);

        return redirect()
            ->route('system.backups')
            ->with('success', 'Backup deleted.');
    }

    public function logs(Request $request): View
    {
        $logDir = storage_path('logs');
        $files = collect(File::exists($logDir) ? File::files($logDir) : [])
            ->filter(fn (\SplFileInfo $file): bool => str_ends_with($file->getFilename(), '.log'))
            ->map(fn (\SplFileInfo $file): array => [
                'path' => $file->getRealPath(),
                'name' => $file->getFilename(),
                'last_modified' => $file->getMTime(),
            ])
            ->sortByDesc('last_modified')
            ->values();

        $selected = $request->query('file') ?? ($files->first()['name'] ?? null);
        $logPath = $selected ? ($logDir . DIRECTORY_SEPARATOR . basename($selected)) : null;
        $rawEntries = $logPath ? $this->tailFile($logPath, 200) : collect();
        [$entries, $summary] = $this->parseLogEntries($rawEntries);
        $entries = $this->applyLogFilters($entries, $request);
        $summary = $this->buildSummary($entries);

        return view('admin.system.logs', [
            'files' => $files,
            'selected' => $selected,
            'entries' => $entries,
            'summary' => $summary,
            'filters' => [
                'level' => $request->query('level'),
                'from' => $request->query('from'),
                'to' => $request->query('to'),
                'q' => $request->query('q'),
            ],
        ]);
    }

    public function downloadLog(string $filename)
    {
        $path = 'logs/' . basename($filename);
        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        if (request()->query('format') === 'csv') {
            $lines = $this->tailFile(Storage::disk('local')->path($path), 1000);
            [$entries] = $this->parseLogEntries($lines);

            return response()->streamDownload(function () use ($entries): void {
                $handle = fopen('php://output', 'wb');
                fputcsv($handle, ['timestamp', 'env', 'level', 'message', 'context']);
                foreach ($entries as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    fputcsv($handle, [
                        $entry['timestamp'] ?? '',
                        $entry['env'] ?? '',
                        $entry['level'] ?? '',
                        $entry['message'] ?? '',
                        $entry['context'] ? json_encode($entry['context']) : '',
                    ]);
                }
                fclose($handle);
            }, basename($filename, '.log') . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        return Storage::disk('local')->download($path);
    }

    private function createMysqlDump(array $config): string
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('backups/db');

        $timestamp = now()->format('Ymd_His');
        $dumpPath = $disk->path("backups/db/telefleet_backup_{$timestamp}.sql");
        $tempPath = $disk->path("backups/db/telefleet_backup_{$timestamp}.tmp");

        $binary = env('MYSQLDUMP_PATH', 'mysqldump');

        $host = env('MYSQLDUMP_HOST', $config['host'] ?? '127.0.0.1');
        $port = env('MYSQLDUMP_PORT', $config['port'] ?? 3306);
        $user = env('MYSQLDUMP_USER', $config['username'] ?? 'root');
        $password = env('MYSQLDUMP_PASS', $config['password'] ?? null);
        $protocol = strtolower((string) env('MYSQLDUMP_PROTOCOL', 'tcp'));
        $socket = env('MYSQLDUMP_SOCKET', $config['unix_socket'] ?? null);
        $extraArgs = trim((string) env('MYSQLDUMP_EXTRA_ARGS', ''));

        $database = env('MYSQLDUMP_DATABASE', $config['database'] ?? '');
        $attempts = [
            ['host' => $host, 'protocol' => $protocol],
        ];

        if (! env('MYSQLDUMP_HOST')) {
            $attempts[] = ['host' => '127.0.0.1', 'protocol' => 'tcp'];
            $attempts[] = ['host' => 'localhost', 'protocol' => 'tcp'];
        }

        $lastError = null;

        foreach ($attempts as $attempt) {
            $command = [
                $binary,
                '--host=' . $attempt['host'],
                '--port=' . $port,
                '--user=' . $user,
                '--protocol=' . ($attempt['protocol'] ?: 'tcp'),
                '--single-transaction',
                '--routines',
                '--triggers',
            ];

            if ($attempt['protocol'] === 'pipe' && $socket) {
                $command[] = '--socket=' . $socket;
            }

            if ($password !== null && $password !== '') {
                $command[] = '--password=' . $password;
            }

            if ($extraArgs !== '') {
                $command = array_merge($command, preg_split('/\s+/', $extraArgs));
            }

            $command[] = $database;
            $command[] = '--result-file=' . $tempPath;

            $process = new Process($command);
            $process->setTimeout(300);
            $process->run();

            if ($process->isSuccessful()) {
                $lastError = null;
                break;
            }

            $lastError = $process->getErrorOutput() ?: $process->getOutput();
        }

        if ($lastError !== null) {
            $cmdError = $this->runCmdDump($binary, $host, $port, $user, $password, $database, $extraArgs, $tempPath);
            if ($cmdError === null) {
                $lastError = null;
            } else {
                $lastError = $cmdError;
            }
        }

        if ($lastError !== null) {
            $lastError = $this->createSqlBackupFromDb($tempPath) ? null : $lastError;
        }

        if ($lastError !== null) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            throw new \RuntimeException(trim($lastError) ?: 'mysqldump failed.');
        }

        if (! is_file($tempPath) || filesize($tempPath) === 0) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            throw new \RuntimeException('mysqldump produced an empty backup file.');
        }

        @rename($tempPath, $dumpPath);

        return $dumpPath;
    }

    private function runCmdDump(
        string $binary,
        string $host,
        int|string $port,
        string $user,
        ?string $password,
        string $database,
        string $extraArgs,
        string $tempPath
    ): ?string {
        if (stripos(PHP_OS_FAMILY, 'Windows') === false) {
            return 'CMD fallback is only available on Windows.';
        }

        $args = [
            '--host=' . $host,
            '--port=' . $port,
            '--user=' . $user,
        ];

        if ($password !== null && $password !== '') {
            $args[] = '--password=' . $password;
        }

        if ($extraArgs !== '') {
            $args = array_merge($args, preg_split('/\s+/', $extraArgs));
        }

        $args[] = $database;

        $escapedArgs = implode(' ', array_map(static fn (string $value): string => '"' . str_replace('"', '\"', $value) . '"', $args));
        $binaryEscaped = '"' . str_replace('"', '\"', $binary) . '"';
        $outEscaped = '"' . str_replace('"', '\"', $tempPath) . '"';

        $batPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'telefleet_dump_' . uniqid() . '.bat';
        $command = $binaryEscaped . ' ' . $escapedArgs . ' > ' . $outEscaped;
        File::put($batPath, $command);

        $process = new Process(['cmd', '/d', '/s', '/c', $batPath]);
        $process->setTimeout(300);
        $process->run();

        @unlink($batPath);

        if (! $process->isSuccessful()) {
            return $process->getErrorOutput() ?: $process->getOutput();
        }

        return null;
    }

    private function createSqlBackupFromDb(string $tempPath): bool
    {
        try {
            $pdo = DB::connection()->getPdo();
            $database = DB::connection()->getDatabaseName();

            File::put($tempPath, "-- Tele-Fleet database backup\n");
            File::append($tempPath, "-- Generated: " . now()->toDateTimeString() . "\n\n");
            File::append($tempPath, "CREATE DATABASE IF NOT EXISTS `{$database}`;\n");
            File::append($tempPath, "USE `{$database}`;\n\n");

            $tables = DB::select('SHOW TABLES');
            $tableKey = 'Tables_in_' . $database;

            foreach ($tables as $tableRow) {
                $tableName = $tableRow->$tableKey ?? null;
                if (! $tableName) {
                    continue;
                }

                $create = DB::select("SHOW CREATE TABLE `{$tableName}`");
                $createSql = $create[0]->{'Create Table'} ?? null;
                if (! $createSql) {
                    continue;
                }

                File::append($tempPath, "\n-- Table structure for `{$tableName}`\n");
                File::append($tempPath, "DROP TABLE IF EXISTS `{$tableName}`;\n");
                File::append($tempPath, $createSql . ";\n\n");

                $total = DB::table($tableName)->count();
                $chunkSize = 500;
                for ($offset = 0; $offset < $total; $offset += $chunkSize) {
                    $rows = DB::table($tableName)->offset($offset)->limit($chunkSize)->get();
                    if ($rows->isEmpty()) {
                        continue;
                    }

                    $columns = array_keys((array) $rows->first());
                    $colList = implode(', ', array_map(fn ($col) => "`{$col}`", $columns));
                    $values = [];

                    foreach ($rows as $row) {
                        $rowArr = (array) $row;
                        $valueList = [];
                        foreach ($columns as $column) {
                            $value = $rowArr[$column] ?? null;
                            if ($value === null) {
                                $valueList[] = 'NULL';
                            } elseif (is_bool($value)) {
                                $valueList[] = $value ? '1' : '0';
                            } elseif (is_numeric($value)) {
                                $valueList[] = (string) $value;
                            } else {
                                $valueList[] = $pdo->quote((string) $value);
                            }
                        }
                        $values[] = '(' . implode(', ', $valueList) . ')';
                    }

                    File::append($tempPath, "INSERT INTO `{$tableName}` ({$colList}) VALUES\n");
                    File::append($tempPath, implode(",\n", $values) . ";\n\n");
                }
            }

            return true;
        } catch (Throwable) {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
            return false;
        }
    }

    private function tailFile(string $path, int $lines = 200): Collection
    {
        if (! is_file($path)) {
            return collect();
        }

        $buffer = [];
        $file = new \SplFileObject($path, 'r');
        $file->seek(PHP_INT_MAX);
        $position = $file->key();
        for ($i = $position; $i >= 0 && count($buffer) < $lines; $i--) {
            $file->seek($i);
            $line = trim((string) $file->current());
            if ($line === '') {
                continue;
            }
            $buffer[] = $line;
        }

        return collect(array_reverse($buffer));
    }

    private function parseLogEntries(Collection $entries): array
    {
        $parsed = $entries->map(function (string $line): array {
            $pattern = '/^\[(?<timestamp>[^\]]+)\]\s(?<env>[a-zA-Z0-9_-]+)\.(?<level>[A-Z]+):\s(?<message>.*)$/';
            if (! preg_match($pattern, $line, $matches)) {
                return [
                    'raw' => $line,
                    'timestamp' => null,
                    'env' => null,
                    'level' => 'info',
                    'message' => $line,
                    'context' => null,
                ];
            }

            $message = trim($matches['message'] ?? '');
            $context = null;

            $contextStart = strrpos($message, ' {');
            if ($contextStart !== false && str_ends_with($message, '}')) {
                $candidate = substr($message, $contextStart + 1);
                $decoded = json_decode($candidate, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $context = $decoded;
                    $message = trim(substr($message, 0, $contextStart));
                }
            }

            return [
                'raw' => $line,
                'timestamp' => $matches['timestamp'] ?? null,
                'env' => $matches['env'] ?? null,
                'level' => strtolower($matches['level'] ?? 'info'),
                'message' => $message,
                'context' => $context,
            ];
        });

        $levelCounts = $parsed
            ->groupBy('level')
            ->map(fn (Collection $group) => $group->count())
            ->toArray();

        $topMessages = $parsed
            ->filter(fn (array $entry) => ! empty($entry['message']))
            ->groupBy('message')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->take(5)
            ->toArray();

        return [$parsed, $this->buildSummary($parsed)];
    }

    private function buildSummary(Collection $entries): array
    {
        $levelCounts = $entries
            ->groupBy('level')
            ->map(fn (Collection $group) => $group->count())
            ->toArray();

        $topMessages = $entries
            ->filter(fn (array $entry) => ! empty($entry['message']))
            ->groupBy('message')
            ->map(fn (Collection $group) => $group->count())
            ->sortDesc()
            ->take(5)
            ->toArray();

        return [
            'counts' => $levelCounts,
            'top_messages' => $topMessages,
        ];
    }

    private function applyLogFilters(Collection $entries, Request $request): Collection
    {
        $level = $request->query('level');
        $from = $request->query('from');
        $to = $request->query('to');
        $query = trim((string) $request->query('q', ''));

        return $entries->filter(function (array $entry) use ($level, $from, $to, $query): bool {
            if ($level && ($entry['level'] ?? '') !== strtolower($level)) {
                return false;
            }

            if ($from || $to) {
                $timestamp = $entry['timestamp'] ?? null;
                if (! $timestamp) {
                    return false;
                }
                try {
                    $time = \Illuminate\Support\Carbon::parse($timestamp);
                } catch (\Throwable) {
                    return false;
                }
                if ($from && $time->lt(\Illuminate\Support\Carbon::parse($from)->startOfDay())) {
                    return false;
                }
                if ($to && $time->gt(\Illuminate\Support\Carbon::parse($to)->endOfDay())) {
                    return false;
                }
            }

            if ($query !== '') {
                $haystack = strtolower(($entry['message'] ?? '') . ' ' . json_encode($entry['context'] ?? []));
                if (! str_contains($haystack, strtolower($query))) {
                    return false;
                }
            }

            return true;
        })->values();
    }
}
