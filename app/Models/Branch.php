<?php

namespace App\Models;

use App\Models\Concerns\HasUuidRouteKey;
use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branch extends Model
{
    use HasFactory, HasUuidRouteKey;

    protected $fillable = [
        'name',
        'code',
        'address',
        'city',
        'state',
        'phone',
        'email',
        'is_head_office',
        'manager_id',
    ];

    protected $casts = [
        'is_head_office' => 'boolean',
    ];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = TextNormalizer::titleText(is_string($value) ? $value : null) ?? '';
    }

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = TextNormalizer::branchCode(is_string($value) ? $value : null) ?? '';
    }

    public function setCityAttribute($value): void
    {
        $this->attributes['city'] = TextNormalizer::titlePreserveAcronyms(is_string($value) ? $value : null, 3);
    }

    public function setStateAttribute($value): void
    {
        $this->attributes['state'] = TextNormalizer::titlePreserveAcronyms(is_string($value) ? $value : null, 3);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }

    public function tripRequests(): HasMany
    {
        return $this->hasMany(TripRequest::class);
    }
}
