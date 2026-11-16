<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\Permission;

class BulkDeleteProductTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_inventory_index_renders_checkbox_elements(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        Product::create([
            'sku' => 'SKU-001',
            'name' => 'Produk A',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])->get(route('inventory.index'));

        $response->assertStatus(200);
        $response->assertSee('id="selectAllCheckbox"', false);
        $response->assertSee('product-checkbox', false);
        $response->assertSee('id="bulkActionBar"', false);
        $response->assertSee('id="bulkDeleteModal"', false);
    }

    public function test_user_without_permission_cannot_bulk_delete(): void
    {
        $role = Role::create(['name' => 'Staff']);
        $store = Store::create(['name' => 'Store Staff']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $product = Product::create([
            'sku' => 'SKU-001',
            'name' => 'Produk A',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->post(route('inventory.bulk-destroy'), [
                'ids' => [$product->id],
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_user_can_bulk_delete_selected_products(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $p1 = Product::create([
            'sku' => 'SKU-001',
            'name' => 'Produk 1',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $p2 = Product::create([
            'sku' => 'SKU-002',
            'name' => 'Produk 2',
            'purchase_price' => 20000,
            'selling_price' => 25000,
            'stock' => 5,
            'min_stock' => 1,
            'store_id' => $store->id,
        ]);

        $p3 = Product::create([
            'sku' => 'SKU-003',
            'name' => 'Produk 3 (Keep)',
            'purchase_price' => 30000,
            'selling_price' => 35000,
            'stock' => 20,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->post(route('inventory.bulk-destroy'), [
                'ids' => [$p1->id, $p2->id],
            ]);

        $response->assertRedirect(route('inventory.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $p1->id]);
        $this->assertDatabaseMissing('products', ['id' => $p2->id]);
        $this->assertDatabaseHas('products', ['id' => $p3->id]);
    }

    public function test_user_can_delete_all_products_in_active_store(): void
    {
        [$user, $store1] = $this->createAdminWithStore();
        $store2 = Store::create(['name' => 'Store 2']);

        // Products in store 1
        $p1 = Product::create([
            'sku' => 'SKU-S1-01',
            'name' => 'Produk Store 1 A',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store1->id,
        ]);
        $p2 = Product::create([
            'sku' => 'SKU-S1-02',
            'name' => 'Produk Store 1 B',
            'purchase_price' => 20000,
            'selling_price' => 25000,
            'stock' => 5,
            'min_stock' => 1,
            'store_id' => $store1->id,
        ]);

        // Product in store 2 (should remain untouched)
        $pStore2 = Product::create([
            'sku' => 'SKU-S2-01',
            'name' => 'Produk Store 2',
            'purchase_price' => 50000,
            'selling_price' => 60000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store2->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store1->id])
            ->post(route('inventory.bulk-destroy'), [
                'delete_all' => 1,
            ]);

        $response->assertRedirect(route('inventory.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('products', ['id' => $p1->id]);
        $this->assertDatabaseMissing('products', ['id' => $p2->id]);
        $this->assertDatabaseHas('products', ['id' => $pStore2->id]);
    }
}
