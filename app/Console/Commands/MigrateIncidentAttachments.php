<?php

namespace App\Console\Commands;

use App\Models\IncidentReport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MigrateIncidentAttachments extends Command
{
    protected $signature = 'telefleet:migrate-incident-attachments {--dry-run} {--keep-public}';

    protected $description = 'Move incident attachments from public storage to private storage.';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $keepPublic = (bool) $this->option('keep-public');

        $local = Storage::disk('local');
        $public = Storage::disk('public');
        $local->makeDirectory('incidents');

        $migrated = 0;
        $skipped = 0;
        $missing = 0;
        $failed = 0;
        $updated = 0;

        $incidents = IncidentReport::query()
            ->whereNotNull('attachments')
            ->get();

        foreach ($incidents as $incident) {
            $attachments = (array) ($incident->attachments ?? []);
            $normalized = array_values(array_filter($attachments, fn ($value) => ! empty($value)));

            foreach ($normalized as $path) {
                if ($local->exists($path)) {
                    $skipped++;
                    continue;
                }

                if (! $public->exists($path)) {
                    $missing++;
                    continue;
                }

                if ($dryRun) {
                    $migrated++;
                    continue;
                }

                $readStream = $public->readStream($path);
                if (! $readStream) {
                    $failed++;
                    continue;
                }

                $writeOk = $local->writeStream($path, $readStream);
                if (is_resource($readStream)) {
                    fclose($readStream);
                }

                if (! $writeOk) {
                    $failed++;
                    continue;
                }

                if (! $keepPublic) {
                    $public->delete($path);
                }

                $migrated++;
            }

            if ($normalized !== $attachments) {
                $incident->attachments = $normalized;
                if (! $dryRun) {
                    $incident->save();
                }
                $updated++;
            }
        }

        $this->info('Incident attachment migration complete.');
        $this->line("Migrated: {$migrated}");
        $this->line("Skipped (already private): {$skipped}");
        $this->line("Missing: {$missing}");
        $this->line("Failed: {$failed}");
        if ($updated > 0) {
            $this->line("Records normalized: {$updated}");
        }

        Log::channel('telefleet')->info('incident_attachments.migrated', [
            'dry_run' => $dryRun,
            'keep_public' => $keepPublic,
            'migrated' => $migrated,
            'skipped' => $skipped,
            'missing' => $missing,
            'failed' => $failed,
            'normalized' => $updated,
        ]);

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
