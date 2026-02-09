<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TripAssignment extends Model
{
    protected $fillable = [
        'trip_request_id',
        'from_vehicle_id',
        'to_vehicle_id',
        'from_driver_id',
        'to_driver_id',
        'changed_by_user_id',
        'reason',
    ];

    public function tripRequest(): BelongsTo
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function fromVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'from_vehicle_id');
    }

    public function toVehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'to_vehicle_id');
    }

    public function fromDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'from_driver_id');
    }

    public function toDriver(): BelongsTo
    {
        return $this->belongsTo(Driver::class, 'to_driver_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}

