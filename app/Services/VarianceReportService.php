<?php

namespace App\Services;

use App\Domain\Inventory\ExpectedConsumptionDaily;
use App\Domain\Inventory\StockTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VarianceReportService
{
    /**
     * @return Collection<int, array{item_id:int, expected:float, actual:float, variance:float, variance_percent:float|null, net_change:float}>
     */
    public function calculate(int $organizationId, int $locationId, string $fromDate, string $toDate, ?int $warehouseId = null): Collection
    {
        $from = Carbon::parse($fromDate)->startOfDay();
        $to = Carbon::parse($toDate)->endOfDay();
        $fromDateString = $from->toDateString();
        $toDateString = $to->toDateString();

        $expected = ExpectedConsumptionDaily::where('organization_id', $organizationId)
            ->where('location_id', $locationId)
            ->whereDate('date', '>=', $fromDateString)
            ->whereDate('date', '<=', $toDateString)
            ->selectRaw('item_id, SUM(expected_qty_in_base) as expected')
            ->groupBy('item_id')
            ->pluck('expected', 'item_id')
            ->map(fn ($val) => (float) $val);

        $actual = DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.location_id', $locationId)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->whereBetween('t.happened_at', [$from, $to])
            ->when($warehouseId, fn ($q) => $q->where('t.warehouse_id', $warehouseId))
            ->whereIn('t.type', [
                StockTransaction::TYPE_WASTE,
                StockTransaction::TYPE_INTERNAL_USE,
                StockTransaction::TYPE_ADJUSTMENT,
            ])
            ->selectRaw('l.item_id, SUM(CASE WHEN t.type IN (?, ?) THEN -1 * l.quantity_in_base WHEN t.type = ? AND l.quantity_in_base < 0 THEN -1 * l.quantity_in_base ELSE 0 END) as actual', [
                StockTransaction::TYPE_WASTE,
                StockTransaction::TYPE_INTERNAL_USE,
                StockTransaction::TYPE_ADJUSTMENT,
            ])
            ->groupBy('l.item_id')
            ->pluck('actual', 'l.item_id')
            ->map(fn ($val) => (float) $val);

        $netChange = DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.location_id', $locationId)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->whereBetween('t.happened_at', [$from, $to])
            ->when($warehouseId, fn ($q) => $q->where('t.warehouse_id', $warehouseId))
            ->selectRaw('l.item_id, SUM(l.quantity_in_base) as net_change')
            ->groupBy('l.item_id')
            ->pluck('net_change', 'l.item_id')
            ->map(fn ($val) => (float) $val);

        $itemIds = collect(array_unique(array_merge(
            $expected->keys()->all(),
            $actual->keys()->all(),
            $netChange->keys()->all()
        )));

        return $itemIds->map(function ($itemId) use ($expected, $actual, $netChange) {
            $exp = $expected[$itemId] ?? 0.0;
            $act = $actual[$itemId] ?? 0.0;
            $variance = $act - $exp;
            $variancePercent = $exp !== 0.0 ? ($variance / $exp) * 100 : null;

            return [
                'item_id' => (int) $itemId,
                'expected' => $exp,
                'actual' => $act,
                'variance' => $variance,
                'variance_percent' => $variancePercent,
                'net_change' => $netChange[$itemId] ?? 0.0,
            ];
        });
    }
}
