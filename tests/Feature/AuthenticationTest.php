<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_inventory(): void
    {
        $this->get('/inventory')->assertRedirect('/login');
    }

    public function test_guest_is_redirected_from_portal(): void
    {
        $this->get('/portal')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_portal(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Belitung']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)->get('/portal');

        $response->assertOk();
        $response->assertSee('Human Resource');
        $response->assertSee('Security');
        $response->assertSee('Surveillance');
        $response->assertSee('Store Management');
        $response->assertSee('Buka Dashboard Toko');
    }

    public function test_user_login_redirects_to_portal(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Belitung']);
        $user = User::factory()->create([
            'email' => 'testuser@z1vape.com',
            'password' => bcrypt('secret123'),
        ]);
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $response = $this->post('/login', [
            'email' => 'testuser@z1vape.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/portal');
        $this->assertAuthenticatedAs($user);
    }
}
