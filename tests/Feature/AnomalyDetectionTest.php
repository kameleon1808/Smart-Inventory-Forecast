<?php

namespace Tests\Feature;

use App\Domain\Anomaly\Anomaly;
use App\Domain\Anomaly\AnomalyThreshold;
use App\Domain\Inventory\ExpectedConsumptionDaily;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\Unit;
use App\Domain\Warehouse;
use App\Services\AnomalyDetectionService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnomalyDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    public function test_waste_spike_creates_anomaly(): void
    {
        $location = Warehouse::where('name', 'Central Warehouse')->firstOrFail()->location;
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => $location->organization_id,
            'base_unit_id' => $kg->id,
        ]);

        AnomalyThreshold::create([
            'organization_id' => $location->organization_id,
            'location_id' => $location->id,
            'type' => AnomalyDetectionService::TYPE_WASTE_SPIKE,
            'absolute_threshold' => 5,
            'percent_threshold' => 50,
            'severity' => 'medium',
        ]);

        ExpectedConsumptionDaily::create([
            'organization_id' => $location->organization_id,
            'location_id' => $location->id,
            'date' => now()->subDay()->toDateString(),
            'item_id' => $item->id,
            'expected_qty_in_base' => 4,
        ]);

        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $location->id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_WASTE,
                'warehouse_id' => $location->warehouses()->first()->id,
                'happened_at' => now()->subDay()->format('Y-m-d H:i'),
                'reason' => 'Spoilage',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 10,
            ])->assertRedirect();

        app(AnomalyDetectionService::class)->detectForRange(
            $location->organization_id,
            $location->id,
            now()->subDay()->startOfDay(),
            now()->subDay()->endOfDay()
        );

        $this->assertDatabaseHas('anomalies', [
            'type' => AnomalyDetectionService::TYPE_WASTE_SPIKE,
            'location_id' => $location->id,
            'item_id' => $item->id,
            'status' => Anomaly::STATUS_OPEN,
        ]);
    }

    public function test_variance_spike_creates_anomaly(): void
    {
        $location = Warehouse::where('name', 'Central Warehouse')->firstOrFail()->location;
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => $location->organization_id,
            'base_unit_id' => $kg->id,
        ]);

        AnomalyThreshold::create([
            'organization_id' => $location->organization_id,
            'location_id' => $location->id,
            'type' => AnomalyDetectionService::TYPE_VARIANCE_SPIKE,
            'percent_threshold' => 50,
            'severity' => 'medium',
        ]);

        $day = now()->subDay()->startOfDay();

        ExpectedConsumptionDaily::create([
            'organization_id' => $location->organization_id,
            'location_id' => $location->id,
            'date' => $day->toDateString(),
            'item_id' => $item->id,
            'expected_qty_in_base' => 10,
        ]);

        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $location->id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_WASTE,
                'warehouse_id' => $location->warehouses()->first()->id,
                'happened_at' => $day->copy()->addHours(2)->format('Y-m-d H:i'),
                'reason' => 'Spoilage',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 25,
            ]);

        app(AnomalyDetectionService::class)->detectForRange(
            $location->organization_id,
            $location->id,
            $day->copy(),
            $day->copy()->endOfDay()
        );

        $anomalies = \App\Domain\Anomaly\Anomaly::all()->toArray();
        $actualRows = \Illuminate\Support\Facades\DB::table('stock_transaction_lines as l')
            ->join('stock_transactions as t', 't.id', '=', 'l.stock_transaction_id')
            ->where('t.type', StockTransaction::TYPE_WASTE)
            ->selectRaw('l.item_id, date(t.happened_at) as day, SUM(CASE WHEN t.type IN (?, ?) THEN -1 * l.quantity_in_base WHEN t.type = ? AND l.quantity_in_base < 0 THEN -1 * l.quantity_in_base ELSE 0 END) as actual', [
                StockTransaction::TYPE_WASTE,
                StockTransaction::TYPE_INTERNAL_USE,
                StockTransaction::TYPE_ADJUSTMENT,
            ])
            ->groupBy('l.item_id', 'day')
            ->get()
            ->toArray();
        $expectedRows = \App\Domain\Inventory\ExpectedConsumptionDaily::all()->toArray();
        $thresholds = \App\Domain\Anomaly\AnomalyThreshold::all()->toArray();
        $variancePercent = (($actualRows[0]->actual ?? 0) - ($expectedRows[0]['expected_qty_in_base'] ?? 0))
            / max(0.0001, ($expectedRows[0]['expected_qty_in_base'] ?? 0)) * 100;
        $transactions = \Illuminate\Support\Facades\DB::table('stock_transactions')->select('organization_id', 'location_id', 'type', 'happened_at')->get()->toArray();

        $this->assertTrue(
            \App\Domain\Anomaly\Anomaly::where('type', AnomalyDetectionService::TYPE_VARIANCE_SPIKE)->exists(),
            'Anomalies: '.json_encode($anomalies).' Actual: '.json_encode($actualRows).' Expected: '.json_encode($expectedRows).' Thresholds: '.json_encode($thresholds).' Variance: '.$variancePercent.' Tx: '.json_encode($transactions)
        );

        $this->assertDatabaseHas('anomalies', [
            'type' => AnomalyDetectionService::TYPE_VARIANCE_SPIKE,
            'location_id' => $location->id,
            'item_id' => $item->id,
        ]);
    }

    protected function admin()
    {
        return \App\Models\User::where('email', 'admin@example.com')->firstOrFail();
    }
}
