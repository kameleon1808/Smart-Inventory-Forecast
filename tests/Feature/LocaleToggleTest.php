<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_locale_switch_persists_in_session(): void
    {
        $user = User::first();

        $response = $this->actingAs($user)->post('/locale', ['locale' => 'sr']);

        $response->assertRedirect();
        $this->assertEquals('sr', session('app_locale'));
    }

    public function test_dashboard_renders_serbian_when_locale_is_sr(): void
    {
        $user = User::first();

        $this->actingAs($user)
            ->withSession(['app_locale' => 'sr'])
            ->get('/dashboard')
            ->assertSee('Kontrolna tabla');

        $this->actingAs($user)
            ->withSession(['app_locale' => 'en'])
            ->get('/dashboard')
            ->assertSee('Dashboard');
    }
}
