<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\MovementDate;
use Carbon\Carbon;

class StockOutSalesAndShiftSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create(['name' => 'Budi Kasir']);
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_create_stock_out_creates_sale_and_appears_in_sales_history_and_shift_recap(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SYNC-01',
            'name' => 'Liquid Saltnic Strawberry',
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'stock' => 20,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $today = Carbon::now()->toDateString();

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => 2,
                'movement_date' => $today,
                'user_id' => $user->id,
                'selling_price' => 100000,
                'discount' => 10000,
                'total_price' => 190000,
                'payment_method' => 'cash',
                'notes' => 'Penjualan offline ke pelanggan',
            ]);

        $response->assertSessionHas('success');

        // 1. Assert StockMovement was created
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 2,
            'selling_price' => 100000,
            'discount' => 10000,
            'total_price' => 190000,
            'payment_method' => 'cash',
        ]);

        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $refNumber = $movement->reference_number;
        $this->assertStringStartsWith('SALE-', $refNumber);

        // 2. Assert Sale was created
        $this->assertDatabaseHas('sales', [
            'reference_number' => $refNumber,
            'total_amount' => 190000,
            'payment_method' => 'cash',
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        // 3. Assert SaleItem was created
        $sale = Sale::where('reference_number', $refNumber)->first();
        $this->assertNotNull($sale);
        $this->assertDatabaseHas('sale_items', [
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 100000,
            'discount' => 10000,
            'subtotal' => 190000,
        ]);

        // 4. Assert stock was deducted
        $product->refresh();
        $this->assertEquals(18, $product->stock); // 20 - 2

        // 5. Assert appears in Sales History page
        $resSalesHistory = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index'));
        $resSalesHistory->assertStatus(200);
        $resSalesHistory->assertSee($refNumber);
        $resSalesHistory->assertSee('190.000');

        // 6. Assert appears in POS Shift Recap endpoints
        $summaryRes = $this->getJson("/api/pos/summary-today?store_id={$store->id}&period=today");
        $summaryRes->assertStatus(200);
        $summaryRes->assertJsonPath('status', 'success');
        $this->assertEquals(1, $summaryRes->json('data.total_transactions'));
        $this->assertEquals(190000, $summaryRes->json('data.total_revenue'));
        $this->assertEquals(190000, $summaryRes->json('data.cash_revenue'));
        $this->assertEquals(2, $summaryRes->json('data.total_items_sold'));

        $recentRes = $this->getJson("/api/pos/recent-sales?store_id={$store->id}&period=today");
        $recentRes->assertStatus(200);
        $this->assertEquals(1, $recentRes->json('count'));
        $this->assertEquals($refNumber, $recentRes->json('data.0.reference_number'));
    }

    public function test_deleting_stock_out_movement_cleans_up_sale(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SYNC-02',
            'name' => 'Device Pod Mod',
            'purchase_price' => 150000,
            'selling_price' => 250000,
            'stock' => 10,
            'min_stock' => 1,
            'store_id' => $store->id,
        ]);

        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => 1,
                'movement_date' => Carbon::now()->toDateString(),
                'user_id' => $user->id,
                'selling_price' => 250000,
                'discount' => 0,
                'total_price' => 250000,
                'payment_method' => 'qris',
            ]);

        $movement = StockMovement::where('product_id', $product->id)->first();
        $refNumber = $movement->reference_number;
        $this->assertDatabaseHas('sales', ['reference_number' => $refNumber]);

        // Delete movement
        $resDelete = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->delete(route('movements.destroy', $movement->id));

        $resDelete->assertSessionHas('success');
        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);
        $this->assertDatabaseMissing('sales', ['reference_number' => $refNumber]);

        $product->refresh();
        $this->assertEquals(10, $product->stock); // reverted 9 + 1 = 10
    }

    public function test_deleting_movement_date_cleans_up_associated_sales(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-SYNC-03',
            'name' => 'Coil 0.6 Ohm',
            'purchase_price' => 25000,
            'selling_price' => 45000,
            'stock' => 50,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $today = Carbon::now()->toDateString();

        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'product_id' => $product->id,
                'type' => 'out',
                'quantity' => 3,
                'movement_date' => $today,
                'user_id' => $user->id,
                'selling_price' => 45000,
                'discount' => 0,
                'total_price' => 135000,
                'payment_method' => 'transfer',
            ]);

        $movement = StockMovement::where('product_id', $product->id)->first();
        $refNumber = $movement->reference_number;
        $this->assertDatabaseHas('sales', ['reference_number' => $refNumber]);

        $mDate = MovementDate::where('date', $today)->where('type', 'out')->first();
        $this->assertNotNull($mDate);

        // Delete movement date
        $resDelete = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->delete(route('movement-dates.destroy', $mDate->id));

        $resDelete->assertRedirect(route('movements.index'));
        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);
        $this->assertDatabaseMissing('sales', ['reference_number' => $refNumber]);

        $product->refresh();
        $this->assertEquals(50, $product->stock); // reverted 47 + 3 = 50
    }

    public function test_artisan_sync_historical_stock_out_command_syncs_movements(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product1 = Product::create([
            'sku' => 'SKU-HIST-01',
            'name' => 'Pod Cartridge 0.8',
            'purchase_price' => 30000,
            'selling_price' => 40000,
            'stock' => 15,
            'store_id' => $store->id,
        ]);

        $product2 = Product::create([
            'sku' => 'SKU-HIST-02',
            'name' => 'Lanyard Strap',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 10,
            'store_id' => $store->id,
        ]);

        // Create historical StockMovements directly (simulating pre-update production records)
        $refHist = 'TXN-LEGACY-001';
        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product1->id,
            'user_id' => $user->id,
            'reference_number' => $refHist,
            'movement_date' => '2026-10-01',
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 30000,
            'selling_price' => 40000,
            'discount' => 5000,
            'total_price' => 75000,
            'payment_method' => 'cash',
            'notes' => 'Transaksi lama di production',
        ]);

        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product2->id,
            'user_id' => $user->id,
            'reference_number' => $refHist,
            'movement_date' => '2026-10-01',
            'type' => 'out',
            'quantity' => 1,
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'discount' => 0,
            'total_price' => 20000,
            'payment_method' => 'cash',
            'notes' => 'Transaksi lama di production',
        ]);

        // Verify no sale exists yet
        $this->assertDatabaseMissing('sales', ['reference_number' => $refHist]);

        // 1. Dry run test
        $this->artisan('stock-out:sync-sales', ['--dry-run' => true])
            ->expectsOutputToContain('Ditemukan 1 transaksi (2 baris item) Stock Out')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('sales', ['reference_number' => $refHist]);

        // 2. Real sync test
        $this->artisan('stock-out:sync-sales')
            ->expectsOutputToContain('Berhasil menyinkronkan 1 transaksi penjualan (2 item)')
            ->assertExitCode(0);

        // 3. Verify Sale was created with accumulated total: 75.000 + 20.000 = 95.000
        $this->assertDatabaseHas('sales', [
            'reference_number' => $refHist,
            'total_amount' => 95000,
            'payment_method' => 'cash',
            'user_id' => $user->id,
            'store_id' => $store->id,
        ]);

        $sale = Sale::where('reference_number', $refHist)->first();
        $this->assertNotNull($sale);
        $this->assertEquals(2, $sale->items()->count());

        // 4. Running it again is idempotent (nothing to sync)
        $this->artisan('stock-out:sync-sales')
            ->expectsOutputToContain('Seluruh data Stock Out sudah tersinkronisasi')
            ->assertExitCode(0);
    }
}

