<?php

namespace App\Jobs;

use App\Services\ForecastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PredictForecastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int>  $itemIds
     */
    public function __construct(
        public int $organizationId,
        public int $locationId,
        public array $itemIds,
        public int $horizonDays = 14,
        public ?int $requestedBy = null
    ) {
    }

    public function handle(ForecastService $service): void
    {
        $service->predict(
            $this->organizationId,
            $this->locationId,
            $this->itemIds,
            $this->horizonDays,
            $this->requestedBy
        );
    }
}
