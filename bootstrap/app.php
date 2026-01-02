<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Domain\Location;
use App\Jobs\PredictForecastJob;
use App\Jobs\TrainForecastJob;
use App\Jobs\AnomalyScanJob;
use App\Services\ForecastService;
use App\Http\Middleware\EnsureOrganizationAndLocation;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'org.context' => EnsureOrganizationAndLocation::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->call(function () {
            $locations = Location::all();
            $service = app(ForecastService::class);

            foreach ($locations as $location) {
                $itemIds = $service->defaultItemIds($location->organization_id);

                if (empty($itemIds)) {
                    continue;
                }

                TrainForecastJob::dispatch(
                    $location->organization_id,
                    $location->id,
                    $itemIds,
                    null
                );
            }
        })->weeklyOn(1, '02:00');

        $schedule->call(function () {
            $locations = Location::all();
            $service = app(ForecastService::class);

            foreach ($locations as $location) {
                $itemIds = $service->defaultItemIds($location->organization_id);

                if (empty($itemIds)) {
                    continue;
                }

                PredictForecastJob::dispatch(
                    $location->organization_id,
                    $location->id,
                    $itemIds,
                    14,
                    null
                );
            }
        })->dailyAt('03:00');

        $schedule->job(new AnomalyScanJob())->dailyAt('04:00');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
