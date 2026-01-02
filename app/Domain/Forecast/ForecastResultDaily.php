<?php

namespace App\Domain\Forecast;

use App\Domain\Inventory\Item;
use App\Domain\Location;
use App\Domain\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ForecastResultDaily extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'location_id',
        'item_id',
        'date',
        'predicted_qty_in_base',
        'lower',
        'upper',
        'model_version',
    ];

    protected $casts = [
        'date' => 'date',
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
}
