<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\Demo\DemoSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RbacSeeder::class,
            InventorySeeder::class,
            TestUserSeeder::class,
        ]);

        if (env('DEMO_SEED', false)) {
            $this->call(DemoSeeder::class);
        }
    }
}
