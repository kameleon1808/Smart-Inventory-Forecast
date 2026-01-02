<?php

namespace Tests\Feature;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockCount;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\Unit;
use App\Domain\Warehouse;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountTest extends TestCase
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

    protected function waiter(): User
    {
        return User::where('email', 'waiter@example.com')->firstOrFail();
    }

    public function test_posting_stock_count_adjusts_to_counted_balance(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::where('name', 'Central Warehouse')->firstOrFail();
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => $warehouse->organization_id,
            'base_unit_id' => $kg->id,
        ]);

        // Seed receipt: 5 kg
        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock', [
                'type' => StockTransaction::TYPE_RECEIPT,
                'warehouse_id' => $warehouse->id,
                'happened_at' => now()->format('Y-m-d H:i'),
                'supplier_name' => 'Supplier',
                'item_id' => $item->id,
                'unit_id' => $kg->id,
                'quantity' => 5,
            ]);

        // Stock count: counted 3 kg
        $response = $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock-counts', [
                'warehouse_id' => $warehouse->id,
                'counted_at' => now()->format('Y-m-d\TH:i'),
                'lines' => [
                    ['item_id' => $item->id, 'counted_quantity' => 3],
                ],
            ]);

        $count = StockCount::firstOrFail();

        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post(route('stock-counts.post', $count))
            ->assertRedirect(route('stock.ledger'));

        $balance = app(StockService::class)->balanceForItemInWarehouse($item->id, $warehouse->id);
        $this->assertEquals(3.0, $balance);
    }

    public function test_waiter_cannot_post_stock_count(): void
    {
        $warehouse = Warehouse::where('name', 'Central Warehouse')->firstOrFail();
        $item = Item::factory()->create(['organization_id' => $warehouse->organization_id]);

        $this->actingAs($this->admin())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock-counts', [
                'warehouse_id' => $warehouse->id,
                'counted_at' => now()->format('Y-m-d\TH:i'),
                'lines' => [
                    ['item_id' => $item->id, 'counted_quantity' => 1],
                ],
            ]);

        $count = StockCount::firstOrFail();

        $this->actingAs($this->waiter())
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post(route('stock-counts.post', $count))
            ->assertForbidden();
    }
}
