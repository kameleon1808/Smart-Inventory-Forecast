<?php

namespace Tests\Feature;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\Unit;
use App\Domain\Recipes\MenuItem;
use App\Services\RecipeService;
use Database\Seeders\InventorySeeder;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
        $this->seed(InventorySeeder::class);
    }

    public function test_prevents_overlapping_versions(): void
    {
        $menuItem = MenuItem::create([
            'organization_id' => 1,
            'name' => 'Latte',
            'is_active' => true,
        ]);

        $g = Unit::where('slug', 'g')->firstOrFail();
        $item = Item::factory()->create([
            'organization_id' => 1,
            'base_unit_id' => $g->id,
        ]);

        $service = app(RecipeService::class);

        $service->createVersion($menuItem, '2025-01-01', [
            ['item_id' => $item->id, 'unit_id' => $g->id, 'quantity' => 1],
        ]);

        $this->expectException(ValidationException::class);

        $service->createVersion($menuItem, '2025-01-02', [
            ['item_id' => $item->id, 'unit_id' => $g->id, 'quantity' => 1],
        ]);
    }

    public function test_converts_ingredient_to_base_unit(): void
    {
        $menuItem = MenuItem::create([
            'organization_id' => 1,
            'name' => 'Espresso',
            'is_active' => true,
        ]);

        $kg = Unit::where('slug', 'kg')->firstOrFail();
        $g = Unit::where('slug', 'g')->firstOrFail();

        $item = Item::factory()->create([
            'organization_id' => 1,
            'base_unit_id' => $g->id,
        ]);

        $service = app(RecipeService::class);

        $version = $service->createVersion($menuItem, '2025-01-05', [
            ['item_id' => $item->id, 'unit_id' => $kg->id, 'quantity' => 0.25], // 250 g
        ]);

        $ingredient = $version->ingredients()->first();

        $this->assertEquals(250.0, (float) $ingredient->quantity_in_base);
    }
}
