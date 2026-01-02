<?php

namespace App\Domain\Procurement;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'item_id',
        'qty_ordered_in_base',
        'unit_id_display',
        'qty_display',
        'unit_cost_estimate',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unitDisplay(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id_display');
    }
}
