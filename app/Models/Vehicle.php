<?php

namespace App\Models;

use App\Models\Concerns\HasUuidRouteKey;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes, HasUuidRouteKey;

    protected $fillable = [
        'registration_number',
        'branch_id',
        'make',
        'model',
        'year',
        'color',
        'fuel_type',
        'engine_capacity',
        'current_mileage',
        'last_maintenance_mileage',
        'insurance_expiry',
        'registration_expiry',
        'status',
        'maintenance_state',
        'maintenance_due_notified_at',
        'maintenance_overdue_notified_at',
        'created_by',
    ];

    protected $casts = [
        'insurance_expiry' => 'date',
        'registration_expiry' => 'date',
        'current_mileage' => 'integer',
        'last_maintenance_mileage' => 'integer',
        'maintenance_due_notified_at' => 'datetime',
        'maintenance_overdue_notified_at' => 'datetime',
    ];

    public function setMakeAttribute($value): void
    {
        $this->attributes['make'] = TextNormalizer::titlePreserveAcronyms(is_string($value) ? $value : null, 3) ?? '';
    }

    public function setModelAttribute($value): void
    {
        $this->attributes['model'] = TextNormalizer::titlePreserveAcronyms(is_string($value) ? $value : null, 3) ?? '';
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function maintenances()
    {
        return $this->hasMany(VehicleMaintenance::class);
    }
}
