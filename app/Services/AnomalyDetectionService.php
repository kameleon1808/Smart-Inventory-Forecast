<?php

namespace App\Services;

use App\Domain\Anomaly\Anomaly;
use App\Domain\Anomaly\AnomalyThreshold;
use App\Domain\Inventory\ExpectedConsumptionDaily;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Services\VarianceReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnomalyDetectionService
{
    public const TYPE_WASTE_SPIKE = 'waste_spike';
    public const TYPE_VARIANCE_SPIKE = 'variance_spike';
    public const TYPE_ADJUSTMENT_COUNT = 'adjustment_count';

    public function detectForRange(int $organizationId, int $locationId, Carbon $start, Carbon $end): void
    {
        $items = Item::where('organization_id', $organizationId)->get()->keyBy('id');
        $thresholds = $this->thresholds($organizationId, $locationId);

        $this->detectWasteSpikes($organizationId, $locationId, $start, $end, $items, $thresholds);
        $this->detectVarianceSpikes($organizationId, $locationId, $start, $end, $items, $thresholds);
        $this->detectAdjustmentCounts($organizationId, $locationId, $start, $end, $items, $thresholds);
    }

    /**
     * @param  Collection<int, Item>  $items
     * @param  array<string, array>  $thresholds
     */
    private function detectWasteSpikes(
        int $organizationId,
        int $locationId,
        Carbon $start,
        Carbon $end,
        Collection $items,
        array $thresholds
    ): void {
        $expected = ExpectedConsumptionDaily::where('organization_id', $organizationId)
            ->where('location_id', $locationId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->reduce(function (array $carry, $row) {
                $carry[$row->item_id.'-'.$row->date->toDateString()] = (float) $row->expected_qty_in_base;

                return $carry;
            }, []);

        $wasteRows = DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.organization_id', $organizationId)
            ->where('t.location_id', $locationId)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->where('t.type', StockTransaction::TYPE_WASTE)
            ->whereBetween('t.happened_at', [$start, $end])
            ->selectRaw('l.item_id, date(t.happened_at) as day, SUM(-1 * l.quantity_in_base) as waste_qty')
            ->groupBy('l.item_id', 'day')
            ->get();

        foreach ($wasteRows as $row) {
            $itemId = (int) $row->item_id;
            $item = $items->get($itemId);
            $expectedKey = $itemId.'-'.$row->day;
            $expectedQty = $expected[$expectedKey] ?? 0.0;

            $threshold = $this->resolveThreshold(self::TYPE_WASTE_SPIKE, $item, $thresholds);

            $absolute = $threshold['absolute'] ?? 0.0;
            $percent = $threshold['percent'] ?? 0.0;
            $calculatedThreshold = max($absolute, $expectedQty * ($percent / 100));

            if ($calculatedThreshold <= 0) {
                $calculatedThreshold = $absolute > 0 ? $absolute : 10.0;
            }

            $wasteQty = (float) $row->waste_qty;

            if ($wasteQty <= $calculatedThreshold) {
                continue;
            }

            $severity = $wasteQty > ($calculatedThreshold * 1.5) ? 'high' : ($threshold['severity'] ?? 'medium');

            Anomaly::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'location_id' => $locationId,
                    'type' => self::TYPE_WASTE_SPIKE,
                    'item_id' => $itemId,
                    'happened_on' => $row->day,
                ],
                [
                    'severity' => $severity,
                    'metric_value' => $wasteQty,
                    'threshold_value' => $calculatedThreshold,
                    'status' => Anomaly::STATUS_OPEN,
                ]
            );
        }
    }

    /**
     * @param  Collection<int, Item>  $items
     * @param  array<string, array>  $thresholds
     */
    private function detectVarianceSpikes(
        int $organizationId,
        int $locationId,
        Carbon $start,
        Carbon $end,
        Collection $items,
        array $thresholds
    ): void {
        $varianceService = app(VarianceReportService::class);
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            $varianceRows = $varianceService->calculate(
                $organizationId,
                $locationId,
                $cursor->toDateString(),
                $cursor->toDateString()
            );

            foreach ($varianceRows as $row) {
                if ($row['expected'] === 0.0 || $row['variance_percent'] === null) {
                    continue;
                }

                $item = $items->get($row['item_id']);
                $threshold = $this->resolveThreshold(self::TYPE_VARIANCE_SPIKE, $item, $thresholds);
                $percentThreshold = (float) ($threshold['percent'] ?? 50.0);

                if (abs($row['variance_percent']) <= $percentThreshold) {
                    continue;
                }

                $severity = abs($row['variance_percent']) > ($percentThreshold * 1.5)
                    ? 'high'
                    : ($threshold['severity'] ?? 'medium');

                Anomaly::firstOrCreate(
                    [
                        'organization_id' => $organizationId,
                        'location_id' => $locationId,
                        'type' => self::TYPE_VARIANCE_SPIKE,
                        'item_id' => $row['item_id'],
                        'happened_on' => $cursor->toDateString(),
                    ],
                    [
                        'severity' => $severity,
                        'metric_value' => $row['variance_percent'],
                        'threshold_value' => $percentThreshold,
                        'status' => Anomaly::STATUS_OPEN,
                    ]
                );
            }

            $cursor->addDay();
        }
    }

    /**
     * @param  Collection<int, Item>  $items
     * @param  array<string, array>  $thresholds
     */
    private function detectAdjustmentCounts(
        int $organizationId,
        int $locationId,
        Carbon $start,
        Carbon $end,
        Collection $items,
        array $thresholds
    ): void {
        $rows = DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.organization_id', $organizationId)
            ->where('t.location_id', $locationId)
            ->where('t.status', StockTransaction::STATUS_POSTED)
            ->where('t.type', StockTransaction::TYPE_ADJUSTMENT)
            ->whereBetween('t.happened_at', [$start, $end])
            ->selectRaw('l.item_id, COUNT(*) as adjustments')
            ->groupBy('l.item_id')
            ->get();

        foreach ($rows as $row) {
            $item = $items->get((int) $row->item_id);
            $threshold = $this->resolveThreshold(self::TYPE_ADJUSTMENT_COUNT, $item, $thresholds);
            $countThreshold = $threshold['count'] ?? 3;

            if ((int) $row->adjustments <= $countThreshold) {
                continue;
            }

            Anomaly::firstOrCreate(
                [
                    'organization_id' => $organizationId,
                    'location_id' => $locationId,
                    'type' => self::TYPE_ADJUSTMENT_COUNT,
                    'item_id' => $row->item_id,
                    'happened_on' => $end->toDateString(),
                ],
                [
                    'severity' => $threshold['severity'] ?? 'medium',
                    'metric_value' => (int) $row->adjustments,
                    'threshold_value' => $countThreshold,
                    'status' => Anomaly::STATUS_OPEN,
                ]
            );
        }
    }

    /**
     * @return array<string, array>
     */
    private function thresholds(int $organizationId, int $locationId): array
    {
        $rows = AnomalyThreshold::where('organization_id', $organizationId)
            ->where('location_id', $locationId)
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row->type][] = $row;
        }

        return $grouped;
    }

    /**
     * @param  array<string, array>  $thresholds
     */
    private function resolveThreshold(string $type, ?Item $item, array $thresholds): array
    {
        $candidates = $thresholds[$type] ?? [];
        $itemId = $item?->id;
        $categoryId = $item?->category_id;

        $match = collect($candidates)->first(function ($threshold) use ($itemId, $categoryId) {
            if ($threshold->item_id && $threshold->item_id === $itemId) {
                return true;
            }

            if ($threshold->category_id && $threshold->category_id === $categoryId) {
                return true;
            }

            return ! $threshold->item_id && ! $threshold->category_id;
        });

        if (! $match) {
            return [
                'absolute' => 10.0,
                'percent' => 50.0,
                'count' => 3,
                'severity' => 'medium',
            ];
        }

        return [
            'absolute' => $match->absolute_threshold,
            'percent' => $match->percent_threshold,
            'count' => $match->count_threshold,
            'severity' => $match->severity,
        ];
    }
}
