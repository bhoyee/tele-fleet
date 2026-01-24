<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

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
        'insurance_expiry',
        'registration_expiry',
        'status',
        'created_by',
    ];

    protected $casts = [
        'insurance_expiry' => 'date',
        'registration_expiry' => 'date',
        'current_mileage' => 'integer',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
