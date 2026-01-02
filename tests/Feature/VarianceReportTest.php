<?php

namespace Tests\Feature;

use App\Domain\Inventory\ExpectedConsumptionDaily;
use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\Unit;
use App\Domain\Warehouse;
use App\Services\VarianceReportService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VarianceReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    public function test_variance_calculates_expected_vs_actual(): void
    {
        $warehouse = Warehouse::where('name', 'Central Warehouse')->firstOrFail();
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $g = Unit::where('slug', 'g')->firstOrFail();

        $item = Item::factory()->create([
            'organization_id' => $warehouse->organization_id,
            'base_unit_id' => $g->id,
        ]);

        // expected: 100 g
        ExpectedConsumptionDaily::create([
            'organization_id' => $warehouse->organization_id,
            'location_id' => $warehouse->location_id,
            'date' => '2025-01-10',
            'item_id' => $item->id,
            'expected_qty_in_base' => 100,
        ]);

        $this->assertDatabaseHas('expected_consumption_dailies', [
            'organization_id' => $warehouse->organization_id,
            'location_id' => $warehouse->location_id,
            'item_id' => $item->id,
            'expected_qty_in_base' => 100,
            'date' => '2025-01-10 00:00:00',
        ]);

        // actual: waste 0.03 kg (30 g) and internal 50 g, adjustment -20 g -> total 100 g
        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_WASTE,
                'warehouse_id' => $warehouse->id,
                'happened_at' => '2025-01-10 10:00:00',
                'reason' => 'W',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 0.03,
            ]);

        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_INTERNAL_USE,
                'warehouse_id' => $warehouse->id,
                'happened_at' => '2025-01-10 11:00:00',
                'reason' => 'Prep',
                'item_id' => $item->id,
                'unit_id' => $g->id,
                'quantity' => 50,
            ]);

        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_ADJUSTMENT,
                'warehouse_id' => $warehouse->id,
                'happened_at' => '2025-01-10 12:00:00',
                'item_id' => $item->id,
                'unit_id' => $g->id,
                'quantity' => -20,
            ]);

        $report = app(VarianceReportService::class)
            ->calculate($warehouse->organization_id, $warehouse->location_id, '2025-01-10', '2025-01-10');

        $row = $report->firstWhere('item_id', $item->id);

        $this->assertNotNull($row);
        $this->assertEquals(100.0, $row['expected']);
        $this->assertEquals(100.0, $row['actual']);
        $this->assertEquals(0.0, $row['variance']);
    }

    private function admin()
    {
        return \App\Models\User::where('email', 'admin@example.com')->firstOrFail();
    }
}
