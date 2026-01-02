<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ForecastClient
{
    public function __construct(private readonly ?string $baseUrl = null)
    {
    }

    private function base(): string
    {
        return rtrim($this->baseUrl ?? config('services.forecast.base_url'), '/');
    }

    /**
     * @param  array<int>  $itemIds
     * @param  array<int, array<string, mixed>>  $history
     */
    public function train(int $orgId, int $locationId, array $itemIds, string $startDate, string $endDate, array $history): array
    {
        $response = Http::acceptJson()->post($this->base().'/train', [
            'org_id' => $orgId,
            'location_id' => $locationId,
            'item_ids' => array_values($itemIds),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'history' => $history,
        ]);

        if ($response->failed()) {
            throw new RuntimeException($response->body() ?: 'Forecast service train failed');
        }

        return $response->json();
    }

    /**
     * @param  array<int>  $itemIds
     */
    public function predict(int $orgId, int $locationId, array $itemIds, int $horizonDays): array
    {
        $response = Http::acceptJson()->post($this->base().'/predict', [
            'org_id' => $orgId,
            'location_id' => $locationId,
            'item_ids' => array_values($itemIds),
            'horizon_days' => $horizonDays,
            'method' => 'baseline',
        ]);

        if ($response->failed()) {
            throw new RuntimeException($response->body() ?: 'Forecast service predict failed');
        }

        return $response->json();
    }
}
