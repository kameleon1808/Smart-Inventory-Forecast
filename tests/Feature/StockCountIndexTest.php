<?php

namespace Tests\Feature;

use App\Domain\Inventory\StockCount;
use App\Domain\Warehouse;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockCountIndexTest extends TestCase
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

    public function test_index_shows_counts_and_links_to_edit(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::first();

        $count = StockCount::create([
            'organization_id' => $warehouse->organization_id,
            'location_id' => $warehouse->location_id,
            'warehouse_id' => $warehouse->id,
            'status' => StockCount::STATUS_DRAFT,
            'counted_at' => now(),
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->get('/stock-counts')
            ->assertOk()
            ->assertSee('Stock counts')
            ->assertSee('Edit')
            ->assertSee((string) $count->id);
    }

    public function test_store_redirects_to_index_with_flash(): void
    {
        $admin = $this->admin();
        $warehouse = Warehouse::first();
        $item = \App\Domain\Inventory\Item::factory()->create(['organization_id' => $warehouse->organization_id]);

        $response = $this->actingAs($admin)
            ->withSession(['active_location_id' => $warehouse->location_id])
            ->post('/stock-counts', [
                'warehouse_id' => $warehouse->id,
                'counted_at' => now()->format('Y-m-d\TH:i'),
                'lines' => [
                    ['item_id' => $item->id, 'counted_quantity' => 5],
                ],
            ]);

        $response->assertRedirect(route('stock-counts.index'));

        $this->get(route('stock-counts.index'))
            ->assertSee('Stock count saved as draft.')
            ->assertSee((string) StockCount::latest('id')->first()->id);
    }
}
