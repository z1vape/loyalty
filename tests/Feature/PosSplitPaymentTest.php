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

class PosSplitPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore(): array
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create([
            'name' => 'Store Utama',
            'address' => 'Jl. Merdeka No. 10',
            'phone' => '08123456789'
        ]);
        $user = User::factory()->create(['name' => 'Kasir Bertugas']);
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_pos_checkout_with_split_payment_success(): void
    {
        [$cashier, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SPLIT-01',
            'name' => 'Sepatu Sneakers',
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'stock' => 10,
            'store_id' => $store->id,
        ]);

        $payload = [
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'cashier_name' => 'Kasir Bertugas',
            'customer_name' => 'Pelanggan VIP',
            'payment_method' => 'split',
            'amount_paid' => 120000,
            'change_amount' => 20000,
            'payment_splits' => [
                [
                    'method' => 'cash',
                    'amount' => 60000,
                    'amount_paid' => 80000,
                    'change_amount' => 20000,
                ],
                [
                    'method' => 'qris',
                    'amount' => 40000,
                    'amount_paid' => 40000,
                    'change_amount' => 0,
                ]
            ],
            'notes' => 'Catatan Tambahan',
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'selling_price' => 100000,
                    'discount' => 0,
                    'subtotal' => 100000,
                ]
            ],
            'total_amount' => 100000,
        ];

        $response = $this->postJson('/api/pos/checkout', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Transaksi POS berhasil diselesaikan!',
            'data' => [
                'payment_method' => 'SPLIT',
                'total_amount' => 100000,
                'amount_paid' => 120000,
                'change_amount' => 20000,
            ]
        ]);

        // Stock should be decremented from 10 to 9
        $this->assertEquals(9, $product->fresh()->stock);

        // Sale record
        $sale = Sale::first();
        $this->assertNotNull($sale);
        $this->assertEquals('split', $sale->payment_method);
        $this->assertCount(2, $sale->payment_splits);
        $this->assertEquals(60000, $sale->payment_splits[0]['amount']);
        $this->assertEquals(40000, $sale->payment_splits[1]['amount']);
        $this->assertStringContainsString('SPLIT', $sale->notes);
        $this->assertStringContainsString('Tunai: Rp60.000', $sale->notes);
        $this->assertStringContainsString('QRIS: Rp40.000', $sale->notes);

        // Stock movement recorded
        $movement = StockMovement::first();
        $this->assertNotNull($movement);
        $this->assertEquals('split', $movement->payment_method);
    }

    public function test_pos_checkout_rejects_unbalanced_split_amounts(): void
    {
        [$cashier, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SPLIT-02',
            'name' => 'Kemeja Flannel',
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'stock' => 10,
            'store_id' => $store->id,
        ]);

        $payload = [
            'store_id' => $store->id,
            'payment_method' => 'split',
            'amount_paid' => 80000,
            'change_amount' => 0,
            'payment_splits' => [
                ['method' => 'cash', 'amount' => 50000],
                ['method' => 'transfer', 'amount' => 30000],
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'selling_price' => 100000,
                    'discount' => 0,
                    'subtotal' => 100000,
                ]
            ],
            'total_amount' => 100000,
        ];

        $response = $this->postJson('/api/pos/checkout', $payload);

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error'
        ]);
        $this->assertStringContainsString('tidak sama dengan total belanja', $response->json('message'));
    }

    public function test_today_summary_properly_separates_cash_and_non_cash_revenue_for_split_sales(): void
    {
        [$cashier, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SPLIT-03',
            'name' => 'Jaket Hoodie',
            'purchase_price' => 50000,
            'selling_price' => 200000,
            'stock' => 10,
            'store_id' => $store->id,
        ]);

        // Checkout split: Rp 80.000 cash + Rp 120.000 QRIS
        $payload = [
            'store_id' => $store->id,
            'user_id' => $cashier->id,
            'payment_method' => 'split',
            'amount_paid' => 200000,
            'change_amount' => 0,
            'payment_splits' => [
                ['method' => 'cash', 'amount' => 80000, 'amount_paid' => 80000, 'change_amount' => 0],
                ['method' => 'qris', 'amount' => 120000, 'amount_paid' => 120000, 'change_amount' => 0],
            ],
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'selling_price' => 200000,
                    'discount' => 0,
                    'subtotal' => 200000,
                ]
            ],
            'total_amount' => 200000,
        ];

        $this->postJson('/api/pos/checkout', $payload)->assertStatus(200);

        // Fetch summary
        $summaryRes = $this->getJson("/api/pos/summary-today?store_id={$store->id}");
        $summaryRes->assertStatus(200);

        $data = $summaryRes->json('data');
        $this->assertEquals(200000, $data['total_revenue']);
        $this->assertEquals(80000, $data['cash_revenue']);
        $this->assertEquals(120000, $data['non_cash_revenue']);
    }

    public function test_update_payment_method_to_split(): void
    {
        [$cashier, $store] = $this->createAdminWithStore();

        $sale = Sale::create([
            'reference_number' => 'POS-261012-9999',
            'sale_date' => Carbon::now()->toDateString(),
            'customer_name' => 'Pelanggan Umum',
            'total_amount' => 100000,
            'notes' => '[POS - Kasir] Bayar: CASH (Uang: Rp100.000, Kembali: Rp0)',
            'payment_method' => 'cash',
            'user_id' => $cashier->id,
            'store_id' => $store->id,
        ]);

        $product = Product::create([
            'sku' => 'SKU-UPDATE-01',
            'name' => 'Produk Uji',
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'stock' => 10,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $cashier->id,
            'reference_number' => $sale->reference_number,
            'movement_date' => Carbon::now()->toDateString(),
            'type' => 'out',
            'quantity' => 1,
            'selling_price' => 100000,
            'total_price' => 100000,
            'payment_method' => 'cash',
        ]);

        $updatePayload = [
            'payment_method' => 'split',
            'payment_splits' => [
                ['method' => 'cash', 'amount' => 50000],
                ['method' => 'transfer', 'amount' => 50000],
            ]
        ];

        $res = $this->putJson("/api/pos/sales/{$sale->id}/payment-method", $updatePayload);
        $res->assertStatus(200);

        $freshSale = $sale->fresh();
        $this->assertEquals('split', $freshSale->payment_method);
        $this->assertCount(2, $freshSale->payment_splits);
        $this->assertStringContainsString('SPLIT', $freshSale->notes);

        $freshMov = StockMovement::where('reference_number', $sale->reference_number)->first();
        $this->assertEquals('split', $freshMov->payment_method);
        $this->assertStringContainsString('SPLIT', $freshMov->notes);

        // Also test that POST method works seamlessly for production compatibility
        $postPayload = [
            'payment_method' => 'split',
            'payment_splits' => [
                ['method' => 'cash', 'amount' => 40000],
                ['method' => 'qris', 'amount' => 60000],
            ]
        ];
        $postRes = $this->postJson("/api/pos/sales/{$sale->id}/payment-method", $postPayload);
        $postRes->assertStatus(200);

        $freshSale2 = $sale->fresh();
        $this->assertEquals(40000, $freshSale2->payment_splits[0]['amount']);
        $this->assertEquals(60000, $freshSale2->payment_splits[1]['amount']);

        $freshMov2 = StockMovement::where('reference_number', $sale->reference_number)->first();
        $this->assertEquals('split', $freshMov2->payment_method);
        $this->assertStringContainsString('QRIS: Rp60.000', $freshMov2->notes);
    }
}
