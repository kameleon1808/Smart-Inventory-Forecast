<?php

namespace App\Services;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Procurement\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProcurementSuggestionService
{
    public function __construct(private readonly StockService $stockService)
    {
    }

    /**
     * @return Collection<int, array>
     */
    public function suggestions(int $organizationId, int $locationId, int $warehouseId): Collection
    {
        $items = Item::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->get();

        if ($items->isEmpty()) {
            return collect();
        }

        $now = Carbon::now();
        $from = $now->copy()->subDays(14)->startOfDay();

        $consumption = DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.location_id', $locationId)
            ->where('t.warehouse_id', $warehouseId)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->whereBetween('t.happened_at', [$from, $now])
            ->whereIn('t.type', [
                StockTransaction::TYPE_WASTE,
                StockTransaction::TYPE_INTERNAL_USE,
                StockTransaction::TYPE_ADJUSTMENT,
                StockTransaction::TYPE_STOCK_COUNT_ADJUSTMENT,
            ])
            ->selectRaw('l.item_id, SUM(CASE WHEN l.quantity_in_base < 0 THEN -1 * l.quantity_in_base ELSE 0 END) as consumed')
            ->groupBy('l.item_id')
            ->pluck('consumed', 'item_id')
            ->map(fn ($val) => (float) $val);

        $avgDaily = $consumption->map(fn ($val) => $val / 14);

        $openPo = DB::table('purchase_order_lines as pol')
            ->join('purchase_orders as po', 'po.id', '=', 'pol.purchase_order_id')
            ->where('po.organization_id', $organizationId)
            ->where('po.location_id', $locationId)
            ->where('po.warehouse_id', $warehouseId)
            ->whereIn('po.status', [
                PurchaseOrder::STATUS_DRAFT,
                PurchaseOrder::STATUS_SUBMITTED,
                PurchaseOrder::STATUS_APPROVED,
                PurchaseOrder::STATUS_SENT,
                PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
            ])
            ->selectRaw('pol.item_id, SUM(pol.qty_ordered_in_base) as qty')
            ->groupBy('pol.item_id')
            ->pluck('qty', 'item_id')
            ->map(fn ($val) => (float) $val);

        return $items->map(function (Item $item) use ($avgDaily, $openPo, $warehouseId) {
            $currentStock = $this->stockService->balanceForItemInWarehouse($item->id, $warehouseId);
            $avg = $avgDaily[$item->id] ?? 0.0;
            $horizon = $item->lead_time_days + 7;
            $forecast = $avg * $horizon;
            $neededRaw = $forecast + $item->safety_stock - $currentStock - ($openPo[$item->id] ?? 0);
            $needed = max(0, $neededRaw);

            $pack = $item->pack_size ?: 1;
            $rounded = $needed > 0 ? ceil($needed / $pack) * $pack : 0;

            return [
                'item' => $item,
                'current_stock' => $currentStock,
                'avg_daily' => $avg,
                'forecast' => $forecast,
                'open_po' => $openPo[$item->id] ?? 0.0,
                'needed' => $needed,
                'suggested_qty' => $rounded,
                'pack_size' => $pack,
            ];
        })->filter(fn ($row) => $row['suggested_qty'] > 0);
    }
}
