<?php

namespace App\Services;

use App\Domain\Inventory\StockCount;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\StockTransactionLine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockCountService
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * Post a stock count: create adjustment transaction(s) and mark posted.
     *
     * @return array<int, float> item_id => adjustment (base units)
     */
    public function post(StockCount $count): array
    {
        if ($count->status === StockCount::STATUS_POSTED) {
            throw new InvalidArgumentException('Stock count already posted.');
        }

        $count->loadMissing(['lines', 'warehouse', 'location', 'organization']);
        $items = $count->lines->loadMissing('item.baseUnit')->pluck('item', 'item_id');

        $itemIds = $count->lines->pluck('item_id')->all();
        $currentBalances = $this->stockService->balancesForItemsInWarehouse($itemIds, $count->warehouse_id);

        $adjustments = [];

        foreach ($count->lines as $line) {
            $current = $currentBalances[$line->item_id] ?? 0.0;
            $diff = (float) $line->counted_quantity_in_base - $current;
            $adjustments[$line->item_id] = $diff;
        }

        $differences = array_filter($adjustments, fn ($value) => abs($value) > 0.0001);

        $creatorId = auth()->id() ?? $count->created_by;

        DB::transaction(function () use ($count, $differences, $items, $creatorId): void {
            if (! empty($differences)) {
                $tx = StockTransaction::create([
                    'organization_id' => $count->organization_id,
                    'location_id' => $count->location_id,
                    'warehouse_id' => $count->warehouse_id,
                    'type' => StockTransaction::TYPE_STOCK_COUNT_ADJUSTMENT,
                    'status' => StockTransaction::STATUS_POSTED,
                    'happened_at' => $count->counted_at,
                    'reference' => 'Stock count #'.$count->id,
                    'reason' => 'Stock count adjustment',
                    'created_by' => $creatorId,
                ]);

                foreach ($differences as $itemId => $diff) {
                    $baseUnitId = $items[$itemId]?->base_unit_id;
                    StockTransactionLine::create([
                        'stock_transaction_id' => $tx->id,
                        'item_id' => $itemId,
                        'unit_id' => $baseUnitId,
                        'quantity' => $diff,
                        'quantity_in_base' => $diff,
                        'unit_cost' => null,
                    ]);
                }
            }

            $count->update(['status' => StockCount::STATUS_POSTED]);
        });

        return $differences;
    }
}
