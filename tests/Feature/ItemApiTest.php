<?php

namespace Tests\Feature;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use App\Domain\Inventory\Unit;
use App\Domain\Organization;
use App\Models\User;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemApiTest extends TestCase
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

    public function test_can_list_items_with_filters(): void
    {
        $organization = Organization::firstOrFail();
        $category = ItemCategory::firstOrFail();
        $unit = Unit::firstOrFail();

        Item::factory()->create([
            'organization_id' => $organization->id,
            'category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'name' => 'Espresso Beans',
            'sku' => 'COF-001',
            'is_active' => true,
        ]);

        Item::factory()->create([
            'organization_id' => $organization->id,
            'category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'name' => 'Inactive Item',
            'sku' => 'COF-002',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson('/api/items?search=Espresso&is_active=1');

        $response->assertOk()->assertJsonFragment(['sku' => 'COF-001']);
        $response->assertJsonMissing(['sku' => 'COF-002']);
    }

    public function test_can_create_item(): void
    {
        $organization = Organization::firstOrFail();
        $category = ItemCategory::where('name', 'Coffee')->firstOrFail();
        $unit = Unit::where('slug', 'kg')->firstOrFail();

        $payload = [
            'sku' => 'COF-003',
            'name' => 'House Blend',
            'category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'pack_size' => 1,
            'min_stock' => 5,
            'safety_stock' => 2,
            'lead_time_days' => 3,
            'shelf_life_days' => 90,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin())
            ->postJson('/api/items', $payload);

        $response->assertCreated()->assertJsonFragment(['sku' => 'COF-003']);

        $this->assertDatabaseHas('items', [
            'organization_id' => $organization->id,
            'sku' => 'COF-003',
        ]);
    }

    public function test_can_update_item(): void
    {
        $organization = Organization::firstOrFail();
        $category = ItemCategory::where('name', 'Coffee')->firstOrFail();
        $unit = Unit::where('slug', 'kg')->firstOrFail();

        $item = Item::factory()->create([
            'organization_id' => $organization->id,
            'category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'sku' => 'COF-004',
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->admin())
            ->putJson("/api/items/{$item->id}", [
                'sku' => 'COF-004',
                'name' => 'Updated Name',
                'category_id' => $category->id,
                'base_unit_id' => $unit->id,
                'pack_size' => 2,
                'min_stock' => 4,
                'safety_stock' => 2,
                'lead_time_days' => 5,
                'shelf_life_days' => null,
                'is_active' => false,
            ]);

        $response->assertOk()->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Updated Name',
            'is_active' => false,
        ]);
    }

    public function test_can_view_single_item(): void
    {
        $organization = Organization::firstOrFail();
        $category = ItemCategory::where('name', 'Coffee')->firstOrFail();
        $unit = Unit::where('slug', 'kg')->firstOrFail();

        $item = Item::factory()->create([
            'organization_id' => $organization->id,
            'category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'sku' => 'COF-005',
            'name' => 'View Me',
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson("/api/items/{$item->id}");

        $response->assertOk()->assertJsonFragment(['sku' => 'COF-005']);
    }
}
