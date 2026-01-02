<?php

namespace Tests\Feature;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\Unit;
use App\Domain\Warehouse;
use App\Services\ProcurementSuggestionService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    public function test_suggestion_calculates_needed_with_pack_rounding(): void
    {
        $warehouse = Warehouse::where('name', 'Central Warehouse')->firstOrFail();
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => $warehouse->organization_id,
            'base_unit_id' => $kg->id,
            'pack_size' => 5,
            'safety_stock' => 20,
            'lead_time_days' => 2,
        ]);

        // Seed current stock: receipt 100
        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_RECEIPT,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'supplier_name' => 'Supplier',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 100,
            ]);

        // Consumption over 14 days: waste 140 (10/day avg)
        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_WASTE,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'reason' => 'Use',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 140,
            ]);

        // Add receipt to restore stock to 100
        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_RECEIPT,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'supplier_name' => 'Supplier 2',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 140,
            ]);

        $suggestions = app(ProcurementSuggestionService::class)
            ->suggestions($warehouse->organization_id, $warehouse->location_id, $warehouse->id);

        $row = $suggestions->firstWhere('item.id', $item->id);
        $this->assertNotNull($row);

        // avg 10/day, horizon 9 -> forecast 90. Needed = 90 + 20 - 100 = 10, rounded to pack 5 => 10.
        $this->assertEquals(10.0, $row['suggested_qty']);
    }

    private function admin()
    {
        return \App\Models\User::where('email', 'admin@example.com')->firstOrFail();
    }
}
