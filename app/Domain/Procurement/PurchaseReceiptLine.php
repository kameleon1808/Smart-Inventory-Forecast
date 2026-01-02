<?php

namespace App\Domain\Procurement;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_receipt_id',
        'item_id',
        'qty_received_in_base',
        'unit_cost',
        'qty_display',
        'unit_id_display',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
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
