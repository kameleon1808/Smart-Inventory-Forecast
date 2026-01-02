<?php

namespace Tests\Feature;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\Unit;
use App\Domain\Location;
use App\Jobs\PredictForecastJob;
use App\Services\ForecastService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ForecastIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    public function test_predict_job_stores_results_from_forecast_service(): void
    {
        $location = Location::firstOrFail();
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => $location->organization_id,
            'base_unit_id' => $kg->id,
        ]);

        Http::fake([
            '*' => Http::response([
                'predictions' => [
                    [
                        'item_id' => $item->id,
                        'date' => now()->addDay()->toDateString(),
                        'prediction' => 5.5,
                        'ci_lower' => 4.5,
                        'ci_upper' => 6.5,
                    ],
                ],
            ]),
        ]);

        $job = new PredictForecastJob(
            $location->organization_id,
            $location->id,
            [$item->id],
            1,
            null
        );

        $job->handle(app(ForecastService::class));

        $this->assertDatabaseHas('forecast_result_dailies', [
            'organization_id' => $location->organization_id,
            'location_id' => $location->id,
            'item_id' => $item->id,
            'predicted_qty_in_base' => 5.5,
        ]);
    }

    public function test_generate_route_dispatches_job(): void
    {
        $location = Location::firstOrFail();
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => $location->organization_id,
            'base_unit_id' => $kg->id,
        ]);

        Bus::fake();

        $response = $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $location->id])
            ->post(route('forecast.run'), [
                'location_id' => $location->id,
                'horizon' => 7,
                'item_id' => $item->id,
            ]);

        $response->assertRedirect();

        Bus::assertDispatched(PredictForecastJob::class, function (PredictForecastJob $job) use ($location, $item) {
            return $job->locationId === $location->id
                && $job->organizationId === $location->organization_id
                && $job->horizonDays === 7
                && in_array($item->id, $job->itemIds, true);
        });
    }

    private function admin()
    {
        return \App\Models\User::where('email', 'admin@example.com')->firstOrFail();
    }
}
