<?php

namespace App\Domain\Inventory;

use App\Domain\Location;
use App\Domain\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpectedConsumptionDaily extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'location_id',
        'date',
        'item_id',
        'expected_qty_in_base',
    ];

    protected $casts = [
        'date' => 'date',
        'expected_qty_in_base' => 'float',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
