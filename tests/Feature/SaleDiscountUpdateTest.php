<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use Carbon\Carbon;

class SaleDiscountUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create(['name' => 'Admin User']);
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_authorized_user_can_update_discount_and_syncs_movements_and_sale_totals(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $product1 = Product::create([
            'sku' => 'PRD-01',
            'name' => 'Kopi Robusta',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 50,
            'store_id' => $store->id,
        ]);

        $product2 = Product::create([
            'sku' => 'PRD-02',
            'name' => 'Kopi Arabika',
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'stock' => 50,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0001',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'Pelanggan Setia',
            'total_amount' => 80000,
            'notes' => '[POS - Kasir] Bayar: CASH',
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $item1 = $sale->items()->create([
            'product_id' => $product1->id,
            'quantity' => 2,
            'selling_price' => 25000,
            'discount' => 0,
            'subtotal' => 50000,
        ]);

        $item2 = $sale->items()->create([
            'product_id' => $product2->id,
            'quantity' => 1,
            'selling_price' => 30000,
            'discount' => 0,
            'subtotal' => 30000,
        ]);

        $movement1 = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product1->id,
            'user_id' => $admin->id,
            'reference_number' => $sale->reference_number,
            'movement_date' => Carbon::today()->toDateString(),
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'discount' => 0,
            'total_price' => 50000,
            'payment_method' => 'cash',
            'notes' => "POS Penjualan - {$sale->reference_number}",
        ]);

        $movement2 = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product2->id,
            'user_id' => $admin->id,
            'reference_number' => $sale->reference_number,
            'movement_date' => Carbon::today()->toDateString(),
            'type' => 'out',
            'quantity' => 1,
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'discount' => 0,
            'total_price' => 30000,
            'payment_method' => 'cash',
            'notes' => "POS Penjualan - {$sale->reference_number}",
        ]);

        // Put new discount on both items:
        // Item 1: discount 5,000 (subtotal 45,000)
        // Item 2: discount 10,000 (subtotal 20,000)
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-discount', $sale->id), [
                'items' => [
                    [
                        'id' => $item1->id,
                        'discount' => 5000,
                    ],
                    [
                        'id' => $item2->id,
                        'discount' => 10000,
                    ],
                ]
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'total_amount' => 65000,
                'total_discount' => 15000,
            ]);

        // Verify database state for Sale
        $sale->refresh();
        $this->assertEquals(65000, $sale->total_amount);

        // Verify SaleItem 1 & 2
        $item1->refresh();
        $this->assertEquals(5000, $item1->discount);
        $this->assertEquals(45000, $item1->subtotal);

        $item2->refresh();
        $this->assertEquals(10000, $item2->discount);
        $this->assertEquals(20000, $item2->subtotal);

        // Verify StockMovement sync
        $movement1->refresh();
        $this->assertEquals(5000, $movement1->discount);
        $this->assertEquals(45000, $movement1->total_price);

        $movement2->refresh();
        $this->assertEquals(10000, $movement2->discount);
        $this->assertEquals(20000, $movement2->total_price);
    }

    public function test_sale_show_endpoint_returns_json_with_items_for_discount_modal(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-01',
            'name' => 'Kopi Robusta',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 50,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0002',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'Pelanggan Umum',
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 25000,
            'discount' => 5000,
            'subtotal' => 45000,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->getJson(route('sales.show', $sale->id));

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'sale' => [
                    'id',
                    'reference_number',
                    'total_gross',
                    'total_discount',
                    'total_amount',
                    'items' => [
                        '*' => [
                            'id',
                            'product_name',
                            'quantity',
                            'selling_price',
                            'discount',
                            'subtotal',
                        ]
                    ]
                ]
            ]);
    }

    public function test_cannot_set_negative_discount_and_caps_at_gross_total(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-01',
            'name' => 'Kopi Robusta',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 50,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0003',
            'sale_date' => Carbon::today()->toDateString(),
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $item = $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 25000,
            'discount' => 0,
            'subtotal' => 50000,
        ]);

        // 1. Negative discount should fail validation
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-discount', $sale->id), [
                'items' => [
                    [
                        'id' => $item->id,
                        'discount' => -5000,
                    ]
                ]
            ]);

        $response->assertStatus(422);

        // 2. Discount exceeding gross (gross = 50,000, discount entered = 999,999) should cap at 50,000
        $responseOk = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-discount', $sale->id), [
                'items' => [
                    [
                        'id' => $item->id,
                        'discount' => 999999,
                    ]
                ]
            ]);

        $responseOk->assertOk();
        $item->refresh();
        $this->assertEquals(50000, $item->discount);
        $this->assertEquals(0, $item->subtotal);

        $sale->refresh();
        $this->assertEquals(0, $sale->total_amount);
    }

    public function test_unauthorized_user_cannot_update_discount(): void
    {
        $store = Store::create(['name' => 'Store Utama']);
        $product = Product::create([
            'sku' => 'PRD-03',
            'name' => 'Item X',
            'purchase_price' => 20000,
            'selling_price' => 50000,
            'stock' => 10,
            'store_id' => $store->id,
        ]);
        $userWithoutPerm = User::factory()->create();
        $userWithoutPerm->stores()->attach($store->id);

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0004',
            'sale_date' => Carbon::today()->toDateString(),
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'user_id' => $userWithoutPerm->id,
            'store_id' => $store->id,
        ]);

        $item = $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'selling_price' => 50000,
            'discount' => 0,
            'subtotal' => 50000,
        ]);

        $response = $this->actingAs($userWithoutPerm)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-discount', $sale->id), [
                'items' => [
                    [
                        'id' => $item->id,
                        'discount' => 5000,
                    ]
                ]
            ]);

        $response->assertStatus(403);
    }

    public function test_pos_api_endpoint_can_update_discount(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-POS-01',
            'name' => 'Kopi POS',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 20,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0005',
            'sale_date' => Carbon::today()->toDateString(),
            'total_amount' => 40000,
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $item = $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 20000,
            'discount' => 0,
            'subtotal' => 40000,
        ]);

        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'reference_number' => $sale->reference_number,
            'movement_date' => Carbon::today()->toDateString(),
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'discount' => 0,
            'total_price' => 40000,
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson("/api/pos/sales/{$sale->id}/discount", [
                'items' => [
                    [
                        'id' => $item->id,
                        'discount' => 5000,
                    ]
                ]
            ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'total_amount' => 35000,
                    'total_discount' => 5000,
                ]
            ]);

        $sale->refresh();
        $this->assertEquals(35000, $sale->total_amount);

        $item->refresh();
        $this->assertEquals(5000, $item->discount);
        $this->assertEquals(35000, $item->subtotal);
    }
}
