<?php

namespace App\Domain\Inventory;

use App\Domain\Location;
use App\Domain\Organization;
use App\Domain\Warehouse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class StockTransaction extends Model
{
    use HasFactory;

    public const TYPE_RECEIPT = 'RECEIPT';
    public const TYPE_WASTE = 'WASTE';
    public const TYPE_INTERNAL_USE = 'INTERNAL_USE';
    public const TYPE_ADJUSTMENT = 'ADJUSTMENT';
    public const TYPE_STOCK_COUNT_ADJUSTMENT = 'STOCK_COUNT_ADJUSTMENT';

    public const STATUS_POSTED = 'POSTED';

    protected $fillable = [
        'organization_id',
        'location_id',
        'warehouse_id',
        'type',
        'status',
        'happened_at',
        'reference',
        'supplier_name',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'happened_at' => 'datetime',
    ];

    public function scopePosted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(StockTransactionLine::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
