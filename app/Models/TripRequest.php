<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class TripRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'request_number',
        'branch_id',
        'requested_by_user_id',
        'purpose',
        'destination',
        'trip_date',
        'estimated_distance_km',
        'number_of_passengers',
        'additional_notes',
        'status',
        'approved_by_user_id',
        'approved_at',
        'rejection_reason',
        'assigned_vehicle_id',
        'assigned_driver_id',
        'assigned_at',
        'is_completed',
        'logbook_entered_by',
        'logbook_entered_at',
        'updated_by_user_id',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'estimated_distance_km' => 'decimal:2',
        'number_of_passengers' => 'integer',
        'approved_at' => 'datetime',
        'assigned_at' => 'datetime',
        'is_completed' => 'boolean',
        'logbook_entered_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function assignedVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'assigned_vehicle_id');
    }

    public function assignedDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'assigned_driver_id');
    }

    public function logbookEnteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logbook_entered_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function log(): HasOne
    {
        return $this->hasOne(TripLog::class);
    }
}
