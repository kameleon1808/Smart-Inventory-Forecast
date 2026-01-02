<?php

namespace App\Services;

use App\Domain\Forecast\ForecastJob;
use App\Domain\Forecast\ForecastResultDaily;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ForecastService
{
    public function __construct(private readonly ForecastClient $client)
    {
    }

    /**
     * @param  array<int>  $itemIds
     */
    public function train(
        int $organizationId,
        int $locationId,
        array $itemIds,
        ?int $requestedBy,
        Carbon $start,
        Carbon $end
    ): ForecastJob {
        $job = ForecastJob::create([
            'organization_id' => $organizationId,
            'location_id' => $locationId,
            'status' => ForecastJob::STATUS_PENDING,
            'requested_by' => $requestedBy,
            'params' => [
                'action' => 'train',
                'item_ids' => $itemIds,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
            ],
        ]);

        $history = $this->buildHistory($organizationId, $locationId, $itemIds, $start, $end);

        if (empty($history)) {
            $job->update(['status' => ForecastJob::STATUS_FAILED]);

            throw new \InvalidArgumentException('No consumption history available for training.');
        }

        try {
            $job->update(['status' => ForecastJob::STATUS_RUNNING]);
            $this->client->train(
                $organizationId,
                $locationId,
                $itemIds,
                $start->toDateString(),
                $end->toDateString(),
                $history
            );
            $job->update(['status' => ForecastJob::STATUS_COMPLETED]);
        } catch (Throwable $e) {
            $job->update(['status' => ForecastJob::STATUS_FAILED]);

            throw $e;
        }

        return $job;
    }

    /**
     * @param  array<int>  $itemIds
     */
    public function predict(
        int $organizationId,
        int $locationId,
        array $itemIds,
        int $horizonDays,
        ?int $requestedBy
    ): ForecastJob {
        $job = ForecastJob::create([
            'organization_id' => $organizationId,
            'location_id' => $locationId,
            'status' => ForecastJob::STATUS_PENDING,
            'requested_by' => $requestedBy,
            'params' => [
                'action' => 'predict',
                'item_ids' => $itemIds,
                'horizon_days' => $horizonDays,
            ],
        ]);

        try {
            $job->update(['status' => ForecastJob::STATUS_RUNNING]);
            $response = $this->client->predict(
                $organizationId,
                $locationId,
                $itemIds,
                $horizonDays
            );

            $this->storePredictions(
                $organizationId,
                $locationId,
                $response['predictions'] ?? [],
                'baseline'
            );

            $job->update(['status' => ForecastJob::STATUS_COMPLETED]);
        } catch (Throwable $e) {
            $job->update(['status' => ForecastJob::STATUS_FAILED]);

            throw $e;
        }

        return $job;
    }

    /**
     * @param  array<int>  $itemIds
     * @return array<int, array<string, mixed>>
     */
    public function buildHistory(
        int $organizationId,
        int $locationId,
        array $itemIds,
        Carbon $start,
        Carbon $end
    ): array {
        $rows = DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.organization_id', $organizationId)
            ->where('t.location_id', $locationId)
            ->whereBetween('t.happened_at', [$start->startOfDay(), $end->endOfDay()])
            ->whereIn('l.item_id', $itemIds)
            ->whereIn('t.type', [
                StockTransaction::TYPE_WASTE,
                StockTransaction::TYPE_INTERNAL_USE,
                StockTransaction::TYPE_ADJUSTMENT,
                StockTransaction::TYPE_STOCK_COUNT_ADJUSTMENT,
            ])
            ->selectRaw('l.item_id, date(t.happened_at) as day, SUM(l.quantity_in_base) as total')
            ->groupBy('l.item_id', 'day')
            ->get();

        $history = [];
        foreach ($rows as $row) {
            $qty = max(0, -(float) $row->total);

            if ($qty <= 0) {
                continue;
            }

            $history[] = [
                'item_id' => (int) $row->item_id,
                'date' => $row->day,
                'quantity' => round($qty, 4),
            ];
        }

        return $history;
    }

    /**
     * @param  array<int, array<string, mixed>>  $predictions
     */
    public function storePredictions(
        int $organizationId,
        int $locationId,
        array $predictions,
        string $modelVersion
    ): void {
        if (empty($predictions)) {
            return;
        }

        $dates = collect($predictions)->pluck('date')->unique();
        $itemIds = collect($predictions)->pluck('item_id')->unique();

        ForecastResultDaily::where('organization_id', $organizationId)
            ->where('location_id', $locationId)
            ->whereIn('item_id', $itemIds)
            ->whereIn('date', $dates)
            ->delete();

        $rows = [];
        foreach ($predictions as $prediction) {
            $rows[] = [
                'organization_id' => $organizationId,
                'location_id' => $locationId,
                'item_id' => (int) $prediction['item_id'],
                'date' => $prediction['date'],
                'predicted_qty_in_base' => (float) $prediction['prediction'],
                'lower' => (float) ($prediction['ci_lower'] ?? $prediction['lower'] ?? 0),
                'upper' => (float) ($prediction['ci_upper'] ?? $prediction['upper'] ?? 0),
                'model_version' => $modelVersion,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        ForecastResultDaily::insert($rows);
    }

    /**
     * @return array<int>
     */
    public function defaultItemIds(int $organizationId): array
    {
        return Item::where('organization_id', $organizationId)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }
}
