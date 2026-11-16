<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic test example.
     */
    public function test_the_application_redirects_unauthenticated_users(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        // Create user and assign Super Admin role
        $role = \App\Models\Role::create(['name' => 'Super Admin']);
        $store = \App\Models\Store::create(['name' => 'Test Store']);
        $user = \App\Models\User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])->get('/');

        $response->assertStatus(200);
    }

    public function test_staff_user_with_dashboard_permission_can_access_dashboard(): void
    {
        $role = \App\Models\Role::create(['name' => 'Staff']);
        $permission = \App\Models\Permission::create(['name' => 'Dashboard List', 'category' => 'Dashboard', 'group_name' => 'DASHBOARD']);
        $role->permissions()->attach($permission->id);

        $store = \App\Models\Store::create(['name' => 'Store Staff']);
        $user = \App\Models\User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_staff_user_without_dashboard_permission_redirects_to_allowed_menu(): void
    {
        $role = \App\Models\Role::create(['name' => 'Staff Only Inventory']);
        $permission = \App\Models\Permission::create(['name' => 'Inventory List', 'category' => 'Inventory', 'group_name' => 'INVENTORY']);
        $role->permissions()->attach($permission->id);

        $store = \App\Models\Store::create(['name' => 'Store Staff']);
        $user = \App\Models\User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])->get('/dashboard');

        $response->assertRedirect(route('inventory.index'));
    }

    public function test_super_admin_can_create_and_update_store_with_multiple_staff(): void
    {
        $role = \App\Models\Role::create(['name' => 'Super Admin']);
        $store = \App\Models\Store::create(['name' => 'HQ Store']);
        $admin = \App\Models\User::factory()->create();
        $admin->roles()->attach($role->id);
        $admin->stores()->attach($store->id);

        $staff1 = \App\Models\User::factory()->create(['name' => 'Staff 1']);
        $staff2 = \App\Models\User::factory()->create(['name' => 'Staff 2']);

        // Test create page loads 200
        $response = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->get(route('stores.create'));
        $response->assertStatus(200);

        // Test storing new store with multiple staff
        $storeResponse = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->post(route('stores.store'), [
            'name' => 'Cabang Surabaya',
            'phone' => '031-123456',
            'address' => 'Jl. Pemuda No. 1',
            'users' => [$staff1->id, $staff2->id],
        ]);

        $storeResponse->assertRedirect(route('stores.index'));
        $this->assertDatabaseHas('stores', ['name' => 'Cabang Surabaya']);
        $newStore = \App\Models\Store::where('name', 'Cabang Surabaya')->first();
        $this->assertCount(2, $newStore->users);

        // Test update store removing one staff
        $updateResponse = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->put(route('stores.update', $newStore), [
            'name' => 'Cabang Surabaya Updated',
            'phone' => '031-123456',
            'address' => 'Jl. Pemuda No. 2',
            'users' => [$staff1->id],
        ]);

        $updateResponse->assertRedirect(route('stores.index'));
        $newStore->refresh();
        $this->assertCount(1, $newStore->users);
        $this->assertTrue($newStore->users->contains($staff1->id));
        $this->assertFalse($newStore->users->contains($staff2->id));
    }

    public function test_super_admin_can_access_roles_index_and_update_permissions(): void
    {
        $role = \App\Models\Role::create(['name' => 'Super Admin']);
        $store = \App\Models\Store::create(['name' => 'HQ Store']);
        $admin = \App\Models\User::factory()->create();
        $admin->roles()->attach($role->id);
        $admin->stores()->attach($store->id);

        $customRole = \App\Models\Role::create(['name' => 'Finance Staff', 'description' => 'Handles finances']);
        $perm1 = \App\Models\Permission::create(['name' => 'Sales List', 'category' => 'Sales', 'group_name' => 'SALES']);
        $perm2 = \App\Models\Permission::create(['name' => 'Expense List', 'category' => 'Pengeluaran Toko', 'group_name' => 'EXPENSE']);

        // View roles index page loads 200
        $response = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->get(route('roles.index'));
        $response->assertStatus(200);

        // Update permissions for customRole
        $updatePermResponse = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->put(route('roles.permissions.update', $customRole), [
            'permissions' => [$perm1->id, $perm2->id],
        ]);

        $updatePermResponse->assertRedirect(route('roles.index'));
        $customRole->refresh();
        $this->assertCount(2, $customRole->permissions);
        $this->assertTrue($customRole->permissions->contains($perm1->id));
        $this->assertTrue($customRole->permissions->contains($perm2->id));
    }

    public function test_super_admin_can_manage_dropdown_settings(): void
    {
        $role = \App\Models\Role::create(['name' => 'Super Admin']);
        $store = \App\Models\Store::create(['name' => 'HQ Store']);
        $admin = \App\Models\User::factory()->create();
        $admin->roles()->attach($role->id);
        $admin->stores()->attach($store->id);

        // 1. Can view settings index
        $indexResponse = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->get(route('settings.index', ['type' => 'category']));
        $indexResponse->assertStatus(200);

        // 2. Can create new setting
        $storeResponse = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->post(route('settings.store'), [
            'type' => 'category',
            'value' => 'Liquid Saltnic',
        ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('settings', [
            'type' => 'category',
            'value' => 'Liquid Saltnic',
            'store_id' => $store->id,
        ]);

        $setting = \App\Models\Setting::where('type', 'category')->where('value', 'Liquid Saltnic')->first();

        // 3. Can update setting
        $updateResponse = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->put(route('settings.update', $setting), [
            'value' => 'Liquid Saltnic Premium',
        ]);
        $updateResponse->assertRedirect();
        $setting->refresh();
        $this->assertEquals('Liquid Saltnic Premium', $setting->value);

        // 4. Can delete setting
        $deleteResponse = $this->actingAs($admin)->withSession(['active_store_id' => $store->id])->delete(route('settings.destroy', $setting));
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('settings', ['id' => $setting->id]);
    }

    public function test_supplier_master_data_and_stock_in_auto_creation(): void
    {
        $role = \App\Models\Role::create(['name' => 'Super Admin']);
        $store = \App\Models\Store::create(['name' => 'HQ Store 2']);
        $admin = \App\Models\User::factory()->create();
        $admin->roles()->attach($role->id);
        $admin->stores()->attach($store->id);

        // 1. Can view supplier settings
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('settings.index', ['type' => 'supplier']));
        $response->assertStatus(200);

        // 2. Can create supplier via settings
        $storeResponse = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('settings.store'), [
                'type' => 'supplier',
                'value' => 'PT Vape Master Supplier',
            ]);
        $storeResponse->assertRedirect();
        $this->assertDatabaseHas('settings', [
            'type' => 'supplier',
            'value' => 'PT Vape Master Supplier',
        ]);

        // 3. Creating Stock In session with new supplier auto-registers it into settings
        $dateResponse = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movement-dates.store'), [
                'date' => '2026-10-22',
                'type' => 'in',
                'supplier_name' => 'CV Sumber Liquid Baru',
                'payment_method' => 'cash',
            ]);
        $dateResponse->assertRedirect();
        $this->assertDatabaseHas('movement_dates', [
            'date' => '2026-10-22',
            'supplier_name' => 'CV Sumber Liquid Baru',
        ]);
        $this->assertDatabaseHas('settings', [
            'type' => 'supplier',
            'value' => 'CV Sumber Liquid Baru',
        ]);

        // 4. Creating Stock Movement with another new supplier auto-registers it into settings and movement log
        $product = \App\Models\Product::create([
            'sku' => 'TEST-SUP-01',
            'name' => 'Test Product Supplier',
            'stock' => 10,
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'store_id' => $store->id,
        ]);

        $movResponse = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'type' => 'in',
                'movement_date' => '2026-10-22',
                'product_id' => $product->id,
                'quantity' => 5,
                'purchase_price' => 50000,
                'supplier_name' => 'Distributor Utama Indonesia',
                'payment_method' => 'cash',
            ]);
        $movResponse->assertRedirect();
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'supplier_name' => 'Distributor Utama Indonesia',
        ]);
        $this->assertDatabaseHas('settings', [
            'type' => 'supplier',
            'value' => 'Distributor Utama Indonesia',
        ]);
    }
}


