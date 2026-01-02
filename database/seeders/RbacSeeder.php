<?php

namespace Database\Seeders;

use App\Domain\Location;
use App\Domain\Organization;
use App\Domain\Role;
use App\Domain\Warehouse;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            Role::ADMIN,
            Role::MANAGER,
            Role::WAITER,
            Role::VIEWER,
        ])->mapWithKeys(fn (string $slug) => [$slug => Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => ucfirst($slug)]
        )]);

        $organization = Organization::firstOrCreate(['name' => 'Smart Inventory Org']);

        $mainHall = Location::firstOrCreate(
            ['name' => 'Main Hall', 'organization_id' => $organization->id]
        );
        $garden = Location::firstOrCreate(
            ['name' => 'Garden', 'organization_id' => $organization->id]
        );

        Warehouse::firstOrCreate([
            'name' => 'Central Warehouse',
            'organization_id' => $organization->id,
            'location_id' => $mainHall->id,
        ]);

        Warehouse::firstOrCreate([
            'name' => 'Garden Storage',
            'organization_id' => $organization->id,
            'location_id' => $garden->id,
        ]);

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $manager = User::firstOrCreate(
            ['email' => 'manager@example.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $waiter = User::firstOrCreate(
            ['email' => 'waiter@example.com'],
            [
                'name' => 'Waiter User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $users = collect([$admin, $manager, $waiter]);
        $users->each(fn (User $user) => $user->organizations()->syncWithoutDetaching([$organization->id]));

        $admin->locations()->syncWithoutDetaching([$mainHall->id => ['role_id' => $roles[Role::ADMIN]->id]]);
        $manager->locations()->syncWithoutDetaching([$mainHall->id => ['role_id' => $roles[Role::MANAGER]->id]]);
        $waiter->locations()->syncWithoutDetaching([$garden->id => ['role_id' => $roles[Role::WAITER]->id]]);
    }
}
