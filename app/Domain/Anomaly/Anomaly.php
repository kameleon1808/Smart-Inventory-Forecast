<?php

namespace App\Domain\Anomaly;

use App\Domain\Inventory\Item;
use App\Domain\Location;
use App\Domain\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anomaly extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'OPEN';
    public const STATUS_INVESTIGATING = 'INVESTIGATING';
    public const STATUS_RESOLVED = 'RESOLVED';
    public const STATUS_FALSE_POSITIVE = 'FALSE_POSITIVE';

    protected $fillable = [
        'organization_id',
        'location_id',
        'type',
        'severity',
        'item_id',
        'happened_on',
        'metric_value',
        'threshold_value',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'happened_on' => 'date',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(AnomalyComment::class);
    }
}
