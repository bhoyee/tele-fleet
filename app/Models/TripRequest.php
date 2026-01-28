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
        'trip_time',
        'estimated_distance_km',
        'number_of_passengers',
        'additional_notes',
        'status',
        'requires_reassignment',
        'assignment_conflict_reason',
        'assignment_conflict_at',
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
        'overdue_notified_at',
        'reminder_notified_at',
        'assignment_reminder_notified_at',
    ];

    protected $casts = [
        'trip_date' => 'date',
        'trip_time' => 'string',
        'estimated_distance_km' => 'decimal:2',
        'number_of_passengers' => 'integer',
        'approved_at' => 'datetime',
        'assigned_at' => 'datetime',
        'is_completed' => 'boolean',
        'logbook_entered_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'reminder_notified_at' => 'datetime',
        'assignment_reminder_notified_at' => 'datetime',
        'assignment_conflict_at' => 'datetime',
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

    public function dueStatus(?\Illuminate\Support\Carbon $now = null): ?string
    {
        $status = strtolower((string) $this->status);
        if (in_array($status, ['completed', 'cancelled', 'rejected'], true)) {
            return null;
        }

        if (! $this->trip_date) {
            return null;
        }

        $estimateDays = (float) ($this->estimated_distance_km ?? 0);
        if ($estimateDays <= 0) {
            return null;
        }

        $now = $now ?? \Illuminate\Support\Carbon::now();
        $tripTime = $this->trip_time ? $this->trip_time : '00:00';

        try {
            $start = \Illuminate\Support\Carbon::createFromFormat('Y-m-d H:i', $this->trip_date->format('Y-m-d').' '.$tripTime);
        } catch (\Exception $exception) {
            $start = \Illuminate\Support\Carbon::parse($this->trip_date->format('Y-m-d').' '.$tripTime);
        }

        $hours = (int) round($estimateDays * 24);
        if ($hours <= 0) {
            return null;
        }

        $expectedEnd = $start->copy()->addHours($hours);

        if ($now->greaterThanOrEqualTo($expectedEnd->copy()->addHours(24))) {
            return 'overdue';
        }

        if ($now->greaterThanOrEqualTo($expectedEnd)) {
            return 'due';
        }

        return null;
    }
}
