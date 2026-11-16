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

class SalePaymentMethodSyncConsistencyTest extends TestCase
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

    public function test_sales_with_transfer_notes_are_categorized_as_transfer_even_if_raw_payment_method_was_cash(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        // Simulate a legacy POS sale where raw column was 'cash' but receipt notes said TRANSFER
        $sale = Sale::create([
            'reference_number' => 'POS-261009-TEST1',
            'sale_date' => '2026-10-09',
            'total_amount' => 630000,
            'notes' => '[POS - Jovanus Putra Junior] Bayar: TRANSFER (Uang: Rp630.000, Kembali: Rp0)',
            'payment_method' => 'cash', // accidentally defaulted
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        // Accessor should report 'transfer' because of explicit notes
        $this->assertEquals('transfer', $sale->payment_method);

        // Sales index breakdown should count it as transfer, not cash
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index', ['start_date' => '2026-10-09', 'end_date' => '2026-10-09']));

        $response->assertStatus(200);
        $breakdown = $response->viewData('paymentBreakdown');
        $this->assertEquals(630000, $breakdown['transfer']['amount']);
        $this->assertEquals(0, $breakdown['cash']['amount']);
    }

    public function test_pos_checkout_saves_payment_method_on_both_sale_and_stock_movements(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-TF1',
            'name' => 'Liquid Saltnic',
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'stock' => 10,
            'store_id' => $store->id,
        ]);

        $checkoutPayload = [
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'cashier_name' => 'Jovanus',
            'customer_name' => 'Pelanggan Transfer',
            'payment_method' => 'transfer',
            'total_amount' => 100000,
            'amount_paid' => 100000,
            'change_amount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 1,
                    'selling_price' => 100000,
                    'discount' => 0,
                    'subtotal' => 100000,
                ]
            ],
        ];

        $response = $this->postJson('/api/pos/checkout', $checkoutPayload);
        $response->assertStatus(200);

        $saleId = $response->json('data.sale_id');
        $sale = Sale::find($saleId);

        $this->assertNotNull($sale);
        $this->assertEquals('transfer', $sale->payment_method);

        $movement = StockMovement::where('reference_number', $sale->reference_number)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('transfer', $movement->payment_method);
    }
}
