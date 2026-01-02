<?php

namespace App\Services;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Get current balance for an item in a warehouse (in base units).
     */
    public function balanceForItemInWarehouse(int $itemId, int $warehouseId): float
    {
        return (float) DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.warehouse_id', $warehouseId)
            ->where('l.item_id', $itemId)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->sum('l.quantity_in_base');
    }

    /**
     * Balances for many items in a warehouse keyed by item_id.
     *
     * @param  array<int>  $itemIds
     * @return array<int, float>
     */
    public function balancesForItemsInWarehouse(array $itemIds, int $warehouseId): array
    {
        if (empty($itemIds)) {
            return [];
        }

        return DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->selectRaw('l.item_id, SUM(l.quantity_in_base) as balance')
            ->where('t.warehouse_id', $warehouseId)
            ->whereIn('l.item_id', $itemIds)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->groupBy('l.item_id')
            ->pluck('balance', 'item_id')
            ->map(fn ($balance) => (float) $balance)
            ->toArray();
    }

    /**
     * Balances for an item across all warehouses keyed by warehouse_id.
     *
     * @return array<int, float>
     */
    public function balancesForItem(Item $item): array
    {
        return DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->selectRaw('t.warehouse_id, SUM(l.quantity_in_base) as balance')
            ->where('l.item_id', $item->id)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->groupBy('t.warehouse_id')
            ->pluck('balance', 'warehouse_id')
            ->map(fn ($balance) => (float) $balance)
            ->toArray();
    }
}
