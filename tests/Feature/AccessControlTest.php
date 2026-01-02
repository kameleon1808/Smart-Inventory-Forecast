<?php

namespace Tests\Feature;

use App\Domain\Location;
use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_access_location_data(): void
    {
        $this->seed(RbacSeeder::class);

        $manager = User::where('email', 'manager@example.com')->firstOrFail();
        $location = Location::where('name', 'Main Hall')->firstOrFail();

        $response = $this->actingAs($manager)
            ->withSession(['active_location_id' => $location->id])
            ->get('/location-data');

        $response->assertOk()->assertSee('Location Data');
    }

    public function test_waiter_cannot_access_location_data(): void
    {
        $this->seed(RbacSeeder::class);

        $waiter = User::where('email', 'waiter@example.com')->firstOrFail();
        $location = Location::where('name', 'Garden')->firstOrFail();

        $response = $this->actingAs($waiter)
            ->withSession(['active_location_id' => $location->id])
            ->get('/location-data');

        $response->assertForbidden();
    }
}
