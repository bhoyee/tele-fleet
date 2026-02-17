<?php

namespace App\Notifications\Concerns;

trait QueueReliability
{
    /**
     * Shared-hosting friendly retry settings.
     *
     * - Higher tries/backoff helps survive short DB/SMTP hiccups without landing in failed_jobs.
     * - Keep timeout modest so cron-based workers can cycle frequently.
     */
    public int $tries = 10;

    /**
     * Seconds to wait before retrying.
     *
     * @var array<int>|int
     */
    public array|int $backoff = [5, 30, 120, 300];

    /**
     * Max seconds a single attempt can run.
     */
    public int $timeout = 120;
}

