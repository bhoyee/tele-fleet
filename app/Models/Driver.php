<?php

namespace App\Models;

use App\Models\Concerns\HasUuidRouteKey;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Driver extends Model
{
    use HasFactory, SoftDeletes, HasUuidRouteKey;

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

    public function setFullNameAttribute($value): void
    {
        $this->attributes['full_name'] = TextNormalizer::personName(is_string($value) ? $value : null) ?? '';
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
