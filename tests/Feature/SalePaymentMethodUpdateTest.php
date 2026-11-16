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
use App\Models\MovementDate;
use Carbon\Carbon;

class SalePaymentMethodUpdateTest extends TestCase
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

    public function test_authorized_user_can_update_payment_method_and_syncs_movements_and_notes(): void
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
            'reference_number' => 'POS-261008-0001',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'Pelanggan Umum',
            'total_amount' => 50000,
            'notes' => '[POS - Kasir] Bayar: CASH (Uang: Rp50.000, Kembali: Rp0)',
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 25000,
            'discount' => 0,
            'subtotal' => 50000,
        ]);

        $movement = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
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

        // Request update payment method to transfer
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-payment-method', $sale->id), [
                'payment_method' => 'transfer',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'payment_method' => 'transfer',
            'payment_method_label' => 'Transfer Bank',
        ]);

        // Assert Sale payment_method and notes were updated
        $sale->refresh();
        $this->assertEquals('transfer', $sale->payment_method);
        $this->assertStringContainsString('Bayar: TRANSFER', $sale->notes);
        $this->assertStringNotContainsString('Bayar: CASH', $sale->notes);

        // Assert StockMovement payment_method was synchronized
        $movement->refresh();
        $this->assertEquals('transfer', $movement->payment_method);
    }

    public function test_changing_payment_method_syncs_to_stock_out_movement_date_show(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-02',
            'name' => 'Teh Melati',
            'purchase_price' => 5000,
            'selling_price' => 10000,
            'stock' => 30,
            'store_id' => $store->id,
        ]);

        $today = Carbon::today()->toDateString();
        $movDate = MovementDate::create([
            'date' => $today,
            'store_id' => $store->id,
            'type' => 'out',
            'created_by' => $admin->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0002',
            'sale_date' => $today,
            'customer_name' => 'Budi',
            'total_amount' => 20000,
            'notes' => '[POS - Kasir] Bayar: CASH (Uang: Rp20.000, Kembali: Rp0)',
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'reference_number' => $sale->reference_number,
            'movement_date' => $today,
            'type' => 'out',
            'quantity' => 2,
            'selling_price' => 10000,
            'total_price' => 20000,
            'payment_method' => 'cash',
        ]);

        // Check before update: payment breakdown in date_show
        $viewBefore = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', $movDate->id));
        $viewBefore->assertStatus(200);
        $breakdownBefore = $viewBefore->viewData('paymentBreakdown');
        $this->assertEquals(20000, $breakdownBefore['cash']['amount']);
        $this->assertEquals(0, $breakdownBefore['qris']['amount']);

        // Now update payment method to qris
        $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-payment-method', $sale->id), [
                'payment_method' => 'qris',
            ])
            ->assertStatus(200);

        // Check after update: payment breakdown in date_show
        $viewAfter = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', $movDate->id));
        $viewAfter->assertStatus(200);
        $breakdownAfter = $viewAfter->viewData('paymentBreakdown');
        $this->assertEquals(0, $breakdownAfter['cash']['amount']);
        $this->assertEquals(20000, $breakdownAfter['qris']['amount']);
    }

    public function test_changing_payment_method_syncs_to_pos_api_summary_and_filters(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0003',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'Pelanggan POS',
            'total_amount' => 75000,
            'notes' => '[POS - Kasir] Bayar: CASH (Uang: Rp75.000, Kembali: Rp0)',
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        // Initially POS summary shows 75,000 cash revenue
        $summaryInitial = $this->getJson("/api/pos/summary-today?store_id={$store->id}");
        $summaryInitial->assertStatus(200);
        $this->assertEquals(75000, $summaryInitial->json('data.cash_revenue'));
        $this->assertEquals(0, $summaryInitial->json('data.non_cash_revenue'));

        // Update payment method to debit
        $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-payment-method', $sale->id), [
                'payment_method' => 'debit',
            ])
            ->assertStatus(200);

        // Now POS summary shows 0 cash revenue and 75,000 non-cash revenue
        $summaryAfter = $this->getJson("/api/pos/summary-today?store_id={$store->id}");
        $summaryAfter->assertStatus(200);
        $this->assertEquals(0, $summaryAfter->json('data.cash_revenue'));
        $this->assertEquals(75000, $summaryAfter->json('data.non_cash_revenue'));

        // Recent sales filter by payment_method=debit returns 1 sale
        $recentDebit = $this->getJson("/api/pos/recent-sales?store_id={$store->id}&payment_method=debit");
        $recentDebit->assertStatus(200);
        $this->assertEquals(1, $recentDebit->json('count'));

        // Recent sales filter by payment_method=cash returns 0 sales
        $recentCash = $this->getJson("/api/pos/recent-sales?store_id={$store->id}&payment_method=cash");
        $recentCash->assertStatus(200);
        $this->assertEquals(0, $recentCash->json('count'));
    }

    public function test_updating_payment_method_via_pos_api_endpoint(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-04',
            'name' => 'Snack Keripik',
            'purchase_price' => 20000,
            'selling_price' => 45000,
            'stock' => 15,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0004',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'POS Mobile Customer',
            'total_amount' => 45000,
            'notes' => '[POS - Kasir] Bayar: CASH (Uang: Rp45.000, Kembali: Rp0)',
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'reference_number' => $sale->reference_number,
            'movement_date' => Carbon::today()->toDateString(),
            'type' => 'out',
            'quantity' => 1,
            'selling_price' => 45000,
            'total_price' => 45000,
            'payment_method' => 'cash',
        ]);

        // PUT to POS API route
        $response = $this->putJson("/api/pos/sales/{$sale->id}/payment-method", [
            'payment_method' => 'qris',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'data' => [
                'payment_method' => 'qris',
                'payment_method_label' => 'QRIS',
            ]
        ]);

        $sale->refresh();
        $this->assertEquals('qris', $sale->payment_method);
        $this->assertStringContainsString('Bayar: QRIS', $sale->notes);

        $mov = StockMovement::where('reference_number', $sale->reference_number)->first();
        $this->assertEquals('qris', $mov->payment_method);
    }

    public function test_validation_fails_for_invalid_payment_method(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $sale = Sale::create([
            'reference_number' => 'POS-261008-0005',
            'sale_date' => Carbon::today()->toDateString(),
            'total_amount' => 10000,
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-payment-method', $sale->id), [
                'payment_method' => 'crypto', // invalid
            ]);

        $response->assertStatus(422);
    }

    public function test_updating_payment_method_updates_sales_index_payment_breakdown(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $sale1 = Sale::create([
            'reference_number' => 'POS-261008-BRK1',
            'sale_date' => Carbon::today()->toDateString(),
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $sale2 = Sale::create([
            'reference_number' => 'POS-261008-BRK2',
            'sale_date' => Carbon::today()->toDateString(),
            'total_amount' => 30000,
            'payment_method' => 'qris',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        // Initial view check: cash=50,000, qris=30,000
        $resBefore = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index'));

        $resBefore->assertStatus(200);
        $breakdownBefore = $resBefore->viewData('paymentBreakdown');
        $this->assertEquals(50000, $breakdownBefore['cash']['amount']);
        $this->assertEquals(30000, $breakdownBefore['qris']['amount']);
        $this->assertEquals(0, $breakdownBefore['transfer']['amount']);

        // Update sale1 from cash to transfer
        $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-payment-method', $sale1->id), [
                'payment_method' => 'transfer',
            ])
            ->assertStatus(200);

        // After view check: cash=0, transfer=50,000, qris=30,000
        $resAfter = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index'));

        $resAfter->assertStatus(200);
        $breakdownAfter = $resAfter->viewData('paymentBreakdown');
        $this->assertEquals(0, $breakdownAfter['cash']['amount']);
        $this->assertEquals(50000, $breakdownAfter['transfer']['amount']);
        $this->assertEquals(30000, $breakdownAfter['qris']['amount']);
        $resAfter->assertSee('Rincian Kalkulasi Metode Pembayaran');
    }

    public function test_update_payment_method_syncs_accounting_journal_items_to_target_account(): void
    {
        [$admin, $store] = $this->createAdminWithStore();
        app(\App\Services\AccountingService::class)->ensureStoreAccounts($store->id);

        $bankAccount = \App\Models\Account::withoutGlobalScopes()->where('store_id', $store->id)->where('code', '1102')->first();
        $cashAccount = \App\Models\Account::withoutGlobalScopes()->where('store_id', $store->id)->where('code', '1101')->first();

        $sale = Sale::create([
            'reference_number' => 'POS-261109-9999',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'Pelanggan Bank',
            'total_amount' => 100000,
            'notes' => 'Bayar: CASH',
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        // Record initial journal (cash)
        app(\App\Services\AccountingService::class)->recordSaleJournal($sale);

        // Before update: cash account has balance 100,000, bank account has balance 0
        $this->assertEquals(100000, $cashAccount->calculateBalance());
        $this->assertEquals(0, $bankAccount->calculateBalance());

        // Update payment method to transfer
        $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-payment-method', $sale->id), [
                'payment_method' => 'transfer',
            ])
            ->assertStatus(200);

        // After update: cash account balance becomes 0, bank account balance becomes 100,000
        $this->assertEquals(0, $cashAccount->calculateBalance());
        $this->assertEquals(100000, $bankAccount->calculateBalance());
    }
}
