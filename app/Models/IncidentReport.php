<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentReport extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_REVIEW = 'under_review';
    public const STATUS_RESOLVED = 'resolved';

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
        'closed_by_user_id',
        'closed_at',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'incident_time' => 'string',
        'attachments' => 'array',
        'closed_at' => 'datetime',
    ];

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
}
