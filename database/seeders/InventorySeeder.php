<?php

namespace Database\Seeders;

use App\Domain\Inventory\ItemCategory;
use App\Domain\Inventory\Unit;
use App\Domain\Inventory\UnitConversion;
use App\Domain\Organization;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Coffee',
            'Alcohol',
            'Soft Drinks',
            'Food',
            'Supplies',
        ];

        foreach ($categories as $name) {
            ItemCategory::firstOrCreate(['name' => $name]);
        }

        $units = collect([
            ['name' => 'Gram', 'slug' => 'g', 'symbol' => 'g'],
            ['name' => 'Kilogram', 'slug' => 'kg', 'symbol' => 'kg'],
            ['name' => 'Milliliter', 'slug' => 'ml', 'symbol' => 'ml'],
            ['name' => 'Liter', 'slug' => 'l', 'symbol' => 'l'],
            ['name' => 'Pieces', 'slug' => 'pcs', 'symbol' => 'pcs'],
        ])->mapWithKeys(fn ($unit) => [$unit['slug'] => Unit::firstOrCreate($unit)]);

        $this->seedConversion($units['kg'], $units['g'], 1000);
        $this->seedConversion($units['g'], $units['kg'], 0.001);
        $this->seedConversion($units['l'], $units['ml'], 1000);
        $this->seedConversion($units['ml'], $units['l'], 0.001);

        // Ensure an organization exists for inventory scoping
        Organization::firstOrCreate(['name' => 'Smart Inventory Org']);
    }

    private function seedConversion(Unit $from, Unit $to, float $factor): void
    {
        UnitConversion::firstOrCreate([
            'from_unit_id' => $from->id,
            'to_unit_id' => $to->id,
        ], [
            'factor' => $factor,
        ]);
    }
}
