<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehicleAvailabilitySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'snapshot_date',
        'total_vehicles',
        'available_vehicles',
        'maintenance_vehicles',
        'assigned_vehicles',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
    ];
}
