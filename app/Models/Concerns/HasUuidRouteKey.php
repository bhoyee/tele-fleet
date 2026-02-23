<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasUuidRouteKey
{
    protected static ?bool $uuidColumnExists = null;

    protected static function bootHasUuidRouteKey(): void
    {
        static::creating(function ($model): void {
            if (! static::hasUuidColumn()) {
                return;
            }

            if (! is_string($model->uuid) || $model->uuid === '') {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return static::hasUuidColumn() ? 'uuid' : $this->getKeyName();
    }

    protected static function hasUuidColumn(): bool
    {
        if (static::$uuidColumnExists !== null) {
            return static::$uuidColumnExists;
        }

        try {
            static::$uuidColumnExists = Schema::hasColumn((new static())->getTable(), 'uuid');
        } catch (\Throwable) {
            static::$uuidColumnExists = false;
        }

        return static::$uuidColumnExists;
    }
}

