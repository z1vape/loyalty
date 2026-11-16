<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PosLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_login_successful_with_valid_credentials(): void
    {
        $store = Store::create([
            'name' => 'Z1 Vape Store',
            'address' => 'Jl. Riau No. 10',
            'phone' => '08123456789'
        ]);

        $user = User::create([
            'name' => 'Jovanus Cashier',
            'email' => 'jovanus@z1.com',
            'password' => Hash::make('secret123'),
            'position' => 'Cashier'
        ]);

        $user->stores()->attach($store->id);

        $response = $this->postJson('/api/pos/login', [
            'store_id' => $store->id,
            'user_id' => $user->id,
            'password' => 'secret123'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => 'Jovanus Cashier'
                    ],
                    'store' => [
                        'id' => $store->id,
                        'name' => 'Z1 Vape Store'
                    ]
                ]
            ]);
    }

    public function test_pos_login_fails_with_invalid_password(): void
    {
        $store = Store::create([
            'name' => 'Z1 Vape Store',
            'address' => 'Jl. Riau No. 10',
            'phone' => '08123456789'
        ]);

        $user = User::create([
            'name' => 'Jovanus Cashier',
            'email' => 'jovanus@z1.com',
            'password' => Hash::make('secret123'),
            'position' => 'Cashier'
        ]);

        $user->stores()->attach($store->id);

        $response = $this->postJson('/api/pos/login', [
            'store_id' => $store->id,
            'user_id' => $user->id,
            'password' => 'wrongpass'
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'status' => 'error',
                'message' => 'Password yang Anda masukkan salah.'
            ]);
    }

    public function test_pos_login_fails_if_user_not_assigned_to_store(): void
    {
        $storeA = Store::create(['name' => 'Store A', 'address' => 'Addr A', 'phone' => '111']);
        $storeB = Store::create(['name' => 'Store B', 'address' => 'Addr B', 'phone' => '222']);

        $user = User::create([
            'name' => 'Staff A Only',
            'email' => 'staff@a.com',
            'password' => Hash::make('secret123'),
            'position' => 'Staff'
        ]);

        $user->stores()->attach($storeA->id);

        // Try to login to Store B
        $response = $this->postJson('/api/pos/login', [
            'store_id' => $storeB->id,
            'user_id' => $user->id,
            'password' => 'secret123'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error'
            ]);
    }
}
