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

class PosSaleCancellationAndStockSyncTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create(['name' => 'Kasir Bertugas']);
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_cashier_can_cancel_sale_from_pos_api_restores_stock_and_marks_movement_cancelled(): void
    {
        [$cashier, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'PRD-TEST-01',
            'name' => 'Baju Kemeja Putih',
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'stock' => 20, // initial stock
            'store_id' => $store->id,
        ]);

        // Simulate checkout of 3 units -> product stock becomes 17
        $product->decrement('stock', 3);
        $this->assertEquals(17, $product->fresh()->stock);

        $refNumber = 'POS-261009-1234';
        $today = Carbon::today()->toDateString();

        $sale = Sale::create([
            'reference_number' => $refNumber,
            'sale_date' => $today,
            'customer_name' => 'Budi Santoso',
            'total_amount' => 300000,
            'notes' => '[POS - Kasir Bertugas] Bayar: CASH (Uang: Rp300.000, Kembali: Rp0)',
            'payment_method' => 'cash',
            'status' => 'completed',
            'user_id' => $cashier->id,
            'store_id' => $store->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 3,
            'selling_price' => 100000,
            'discount' => 0,
            'subtotal' => 300000,
        ]);

        $mov = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $cashier->id,
            'reference_number' => $refNumber,
            'movement_date' => $today,
            'type' => 'out',
            'quantity' => 3,
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'discount' => 0,
            'total_price' => 300000,
            'payment_method' => 'cash',
            'status' => 'approved',
            'notes' => "POS Penjualan - {$refNumber} (Budi Santoso)",
        ]);

        // Kasir performs cancel from POS API
        $response = $this->postJson("/api/pos/sales/{$sale->id}/cancel", [
            'cancellation_reason' => 'Pelanggan membatalkan pesanan karena salah ukuran',
            'cashier_name' => 'Kasir Bertugas',
            'user_id' => $cashier->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $sale->id,
                    'reference_number' => $refNumber,
                    'status' => 'cancelled',
                    'cancellation_reason' => 'Pelanggan membatalkan pesanan karena salah ukuran',
                ]
            ]);

        // 1. Verify product stock is RESTORED back to 20 (17 + 3)
        $this->assertEquals(20, $product->fresh()->stock);

        // 2. Verify sale status is cancelled
        $saleFresh = $sale->fresh();
        $this->assertEquals('cancelled', $saleFresh->status);
        $this->assertEquals('Pelanggan membatalkan pesanan karena salah ukuran', $saleFresh->cancellation_reason);
        $this->assertNotNull($saleFresh->cancelled_at);
        $this->assertStringContainsString('DIBATALKAN', $saleFresh->notes);

        // 3. Verify StockMovement status is cancelled and notes contain audit trail
        $movFresh = $mov->fresh();
        $this->assertEquals('cancelled', $movFresh->status);
        $this->assertStringContainsString('DIBATALKAN', $movFresh->notes);
        $this->assertStringContainsString('Pelanggan membatalkan pesanan karena salah ukuran', $movFresh->notes);
    }

    public function test_today_summary_excludes_cancelled_sales(): void
    {
        [$cashier, $store] = $this->createAdminWithStore();
        $today = Carbon::today()->toDateString();

        // Active completed sale: Rp 100.000
        Sale::create([
            'reference_number' => 'POS-261009-0001',
            'sale_date' => $today,
            'customer_name' => 'Pelanggan 1',
            'total_amount' => 100000,
            'notes' => '[POS - Kasir] Bayar: CASH',
            'payment_method' => 'cash',
            'status' => 'completed',
            'user_id' => $cashier->id,
            'store_id' => $store->id,
        ]);

        // Cancelled sale: Rp 200.000
        Sale::create([
            'reference_number' => 'POS-261009-0002',
            'sale_date' => $today,
            'customer_name' => 'Pelanggan 2',
            'total_amount' => 200000,
            'notes' => '[DIBATALKAN] [POS - Kasir] Bayar: CASH',
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'cancellation_reason' => 'Dobel transaksi',
            'cancelled_at' => Carbon::now(),
            'user_id' => $cashier->id,
            'store_id' => $store->id,
        ]);

        $response = $this->getJson("/api/pos/summary-today?store_id={$store->id}&period=today");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Total omzet should only be 100.000, not 300.000
        $this->assertEquals(1, $data['total_transactions']);
        $this->assertEquals(100000, $data['total_revenue']);
        $this->assertEquals(100000, $data['cash_revenue']);

        // Cancelled transactions info provided
        $this->assertEquals(1, $data['cancelled_transactions']);
        $this->assertEquals(200000, $data['cancelled_revenue']);
    }

    public function test_stock_out_page_excludes_cancelled_movements_from_grand_totals(): void
    {
        [$admin, $store] = $this->createAdminWithStore();
        $today = Carbon::today()->toDateString();

        $product = Product::create([
            'sku' => 'PRD-OUT-01',
            'name' => 'Sepatu Sneakers',
            'purchase_price' => 100000,
            'selling_price' => 200000,
            'stock' => 50,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => $today,
            'store_id' => $store->id,
            'type' => 'out',
            'created_by' => $admin->id,
        ]);

        // Approved active Stock Out: 1 unit, Rp 200.000
        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'reference_number' => 'POS-OUT-ACTIVE',
            'movement_date' => $today,
            'type' => 'out',
            'quantity' => 1,
            'purchase_price' => 100000,
            'selling_price' => 200000,
            'discount' => 0,
            'total_price' => 200000,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        // Cancelled Stock Out: 2 units, Rp 400.000
        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'reference_number' => 'POS-OUT-CANCELLED',
            'movement_date' => $today,
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 100000,
            'selling_price' => 200000,
            'discount' => 0,
            'total_price' => 400000,
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'notes' => '[DIBATALKAN oleh Kasir: Salah input barang]',
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', $movementDate->id));

        $response->assertStatus(200);

        // View data grand total should be 200.000 (excluding 400.000 cancelled)
        $response->assertViewHas('grandTotalHarga', 200000.0);
        $response->assertViewHas('grandTotalQuantity', 1);
        $response->assertViewHas('totalCancelledRecords', 1);

        // HTML should contain "Dibatalkan" badge and reference number
        $response->assertSee('Dibatalkan');
        $response->assertSee('POS-OUT-CANCELLED');
    }

    public function test_cannot_cancel_already_cancelled_sale(): void
    {
        [$cashier, $store] = $this->createAdminWithStore();
        $today = Carbon::today()->toDateString();

        $sale = Sale::create([
            'reference_number' => 'POS-261009-9999',
            'sale_date' => $today,
            'customer_name' => 'Pelanggan Test',
            'total_amount' => 50000,
            'notes' => 'Catatan',
            'payment_method' => 'cash',
            'status' => 'cancelled',
            'cancellation_reason' => 'Sudah batal sebelumnya',
            'cancelled_at' => Carbon::now(),
            'user_id' => $cashier->id,
            'store_id' => $store->id,
        ]);

        $response = $this->postJson("/api/pos/sales/{$sale->id}/cancel", [
            'cancellation_reason' => 'Coba batalkan lagi',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => "Transaksi #POS-261009-9999 sudah dibatalkan sebelumnya.",
            ]);
    }
}
