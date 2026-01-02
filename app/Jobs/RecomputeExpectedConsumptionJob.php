<?php

namespace App\Jobs;

use App\Services\ExpectedConsumptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecomputeExpectedConsumptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $organizationId,
        public int $locationId,
        public string $fromDate,
        public string $toDate,
    ) {
    }

    public function handle(ExpectedConsumptionService $service): void
    {
        $service->recompute($this->organizationId, $this->locationId, $this->fromDate, $this->toDate);
    }
}
