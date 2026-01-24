<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_request_id',
        'start_mileage',
        'end_mileage',
        'distance_traveled',
        'fuel_before_trip',
        'fuel_after_trip',
        'fuel_consumed',
        'actual_start_time',
        'actual_end_time',
        'trip_duration_hours',
        'driver_name',
        'driver_license_number',
        'paper_logbook_ref_number',
        'driver_notes',
        'entered_by_user_id',
        'log_date',
        'remarks',
        'verified_by_branch_head',
        'branch_head_verified_at',
    ];

    protected $casts = [
        'distance_traveled' => 'integer',
        'fuel_before_trip' => 'decimal:2',
        'fuel_after_trip' => 'decimal:2',
        'fuel_consumed' => 'decimal:2',
        'actual_start_time' => 'datetime',
        'actual_end_time' => 'datetime',
        'trip_duration_hours' => 'decimal:2',
        'log_date' => 'date',
        'verified_by_branch_head' => 'boolean',
        'branch_head_verified_at' => 'datetime',
    ];

    public function tripRequest(): BelongsTo
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by_user_id');
    }
}
