<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Account;
use App\Models\SupplierPayment;
use Carbon\Carbon;

class BatchPayableTest extends TestCase
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

    public function test_can_view_payables_grouped_in_folders(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $prod1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Foom Liquid Mango',
            'sku' => 'FOOM-MNG',
            'cost_price' => 80000,
            'price' => 120000,
            'stock' => 10,
        ]);

        $prod2 = Product::create([
            'store_id' => $store->id,
            'name' => 'Oxva Xlim Cartridge 0.6',
            'sku' => 'OXVA-CRT-06',
            'cost_price' => 30000,
            'price' => 45000,
            'stock' => 20,
        ]);

        // 2 items on same date from same supplier
        $m1 = StockMovement::create([
            'reference_number' => 'TXN-1001',
            'store_id' => $store->id,
            'product_id' => $prod1->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 2,
            'purchase_price' => 80000,
            'total_price' => 160000,
            'payment_method' => 'tempo',
            'supplier_name' => 'Mr Vape Distributor',
            'movement_date' => '2026-10-20',
            'due_date' => '2026-11-10',
            'status' => 'approved',
            'payment_status' => 'pending',
            'paid_amount' => 0,
        ]);

        $m2 = StockMovement::create([
            'reference_number' => 'TXN-1001',
            'store_id' => $store->id,
            'product_id' => $prod2->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 3,
            'purchase_price' => 30000,
            'total_price' => 90000,
            'payment_method' => 'tempo',
            'supplier_name' => 'Mr Vape Distributor',
            'movement_date' => '2026-10-20',
            'due_date' => '2026-11-10',
            'status' => 'approved',
            'payment_status' => 'pending',
            'paid_amount' => 0,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('accounting.payables.index'));

        $response->assertOk();
        $response->assertSee('Mr Vape Distributor');
        $response->assertSee('Stock In 20');
        $response->assertSee('2 Produk');
        $response->assertSee('Foom Liquid Mango');
        $response->assertSee('Oxva Xlim Cartridge 0.6');
        $response->assertSee('Rp 250.000'); // 160.000 + 90.000
    }

    public function test_can_pay_batch_folder_in_full(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        Account::create([
            'store_id' => $store->id,
            'code' => '2101',
            'name' => 'Hutang Dagang',
            'type' => 'liability',
            'category' => 'Hutang Lancar',
            'normal_balance' => 'credit',
            'is_system' => true,
        ]);

        $bank = Account::create([
            'store_id' => $store->id,
            'code' => '1102',
            'name' => 'Bank BCA',
            'type' => 'asset',
            'category' => 'Kas & Bank',
            'normal_balance' => 'debit',
            'is_system' => true,
        ]);

        $prod1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Item A',
            'sku' => 'ITM-A',
            'cost_price' => 100000,
            'price' => 150000,
            'stock' => 10,
        ]);

        $prod2 = Product::create([
            'store_id' => $store->id,
            'name' => 'Item B',
            'sku' => 'ITM-B',
            'cost_price' => 50000,
            'price' => 80000,
            'stock' => 10,
        ]);

        $m1 = StockMovement::create([
            'reference_number' => 'TXN-1001',
            'store_id' => $store->id,
            'product_id' => $prod1->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 1,
            'purchase_price' => 100000,
            'total_price' => 100000,
            'payment_method' => 'tempo',
            'supplier_name' => 'Supplier X',
            'movement_date' => '2026-10-20',
            'due_date' => '2026-11-20',
            'status' => 'approved',
            'payment_status' => 'pending',
            'paid_amount' => 0,
        ]);

        $m2 = StockMovement::create([
            'reference_number' => 'TXN-1001',
            'store_id' => $store->id,
            'product_id' => $prod2->id,
            'user_id' => $user->id,
            'type' => 'in',
            'quantity' => 1,
            'purchase_price' => 50000,
            'total_price' => 50000,
            'payment_method' => 'tempo',
            'supplier_name' => 'Supplier X',
            'movement_date' => '2026-10-20',
            'due_date' => '2026-11-20',
            'status' => 'approved',
            'payment_status' => 'pending',
            'paid_amount' => 0,
        ]);

        // Batch pay full 150,000
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('accounting.payables.store-batch'), [
                'movement_ids' => "{$m1->id},{$m2->id}",
                'payment_date' => '2026-10-21',
                'payment_account_id' => $bank->id,
                'amount' => 150000,
                'reference_number' => 'TRF-FULL-01',
                'notes' => 'Pelunasan seluruh item pengadaan',
            ]);

        $response->assertRedirect(route('accounting.payables.index'));

        $m1->refresh();
        $m2->refresh();

        $this->assertEquals(100000, $m1->paid_amount);
        $this->assertEquals('paid', $m1->payment_status);

        $this->assertEquals(50000, $m2->paid_amount);
        $this->assertEquals('paid', $m2->payment_status);

        $this->assertCount(2, SupplierPayment::all());
    }
}
