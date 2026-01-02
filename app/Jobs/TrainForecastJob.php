<?php

namespace App\Jobs;

use App\Services\ForecastService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrainForecastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int>  $itemIds
     */
    public function __construct(
        public int $organizationId,
        public int $locationId,
        public array $itemIds,
        public ?int $requestedBy = null,
        public int $lookbackDays = 60
    ) {
    }

    public function handle(ForecastService $service): void
    {
        $end = Carbon::now();
        $start = Carbon::now()->subDays($this->lookbackDays);

        $service->train(
            $this->organizationId,
            $this->locationId,
            $this->itemIds,
            $this->requestedBy,
            $start,
            $end
        );
    }
}
