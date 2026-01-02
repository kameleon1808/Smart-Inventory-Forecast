<?php

namespace App\Domain\Anomaly;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use App\Domain\Location;
use App\Domain\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnomalyThreshold extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'location_id',
        'type',
        'item_id',
        'category_id',
        'absolute_threshold',
        'percent_threshold',
        'count_threshold',
        'severity',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }
}
