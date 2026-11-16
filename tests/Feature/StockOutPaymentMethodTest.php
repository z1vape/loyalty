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
use App\Models\Permission;

class StockOutPaymentMethodTest extends TestCase
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

    public function test_can_log_stock_out_with_payment_method(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-OUT-01',
            'name' => 'Produk Stock Out Test',
            'purchase_price' => 20000,
            'selling_price' => 35000,
            'stock' => 50,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => 5,
                'movement_date' => '2026-10-06',
                'user_id' => $user->id,
                'selling_price' => 35000,
                'discount' => 5000,
                'total_price' => 170000,
                'payment_method' => 'qris',
                'notes' => 'Penjualan manual via QRIS',
            ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 5,
            'discount' => 5000,
            'payment_method' => 'qris',
        ]);

        $product->refresh();
        $this->assertEquals(45, $product->stock); // 50 - 5
    }

    public function test_stock_in_ignores_discount_and_accepts_payment_method(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-IN-01',
            'name' => 'Produk Stock In Test',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 10,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => 10,
                'movement_date' => '2026-10-06',
                'user_id' => $user->id,
                'purchase_price' => 12000,
                'discount' => 1000, // Should be forced to 0
                'payment_method' => 'transfer',
            ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 10,
            'discount' => 0,
            'payment_method' => 'transfer',
        ]);
    }

    public function test_date_show_view_renders_payment_method_for_stock_out(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-OUT-02',
            'name' => 'Kopi Latte',
            'purchase_price' => 15000,
            'selling_price' => 28000,
            'stock' => 30,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => '2026-10-06',
            'type' => 'out',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-88888',
            'movement_date' => '2026-10-06',
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 15000,
            'selling_price' => 28000,
            'discount' => 0,
            'total_price' => 56000,
            'payment_method' => 'transfer',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', $movementDate->id));

        $response->assertStatus(200);
        $response->assertSee('Metode Bayar');
        $response->assertSee('TRANSFER');
    }

    public function test_date_show_displays_grand_total_across_all_pages_and_payment_breakdown(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-OUT-PAGINATE',
            'name' => 'Kopi Robusta',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 100,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => '2026-10-07',
            'type' => 'out',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        // Create 25 movements (more than 1 page of 20)
        // 15 Transfer @ Rp 20.000 = Rp 300.000
        for ($i = 1; $i <= 15; $i++) {
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'reference_number' => 'TXN-TRF-' . $i,
                'movement_date' => '2026-10-07',
                'type' => 'out',
                'quantity' => 1,
                'purchase_price' => 10000,
                'selling_price' => 20000,
                'discount' => 0,
                'total_price' => 20000,
                'payment_method' => 'transfer',
                'store_id' => $store->id,
            ]);
        }

        // 10 Cash @ Rp 20.000 = Rp 200.000
        for ($i = 1; $i <= 10; $i++) {
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'reference_number' => 'TXN-CSH-' . $i,
                'movement_date' => '2026-10-07',
                'type' => 'out',
                'quantity' => 1,
                'purchase_price' => 10000,
                'selling_price' => 20000,
                'discount' => 0,
                'total_price' => 20000,
                'payment_method' => 'cash',
                'store_id' => $store->id,
            ]);
        }

        // Total 25 items, Total amount = Rp 500.000 (Transfer: 300.000, Cash: 200.000)
        // Page 1
        $resPage1 = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', ['id' => $movementDate->id, 'page' => 1]));

        $resPage1->assertStatus(200);
        $resPage1->assertSee('500.000'); // Grand total on card
        $resPage1->assertSee('300.000'); // Transfer breakdown
        $resPage1->assertSee('200.000'); // Cash breakdown
        $resPage1->assertSee('Semua Data');

        // Page 2 - Grand total MUST still be Rp 500.000!
        $resPage2 = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', ['id' => $movementDate->id, 'page' => 2]));

        $resPage2->assertStatus(200);
        $resPage2->assertSee('500.000'); // Grand total on card remains consistent
        $resPage2->assertSee('300.000');
        $resPage2->assertSee('200.000');
    }

    public function test_movements_index_displays_summary_and_payment_breakdown(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movements.index', ['type' => 'out']));

        $response->assertStatus(200);
        $response->assertSee('Total Nilai Transaksi');
        $response->assertSee('Akumulasi Metode Pembayaran Stock Out');
    }
}
