<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    public function createBackup(): string
    {
        $connection = DB::connection()->getConfig();
        if (($connection['driver'] ?? null) !== 'mysql') {
            throw new \RuntimeException('Database backup is only supported for MySQL connections.');
        }

        return $this->createMysqlDump($connection);
    }

    public function cleanup(int $keep = 7): int
    {
        $disk = Storage::disk('local');
        $files = collect($disk->files('backups/db'))
            ->filter(fn (string $path): bool => str_ends_with($path, '.sql'))
            ->map(fn (string $path): array => [
                'path' => $path,
                'last_modified' => $disk->lastModified($path),
            ])
            ->sortByDesc('last_modified')
            ->values();

        if ($files->count() <= $keep) {
            return 0;
        }

        $toDelete = $files->slice($keep)->pluck('path')->all();
        $disk->delete($toDelete);

        return count($toDelete);
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
            throw new \RuntimeException('Backup produced an empty file.');
        }

        @rename($tempPath, $dumpPath);

        return $dumpPath;
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
}
