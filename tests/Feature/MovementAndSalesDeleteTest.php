<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\MovementDate;
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\Permission;

class MovementAndSalesDeleteTest extends TestCase
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

    private function createStaffWithStoreAndPermissions(array $permissionNames = [])
    {
        $role = Role::create(['name' => 'Staff']);
        $store = Store::create(['name' => 'Store Staff']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        foreach ($permissionNames as $permName) {
            $perm = Permission::firstOrCreate(
                ['name' => $permName],
                ['category' => 'General', 'group_name' => 'GENERAL', 'description' => $permName]
            );
            $role->permissions()->attach($perm->id);
        }

        return [$user, $store];
    }

    public function test_views_render_checkboxes_and_delete_buttons(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-TEST-01',
            'name' => 'Produk Test',
            'purchase_price' => 10000,
            'selling_price' => 15000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        MovementDate::create([
            'date' => '2026-09-30',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'SALE-TEST-01',
            'sale_date' => '2026-09-30',
            'total_amount' => 15000,
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);
        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'selling_price' => 15000,
            'subtotal' => 15000,
        ]);

        // 1. Stock In page
        $resIn = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->get(route('movements.index', ['type' => 'in']));
        $resIn->assertStatus(200);
        $resIn->assertSee('id="bulkActionBar"', false);
        $resIn->assertSee('id="selectAllCheckbox"', false);

        // 2. Stock Out page
        $resOut = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->get(route('movements.index', ['type' => 'out']));
        $resOut->assertStatus(200);
        $resOut->assertSee('id="bulkActionBar"', false);

        // 3. Sales History page
        $resSales = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index'));
        $resSales->assertStatus(200);
        $resSales->assertSee('id="salesBulkActionBar"', false);
        $resSales->assertSee('id="selectAllSales"', false);
    }

    public function test_delete_date_movement_reverts_stock_in(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-IN-01',
            'name' => 'Kopi Arabika',
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'stock' => 15, // Stock has 15 (was 5 + 10 incoming)
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => '2026-09-30',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-12345',
            'movement_date' => '2026-09-30',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 20000,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->delete(route('movement-dates.destroy', $movementDate->id));

        $response->assertRedirect(route('movements.index'));
        $this->assertDatabaseMissing('movement_dates', ['id' => $movementDate->id]);
        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);

        $product->refresh();
        // Reverted: 15 - 10 = 5
        $this->assertEquals(5, $product->stock);
    }

    public function test_delete_date_movement_reverts_stock_out(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-OUT-01',
            'name' => 'Susu Segar',
            'purchase_price' => 10000,
            'selling_price' => 18000,
            'stock' => 5, // Was 12 - 7 outgoing = 5
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => '2026-09-30',
            'type' => 'out',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $movement = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-99999',
            'movement_date' => '2026-09-30',
            'type' => 'out',
            'quantity' => 7,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->delete(route('movement-dates.destroy', $movementDate->id));

        $response->assertRedirect(route('movements.index'));
        $this->assertDatabaseMissing('movement_dates', ['id' => $movementDate->id]);
        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);

        $product->refresh();
        // Reverted: 5 + 7 = 12
        $this->assertEquals(12, $product->stock);
    }

    public function test_bulk_delete_individual_movement_logs(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-LOG-01',
            'name' => 'Sirup Vanila',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 20,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $m1 = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-001',
            'movement_date' => '2026-09-30',
            'type' => 'in',
            'quantity' => 5,
            'store_id' => $store->id,
        ]);

        $m2 = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-002',
            'movement_date' => '2026-09-30',
            'type' => 'in',
            'quantity' => 5,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->post(route('movements.bulk-destroy'), [
                'ids' => [$m1->id, $m2->id],
            ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('stock_movements', ['id' => $m1->id]);
        $this->assertDatabaseMissing('stock_movements', ['id' => $m2->id]);

        $product->refresh();
        // 20 - 5 - 5 = 10
        $this->assertEquals(10, $product->stock);
    }

    public function test_delete_sale_reverts_stock_and_removes_movements(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SALE-01',
            'name' => 'Croissant',
            'purchase_price' => 10000,
            'selling_price' => 22000,
            'stock' => 8, // Stock after selling 4 = 8
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'SALE-11111',
            'sale_date' => '2026-09-30',
            'total_amount' => 88000,
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'selling_price' => 22000,
            'discount' => 0,
            'subtotal' => 88000,
        ]);

        $mov = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'SALE-11111',
            'movement_date' => '2026-09-30',
            'type' => 'out',
            'quantity' => 4,
            'purchase_price' => 10000,
            'selling_price' => 22000,
            'total_price' => 88000,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->delete(route('sales.destroy', $sale->id));

        $response->assertRedirect(route('sales.index'));
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
        $this->assertDatabaseMissing('sale_items', ['sale_id' => $sale->id]);
        $this->assertDatabaseMissing('stock_movements', ['reference_number' => 'SALE-11111']);

        $product->refresh();
        // Reverted: 8 + 4 = 12
        $this->assertEquals(12, $product->stock);
    }

    public function test_bulk_delete_sales(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SALE-02',
            'name' => 'Latte',
            'purchase_price' => 12000,
            'selling_price' => 25000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $s1 = Sale::create([
            'reference_number' => 'SALE-A1',
            'sale_date' => '2026-09-30',
            'total_amount' => 50000,
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);
        $s1->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 25000,
            'subtotal' => 50000,
        ]);

        $s2 = Sale::create([
            'reference_number' => 'SALE-A2',
            'sale_date' => '2026-09-30',
            'total_amount' => 75000,
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);
        $s2->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'selling_price' => 25000,
            'subtotal' => 75000,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->post(route('sales.bulk-destroy'), [
                'ids' => [$s1->id, $s2->id],
            ]);

        $response->assertRedirect(route('sales.index'));
        $this->assertDatabaseMissing('sales', ['id' => $s1->id]);
        $this->assertDatabaseMissing('sales', ['id' => $s2->id]);

        $product->refresh();
        // Reverted: 10 + 2 + 3 = 15
        $this->assertEquals(15, $product->stock);
    }

    public function test_staff_without_permission_cannot_delete_stock_in_date(): void
    {
        [$user, $store] = $this->createStaffWithStoreAndPermissions(['Stock In List']);

        $movementDate = MovementDate::create([
            'date' => '2026-09-30',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->delete(route('movement-dates.destroy', $movementDate->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('movement_dates', ['id' => $movementDate->id]);
    }

    public function test_staff_with_stock_in_destroy_permission_can_delete_stock_in_date(): void
    {
        [$user, $store] = $this->createStaffWithStoreAndPermissions(['Stock In Destroy']);

        $movementDate = MovementDate::create([
            'date' => '2026-09-30',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->delete(route('movement-dates.destroy', $movementDate->id));

        $response->assertRedirect(route('movements.index'));
        $this->assertDatabaseMissing('movement_dates', ['id' => $movementDate->id]);
    }

    public function test_staff_without_permission_cannot_delete_sales(): void
    {
        [$user, $store] = $this->createStaffWithStoreAndPermissions(['Sales List', 'Sales Show']);

        $sale = Sale::create([
            'reference_number' => 'SALE-NO-PERM',
            'sale_date' => '2026-09-30',
            'total_amount' => 50000,
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->delete(route('sales.destroy', $sale->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('sales', ['id' => $sale->id]);
    }

    public function test_staff_with_sales_destroy_permission_can_delete_sales(): void
    {
        [$user, $store] = $this->createStaffWithStoreAndPermissions(['Sales Destroy']);

        $sale = Sale::create([
            'reference_number' => 'SALE-WITH-PERM',
            'sale_date' => '2026-09-30',
            'total_amount' => 50000,
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->delete(route('sales.destroy', $sale->id));

        $response->assertRedirect(route('sales.index'));
        $this->assertDatabaseMissing('sales', ['id' => $sale->id]);
    }

    public function test_unauthorized_user_views_do_not_render_delete_actions(): void
    {
        [$user, $store] = $this->createStaffWithStoreAndPermissions(['Movement List', 'Sales List']);

        MovementDate::create([
            'date' => '2026-09-30',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        Sale::create([
            'reference_number' => 'SALE-VIEW-01',
            'sale_date' => '2026-09-30',
            'total_amount' => 50000,
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        // Stock In index should not have delete action or bulk bar
        $resIn = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->get(route('movements.index', ['type' => 'in']));
        $resIn->assertStatus(200);
        $resIn->assertDontSee('id="bulkActionBar"', false);
        $resIn->assertDontSee('Hapus Semua');
        $resIn->assertDontSee('class="date-checkbox', false);

        // Sales index should not have delete action or bulk bar
        $resSales = $this->actingAs($user)->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index'));
        $resSales->assertStatus(200);
        $resSales->assertDontSee('id="salesBulkActionBar"', false);
        $resSales->assertDontSee('Hapus Semua');
        $resSales->assertDontSee('class="sale-checkbox', false);
    }
}
