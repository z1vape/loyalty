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
use Carbon\Carbon;

class MovementDateSessionDefaultsTest extends TestCase
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

    public function test_can_create_movement_date_with_supplier_and_tempo_payment_method(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movement-dates.store'), [
                'date' => '2026-10-25',
                'type' => 'in',
                'supplier_name' => 'PT Jaya Vape Wholesale',
                'payment_method' => 'tempo',
                'due_date' => '2026-11-25',
                'notes' => 'Pengadaan stok awal kuartal 4',
            ]);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('movement_dates', [
            'type' => 'in',
            'supplier_name' => 'PT Jaya Vape Wholesale',
            'payment_method' => 'tempo',
            'notes' => 'Pengadaan stok awal kuartal 4',
        ]);
    }

    public function test_stock_movement_creation_inherits_session_supplier_and_payment_method_when_not_specified(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => '2026-10-25',
            'type' => 'in',
            'created_by' => $user->id,
            'supplier_name' => 'CV Sumber Rejeki Vape',
            'payment_method' => 'tempo',
            'due_date' => '2026-11-30',
        ]);

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Pod Ursa Nano Pro 2',
            'sku' => 'UNP2-BLK',
            'purchase_price' => 150000,
            'selling_price' => 220000,
            'stock' => 10,
        ]);

        // Create movement WITHOUT passing supplier_name or payment_method
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'movement_date' => '2026-10-25',
                'type' => 'in',
                'product_ids' => [$product->id],
                'quantity' => 5,
                'purchase_price' => 150000,
                'user_id' => $user->id,
            ]);

        $response->assertRedirect();

        $movement = StockMovement::where('product_id', $product->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals('CV Sumber Rejeki Vape', $movement->supplier_name);
        $this->assertEquals('tempo', $movement->payment_method);
        $this->assertEquals('pending', $movement->payment_status);
        $this->assertEquals('2026-11-30', Carbon::parse($movement->due_date)->format('Y-m-d'));
    }

    public function test_can_update_session_defaults(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => '2026-10-25',
            'type' => 'in',
            'created_by' => $user->id,
            'supplier_name' => 'Supplier Lama',
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->patch(route('movement-dates.update', $movementDate->id), [
                'supplier_name' => 'PT Supplier Baru Mandiri',
                'payment_method' => 'tempo',
                'due_date' => '2026-12-15',
                'notes' => 'Catatan sesi terupdate',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $movementDate->refresh();
        $this->assertEquals('PT Supplier Baru Mandiri', $movementDate->supplier_name);
        $this->assertEquals('tempo', $movementDate->payment_method);
        $this->assertEquals('2026-12-15', Carbon::parse($movementDate->due_date)->format('Y-m-d'));
        $this->assertEquals('Catatan sesi terupdate', $movementDate->notes);
    }

    public function test_date_show_view_renders_session_supplier_and_payment_method_badges(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => '2026-10-25',
            'type' => 'in',
            'created_by' => $user->id,
            'supplier_name' => 'PT Vape Master Indonesia',
            'payment_method' => 'tempo',
            'due_date' => '2026-11-25',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', $movementDate->id));

        $response->assertOk();
        $response->assertSee('PT Vape Master Indonesia');
        $response->assertSee('Tempo (Hutang AP)');
        $response->assertSee('Ubah Default Sesi');
    }

    public function test_can_batch_store_movements_using_items_array(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => '2026-10-25',
            'type' => 'in',
            'created_by' => $user->id,
            'supplier_name' => 'Distributor A',
            'payment_method' => 'cash',
        ]);

        $p1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Liquid Mango 60ml',
            'sku' => 'LIQ-MNG',
            'purchase_price' => 70000,
            'selling_price' => 110000,
            'stock' => 5,
        ]);

        $p2 = Product::create([
            'store_id' => $store->id,
            'name' => 'Coil 0.6 Ohm',
            'sku' => 'COIL-06',
            'purchase_price' => 35000,
            'selling_price' => 50000,
            'stock' => 15,
        ]);

        // Submit via items array (modal queue builder format)
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'movement_date_id' => $movementDate->id,
                'movement_date' => '2026-10-25',
                'type' => 'in',
                'items' => [
                    [
                        'product_id' => $p1->id,
                        'quantity' => 10,
                        'purchase_price' => 75000,
                    ],
                    [
                        'product_id' => $p2->id,
                        'quantity' => 25,
                        'purchase_price' => 32000,
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $m1 = StockMovement::where('product_id', $p1->id)->first();
        $this->assertNotNull($m1);
        $this->assertEquals(10, $m1->quantity);
        $this->assertEquals(75000, $m1->purchase_price);
        $this->assertEquals(750000, $m1->total_price);
        $this->assertEquals($user->id, $m1->user_id);
        $this->assertEquals('Distributor A', $m1->supplier_name);
        $this->assertEquals('draft', $m1->status);

        $m2 = StockMovement::where('product_id', $p2->id)->first();
        $this->assertNotNull($m2);
        $this->assertEquals(25, $m2->quantity);
        $this->assertEquals(32000, $m2->purchase_price);
        $this->assertEquals(800000, $m2->total_price);
        $this->assertEquals($user->id, $m2->user_id);
        $this->assertEquals('Distributor A', $m2->supplier_name);
        $this->assertEquals('draft', $m2->status);
    }
}
