<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class AuditLogService
{
    public function log(string $action, ?Model $model = null, array $oldValues = [], array $newValues = []): void
    {
        $user = request()->user();

        AuditLog::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::channel('telefleet')->info($action, [
            'user_id' => $user?->id,
            'model_type' => $model ? $model::class : null,
            'model_id' => $model?->getKey(),
            'ip' => request()->ip(),
        ]);
    }
}
