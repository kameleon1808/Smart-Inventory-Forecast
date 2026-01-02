<?php

namespace Tests\Feature;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\StockTransaction;
use App\Domain\Inventory\Unit;
use App\Domain\Procurement\PurchaseOrder;
use App\Domain\Procurement\PurchaseOrderLine;
use App\Domain\Warehouse;
use App\Models\User;
use App\Services\StockService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseReceivingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    private function admin(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    public function test_partial_then_full_receipt_updates_status_and_stock(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::where('name', 'Central Warehouse')->firstOrFail();
        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => $warehouse->organization_id,
            'base_unit_id' => $kg->id,
        ]);

        $po = PurchaseOrder::create([
            'organization_id' => $warehouse->organization_id,
            'location_id' => $warehouse->location_id,
            'warehouse_id' => $warehouse->id,
            'supplier_name' => 'Beans Supplier',
            'status' => PurchaseOrder::STATUS_APPROVED,
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);

        PurchaseOrderLine::create([
            'purchase_order_id' => $po->id,
            'item_id' => $item->id,
            'qty_ordered_in_base' => 10,
            'unit_id_display' => $kg->id,
            'qty_display' => 10,
            'unit_cost_estimate' => 5.50,
        ]);

        // First receipt: 4 kg
        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post(route('procurement.purchase-orders.receive', $po), [
                'received_at' => now()->format('Y-m-d\TH:i'),
                'lines' => [
                    ['item_id' => $item->id, 'qty' => 4],
                ],
            ])
            ->assertRedirect(route('procurement.purchase-orders.show', $po));

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATUS_PARTIALLY_RECEIVED, $po->status);

        $stock = app(StockService::class)->balanceForItemInWarehouse($item->id, $warehouse->id);
        $this->assertEquals(4.0, $stock);

        // Second receipt: remaining 6 kg
        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post(route('procurement.purchase-orders.receive', $po), [
                'received_at' => now()->addHour()->format('Y-m-d\TH:i'),
                'lines' => [
                    ['item_id' => $item->id, 'qty' => 6],
                ],
            ])
            ->assertRedirect(route('procurement.purchase-orders.show', $po));

        $po->refresh();
        $this->assertEquals(PurchaseOrder::STATUS_CLOSED, $po->status);

        $stock = app(StockService::class)->balanceForItemInWarehouse($item->id, $warehouse->id);
        $this->assertEquals(10.0, $stock);

        $this->assertDatabaseCount('purchase_receipts', 2);
        $this->assertDatabaseCount('purchase_receipt_lines', 2);
        $this->assertEquals(2, StockTransaction::where('type', StockTransaction::TYPE_RECEIPT)->count());
    }
}
