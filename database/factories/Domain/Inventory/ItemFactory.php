<?php

namespace Database\Factories\Domain\Inventory;

use App\Domain\Inventory\Item;
use App\Domain\Inventory\ItemCategory;
use App\Domain\Inventory\Unit;
use App\Domain\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        $organization = Organization::first() ?? Organization::factory()->create();
        $category = ItemCategory::first() ?? ItemCategory::factory()->create();
        $unit = Unit::first() ?? Unit::factory()->create();

        return [
            'organization_id' => $organization->id,
            'category_id' => $category->id,
            'base_unit_id' => $unit->id,
            'sku' => strtoupper(fake()->unique()->bothify('SKU-###')),
            'name' => fake()->unique()->words(3, true),
            'pack_size' => fake()->randomFloat(2, 1, 10),
            'min_stock' => fake()->randomFloat(2, 0, 50),
            'safety_stock' => fake()->randomFloat(2, 0, 30),
            'lead_time_days' => fake()->numberBetween(0, 14),
            'shelf_life_days' => fake()->optional()->numberBetween(7, 180),
            'is_active' => true,
        ];
    }
}
