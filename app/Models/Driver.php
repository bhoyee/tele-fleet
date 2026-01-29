<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'full_name',
        'license_number',
        'license_type',
        'license_expiry',
        'license_expiry_notified_at',
        'phone',
        'email',
        'address',
        'branch_id',
        'status',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'license_expiry_notified_at' => 'datetime',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
