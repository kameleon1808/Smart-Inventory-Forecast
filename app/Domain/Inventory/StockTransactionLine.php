<?php

namespace App\Domain\Inventory;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\Unit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransactionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transaction_id',
        'item_id',
        'unit_id',
        'quantity',
        'quantity_in_base',
        'unit_cost',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(StockTransaction::class, 'stock_transaction_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
