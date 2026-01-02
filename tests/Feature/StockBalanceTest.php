<?php

namespace Tests\Feature;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\Unit;
use App\Domain\Warehouse;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockBalanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    protected function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_receipt_and_waste_adjust_balance(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::where('name', 'Central Warehouse')->firstOrFail();
        $item = Item::factory()->create(['organization_id' => $warehouse->organization_id]);
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $g = Unit::where('slug', 'g')->firstOrFail();

        // Receipt: 1 kg = 1000 g
        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_RECEIPT,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'supplier_name' => 'Test Supplier',
                'reference' => 'INV-1',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 1,
                'unit_cost' => 10,
            ])->assertRedirect(route('stock.ledger'));

        // Waste: 200 g
        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_WASTE,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'reason' => 'Spillage',
                'item_id' => $item->id,
                'unit_id' => $g->id,
                'quantity' => 200,
            ])->assertRedirect(route('stock.ledger'));

        $balance = app(StockService::class)->balanceForItemInWarehouse($item->id, $warehouse->id);

        $this->assertEquals(800.0, $balance);
    }

    public function test_internal_use_reduces_balance(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::where('name', 'Central Warehouse')->firstOrFail();
        $item = Item::factory()->create(['organization_id' => $warehouse->organization_id]);
        $kg = Unit::where('slug', 'kg')->firstOrFail();

        // Seed 2 kg receipt
        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_RECEIPT,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'supplier_name' => 'Test Supplier',
                'reference' => 'INV-2',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 2,
            ])->assertRedirect(route('stock.ledger'));

        // Internal use 0.5 kg
        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_INTERNAL_USE,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'reason' => 'Prep',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 0.5,
            ])->assertRedirect(route('stock.ledger'));

        $balance = app(StockService::class)->balanceForItemInWarehouse($item->id, $warehouse->id);

        $this->assertEquals(1500.0, $balance);
    }
}
