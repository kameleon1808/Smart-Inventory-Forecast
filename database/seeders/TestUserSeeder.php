<?php

namespace Database\Seeders;

use App\Domain\Location;
use App\Domain\Organization;
use App\Domain\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Seed a predictable demo user for local development.
     */
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Attach the demo user to the default organization/location as admin so they can use the app immediately.
        $organization = Organization::firstOrCreate(['name' => 'Smart Inventory Org']);
        $location = Location::firstOrCreate([
            'name' => 'Main Hall',
            'organization_id' => $organization->id,
        ]);
        $role = Role::firstOrCreate(['slug' => Role::ADMIN], ['name' => 'Admin']);

        $user->organizations()->syncWithoutDetaching([$organization->id]);
        $user->locations()->syncWithoutDetaching([$location->id => ['role_id' => $role->id]]);
    }
}
