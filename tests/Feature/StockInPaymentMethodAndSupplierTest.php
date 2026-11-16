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
use App\Models\Account;
use Carbon\Carbon;

class StockInPaymentMethodAndSupplierTest extends TestCase
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

    public function test_can_create_stock_in_with_cash_and_supplier_name(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Oxva Xlim Pro 2',
            'sku' => 'OXVA-001',
            'cost_price' => 200000,
            'price' => 300000,
            'stock' => 10,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => 5,
                'purchase_price' => 200000,
                'movement_date' => Carbon::now()->toDateString(),
                'user_id' => $user->id,
                'payment_method' => 'cash',
                'supplier_name' => 'PT Vape Jaya Distributor',
            ]);

        $response->assertSessionHasNoErrors();

        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('cash', $movement->payment_method);
        $this->assertEquals('PT Vape Jaya Distributor', $movement->supplier_name);
        $this->assertEquals('paid', $movement->payment_status);
        $this->assertEquals(1000000, (float) $movement->paid_amount);
        $this->assertNull($movement->due_date);
    }

    public function test_can_create_stock_in_with_tempo_and_due_date(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Liquid Foom Ice Tea 30ml',
            'sku' => 'FOOM-TEA',
            'cost_price' => 80000,
            'price' => 120000,
            'stock' => 5,
        ]);

        $dueDate = Carbon::now()->addDays(30)->toDateString();

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'product_id' => $product->id,
                'type' => 'in',
                'quantity' => 10,
                'purchase_price' => 80000,
                'movement_date' => Carbon::now()->toDateString(),
                'user_id' => $user->id,
                'payment_method' => 'tempo',
                'supplier_name' => 'CV Sumber Makmur',
                'due_date' => $dueDate,
            ]);

        $response->assertSessionHasNoErrors();

        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('tempo', $movement->payment_method);
        $this->assertEquals('CV Sumber Makmur', $movement->supplier_name);
        $this->assertEquals('pending', $movement->payment_status);
        $this->assertEquals(0, (float) $movement->paid_amount);
        $this->assertEquals($dueDate, $movement->due_date->toDateString());
        $this->assertEquals(800000, (float) $movement->remaining_debt);

        // Approve movement
        $approveResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.approve', $movement->id));

        $approveResponse->assertSessionHasNoErrors();
        $movement->refresh();
        $this->assertEquals('approved', $movement->status);

        // Check it appears on payables index
        $payablesResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('accounting.payables.index'));

        $payablesResponse->assertOk();
        $payablesResponse->assertSee('CV Sumber Makmur');
        $payablesResponse->assertSee('Liquid Foom Ice Tea 30ml');
    }

    public function test_stock_in_views_contain_supplier_and_payment_method_inputs(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        // 1. movements.create for type=in
        $createResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movements.create', ['type' => 'in']));

        $createResponse->assertOk();
        $createResponse->assertSee('NAMA SUPPLIER / DISTRIBUTOR');
        $createResponse->assertSee('METODE PEMBAYARAN');
        $createResponse->assertSee('Tempo / Hutang Supplier');
        $createResponse->assertSee('TANGGAL JATUH TEMPO (DUE DATE)');

        // 2. movement-dates.show modal
        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'type' => 'in',
            'created_by' => $user->id,
        ]);

        $dateShowResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', $movementDate->id));

        $dateShowResponse->assertOk();
        $dateShowResponse->assertSee('Nama Supplier / Distributor');
        $dateShowResponse->assertSee('Tempo / Hutang Supplier (Bayar Nanti / AP)');
        $dateShowResponse->assertSee('Tanggal Jatuh Tempo');
    }
}
