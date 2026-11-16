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
use App\Models\Permission;
use Carbon\Carbon;

class SaleCashierUpdateTest extends TestCase
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

    public function test_authorized_user_can_update_sale_cashier_and_syncs_movements_and_notes(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $cashierA = User::factory()->create(['name' => 'Budi Santoso']);
        $cashierB = User::factory()->create(['name' => 'Siti Rahma']);

        $product = Product::create([
            'sku' => 'PRD-01',
            'name' => 'Kopi Robusta',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 50,
            'store_id' => $store->id,
        ]);

        $sale = Sale::create([
            'reference_number' => 'POS-261002-0001',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'Pelanggan Umum',
            'total_amount' => 50000,
            'notes' => '[POS - Budi Santoso] Bayar: CASH (Uang: Rp50.000, Kembali: Rp0)',
            'user_id' => $cashierA->id,
            'store_id' => $store->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 25000,
            'discount' => 0,
            'subtotal' => 50000,
        ]);

        StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $cashierA->id,
            'reference_number' => $sale->reference_number,
            'movement_date' => Carbon::today()->toDateString(),
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'discount' => 0,
            'total_price' => 50000,
            'notes' => "POS Penjualan - {$sale->reference_number}",
        ]);

        // Request update cashier to cashierB
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-cashier', $sale->id), [
                'user_id' => $cashierB->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'cashier' => [
                'id' => $cashierB->id,
                'name' => 'Siti Rahma',
            ]
        ]);

        // Assert Sale user_id and notes were updated
        $sale->refresh();
        $this->assertEquals($cashierB->id, $sale->user_id);
        $this->assertStringContainsString('[POS - Siti Rahma]', $sale->notes);

        // Assert StockMovement user_id was synchronized
        $movement = StockMovement::where('reference_number', $sale->reference_number)->first();
        $this->assertNotNull($movement);
        $this->assertEquals($cashierB->id, $movement->user_id);
    }

    public function test_changing_cashier_updates_pos_recent_sales_and_summary_api(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $cashierA = User::factory()->create(['name' => 'Kasir Pertama']);
        $cashierB = User::factory()->create(['name' => 'Kasir Pengganti']);

        $sale = Sale::create([
            'reference_number' => 'POS-261002-0002',
            'sale_date' => Carbon::today()->toDateString(),
            'customer_name' => 'Pelanggan POS',
            'total_amount' => 75000,
            'notes' => '[POS - Kasir Pertama] Bayar: QRIS (Uang: Rp75.000, Kembali: Rp0)',
            'user_id' => $cashierA->id,
            'store_id' => $store->id,
        ]);

        // Verify initial POS recent sales returns Kasir Pertama
        $posResponse = $this->getJson("/api/pos/recent-sales?store_id={$store->id}");
        $posResponse->assertStatus(200);
        $data = $posResponse->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('Kasir Pertama', $data[0]['user']['name']);

        // Now update cashier in inventory_stock
        $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-cashier', $sale->id), [
                'user_id' => $cashierB->id,
            ])
            ->assertStatus(200);

        // Verify POS recent sales now returns Kasir Pengganti
        $posResponseAfter = $this->getJson("/api/pos/recent-sales?store_id={$store->id}");
        $posResponseAfter->assertStatus(200);
        $dataAfter = $posResponseAfter->json('data');
        $this->assertEquals('Kasir Pengganti', $dataAfter[0]['user']['name']);

        // Verify POS summary filters correctly for new cashier
        $summaryAfter = $this->getJson("/api/pos/summary-today?store_id={$store->id}&user_id={$cashierB->id}");
        $summaryAfter->assertStatus(200);
        $this->assertEquals(1, $summaryAfter->json('data.total_transactions'));
        $this->assertEquals(75000, $summaryAfter->json('data.total_revenue'));

        // Old cashier should now have 0 transactions
        $summaryOld = $this->getJson("/api/pos/summary-today?store_id={$store->id}&user_id={$cashierA->id}");
        $summaryOld->assertStatus(200);
        $this->assertEquals(0, $summaryOld->json('data.total_transactions'));
    }

    public function test_validation_fails_for_invalid_cashier(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        $sale = Sale::create([
            'reference_number' => 'POS-261002-0003',
            'sale_date' => Carbon::today()->toDateString(),
            'total_amount' => 10000,
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->putJson(route('sales.update-cashier', $sale->id), [
                'user_id' => 999999, // non-existent user
            ]);

        $response->assertStatus(422);
    }
}
