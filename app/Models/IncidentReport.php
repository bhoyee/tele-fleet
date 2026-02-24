<?php

namespace App\Models;

use App\Support\TextNormalizer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IncidentReport extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static ?bool $uuidColumnExists = null;

    public const STATUS_OPEN = 'open';
    public const STATUS_REVIEW = 'under_review';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CANCELLED = 'cancelled';

    public const SEVERITY_MINOR = 'minor';
    public const SEVERITY_MAJOR = 'major';
    public const SEVERITY_CRITICAL = 'critical';

    protected $fillable = [
        'reference',
        'trip_request_id',
        'branch_id',
        'vehicle_id',
        'driver_id',
        'reported_by_user_id',
        'title',
        'description',
        'incident_date',
        'incident_time',
        'location',
        'severity',
        'status',
        'attachments',
        'resolution_notes',
        'cancellation_reason',
        'closed_by_user_id',
        'updated_by_user_id',
        'closed_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'incident_time' => 'string',
        'attachments' => 'array',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (IncidentReport $incidentReport): void {
            if (! static::hasUuidColumn()) {
                return;
            }

            if (! is_string($incidentReport->uuid) || $incidentReport->uuid === '') {
                $incidentReport->uuid = (string) Str::uuid();
            }
        });
    }

    public function setTitleAttribute($value): void
    {
        $this->attributes['title'] = TextNormalizer::titleText(is_string($value) ? $value : null) ?? '';
    }

    public function setLocationAttribute($value): void
    {
        $this->attributes['location'] = TextNormalizer::titleText(is_string($value) ? $value : null);
    }

    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = TextNormalizer::collapseWhitespace(is_string($value) ? $value : null) ?? '';
    }

    public function setResolutionNotesAttribute($value): void
    {
        $this->attributes['resolution_notes'] = TextNormalizer::collapseWhitespace(is_string($value) ? $value : null);
    }

    public function setCancellationReasonAttribute($value): void
    {
        $this->attributes['cancellation_reason'] = TextNormalizer::collapseWhitespace(is_string($value) ? $value : null);
    }

    public function getRouteKeyName(): string
    {
        return static::hasUuidColumn() ? 'uuid' : $this->getKeyName();
    }

    private static function hasUuidColumn(): bool
    {
        if (static::$uuidColumnExists !== null) {
            return static::$uuidColumnExists;
        }

        try {
            static::$uuidColumnExists = Schema::hasColumn((new static())->getTable(), 'uuid');
        } catch (\Throwable) {
            static::$uuidColumnExists = false;
        }

        return static::$uuidColumnExists;
    }

    public function tripRequest(): BelongsTo
    {
        return $this->belongsTo(TripRequest::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function reportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_user_id');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
