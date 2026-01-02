<?php

namespace App\Jobs;

use App\Domain\Location;
use App\Services\AnomalyDetectionService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AnomalyScanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $days;

    public function __construct(int $days = 7)
    {
        $this->days = $days;
    }

    public function handle(AnomalyDetectionService $service): void
    {
        $end = Carbon::yesterday()->endOfDay();
        $start = (clone $end)->subDays($this->days - 1)->startOfDay();

        $locations = Location::with('organization')->get();

        foreach ($locations as $location) {
            $service->detectForRange($location->organization_id, $location->id, $start, $end);
        }
    }
}
