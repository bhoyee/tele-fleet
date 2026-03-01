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

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ASSIGNED_TO_OFFICER = 'inactive';
    public const STATUS_ON_LEAVE = 'suspended';

    protected $fillable = [
        'full_name',
        'license_number',
        'license_type',
        'license_expiry',
        'license_expiry_notified_at',
        'phone',
        'email',
        'address',
        'note',
        'branch_id',
        'status',
        'created_by_user_id',
        'updated_by_user_id',
    ];

    protected $casts = [
        'license_expiry' => 'date',
        'license_expiry_notified_at' => 'datetime',
    ];

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ASSIGNED_TO_OFFICER => 'Assigned to Officer',
            self::STATUS_ON_LEAVE => 'On Leave',
            default => 'Unknown',
        };
    }

    public static function statusBadgeClass(?string $status): string
    {
        return match ($status) {
            self::STATUS_ACTIVE => 'success',
            self::STATUS_ASSIGNED_TO_OFFICER => 'secondary',
            self::STATUS_ON_LEAVE => 'warning text-dark',
            default => 'light text-dark',
        };
    }

    public function setFullNameAttribute($value): void
    {
        $this->attributes['full_name'] = TextNormalizer::personName(is_string($value) ? $value : null) ?? '';
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = TextNormalizer::phoneE164(is_string($value) ? $value : null);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
