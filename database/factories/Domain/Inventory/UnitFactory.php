<?php

namespace Database\Factories\Domain\Inventory;

use App\Domain\Inventory\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    protected $model = Unit::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst(fake()->unique()->word()),
            'slug' => fake()->unique()->lexify('u???'),
            'symbol' => fake()->unique()->lexify('?'),
        ];
    }
}
