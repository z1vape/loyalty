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

class StockInDraftEditTest extends TestCase
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

    public function test_can_update_draft_stock_in_quantity_purchase_and_selling_price(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Liquid Freebase 60ml',
            'sku' => 'LIQ-001',
            'purchase_price' => 100000,
            'selling_price' => 140000,
            'stock' => 10,
        ]);

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'type' => 'in',
            'created_by' => $user->id,
        ]);

        $movement = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-TEST01',
            'movement_date' => $movementDate->date,
            'type' => 'in',
            'quantity' => 5,
            'purchase_price' => 100000,
            'selling_price' => 140000,
            'discount' => 0,
            'total_price' => 500000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'paid_amount' => 500000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->put(route('movements.update', $movement->id), [
                'quantity' => 8,
                'purchase_price' => 120000,
                'selling_price' => 160000,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $movement->refresh();
        $this->assertEquals(8, $movement->quantity);
        $this->assertEquals(120000, (float)$movement->purchase_price);
        $this->assertEquals(160000, (float)$movement->selling_price);
        $this->assertEquals(960000, (float)$movement->total_price);
        $this->assertEquals(960000, (float)$movement->paid_amount);

        // Product stock and purchase_price should remain unchanged while in draft
        $product->refresh();
        $this->assertEquals(10, $product->stock);
        $this->assertEquals(100000, (float)$product->purchase_price);
    }

    public function test_cannot_update_approved_movement_via_draft_edit(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Device Pod Kit',
            'sku' => 'POD-001',
            'purchase_price' => 200000,
            'selling_price' => 280000,
            'stock' => 10,
        ]);

        $movement = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-TEST02',
            'movement_date' => Carbon::now()->format('Y-m-d'),
            'type' => 'in',
            'quantity' => 5,
            'purchase_price' => 200000,
            'selling_price' => 280000,
            'total_price' => 1000000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'paid_amount' => 1000000,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->put(route('movements.update', $movement->id), [
                'quantity' => 10,
                'purchase_price' => 250000,
                'selling_price' => 320000,
            ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_approving_edited_draft_movement_applies_moving_average_and_selling_price(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Catridge 0.8 Ohm',
            'sku' => 'CAT-08',
            'purchase_price' => 30000,
            'selling_price' => 45000,
            'stock' => 10,
        ]);

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'type' => 'in',
            'created_by' => $user->id,
        ]);

        $movement = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-TEST03',
            'movement_date' => $movementDate->date,
            'type' => 'in',
            'quantity' => 5,
            'purchase_price' => 30000,
            'selling_price' => 45000,
            'discount' => 0,
            'total_price' => 150000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'paid_amount' => 150000,
            'status' => 'draft',
        ]);

        // Edit draft: incoming qty 10, incoming purchase_price 40000 (price changed from 30000), selling_price 55000
        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->put(route('movements.update', $movement->id), [
                'quantity' => 10,
                'purchase_price' => 40000,
                'selling_price' => 55000,
            ]);

        // Now approve the movement
        $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.approve', $movement->id));

        $product->refresh();
        // Previous: stock 10 @ 30,000 = 300,000
        // Incoming: qty 10 @ 40,000 = 400,000
        // New Stock: 20
        // New Moving Average Cost: (300,000 + 400,000) / 20 = 35,000
        $this->assertEquals(20, $product->stock);
        $this->assertEquals(35000, (float)$product->purchase_price);
        $this->assertEquals(55000, (float)$product->selling_price);
    }

    public function test_view_displays_edit_button_and_average_price_for_draft(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'store_id' => $store->id,
            'name' => 'Cotton Bacon Prime',
            'sku' => 'COT-01',
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'stock' => 10,
        ]);

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => Carbon::now()->format('Y-m-d'),
            'type' => 'in',
            'created_by' => $user->id,
        ]);

        // Movement with price changed to 70000 (from 50000)
        $movement = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-TEST04',
            'movement_date' => $movementDate->date,
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 70000,
            'selling_price' => 95000,
            'discount' => 0,
            'total_price' => 700000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'paid_amount' => 700000,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('movement-dates.show', $movementDate->id));

        $response->assertStatus(200);
        $response->assertSee('openEditDraftModal');
        $response->assertSee('Sebelumnya: Rp 50.000');
        // Moving average: (10 * 50,000 + 10 * 70,000) / 20 = 60,000
        $response->assertSee('Rata²: Rp 60.000');
    }
}
