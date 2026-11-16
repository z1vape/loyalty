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

class StockInMultiProductMovementTest extends TestCase
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

    public function test_can_create_stock_in_for_multiple_products_simultaneously(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $p1 = Product::create([
            'store_id' => $store->id,
            'name' => 'FOOM AVOCADO SHAKE - 30MG',
            'sku' => 'FOOM-AVO',
            'cost_price' => 80000,
            'price' => 110000,
            'stock' => 5,
        ]);

        $p2 = Product::create([
            'store_id' => $store->id,
            'name' => 'FOOM BANANA CREAM - 30MG',
            'sku' => 'FOOM-BANANA',
            'cost_price' => 80000,
            'price' => 110000,
            'stock' => 10,
        ]);

        $p3 = Product::create([
            'store_id' => $store->id,
            'name' => 'FOOM BANANA MILKSHAKE - 30MG',
            'sku' => 'FOOM-MILK',
            'cost_price' => 80000,
            'price' => 110000,
            'stock' => 0,
        ]);

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'created_by' => $user->id,
            'notes' => 'Catatan mutasi batch',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'movement_date_id' => $movementDate->id,
                'movement_date' => now()->toDateString(),
                'user_id' => $user->id,
                'type' => 'in',
                'product_ids' => [$p1->id, $p2->id, $p3->id],
                'quantity' => 2,
                'purchase_price' => 90000,
                'notes' => 'Batch Stock In ala Olsera',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Check movements created for all 3 products as DRAFT
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $p1->id,
            'type' => 'in',
            'quantity' => 2,
            'purchase_price' => 90000,
            'total_price' => 180000,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $p2->id,
            'type' => 'in',
            'quantity' => 2,
            'purchase_price' => 90000,
            'total_price' => 180000,
            'status' => 'draft',
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $p3->id,
            'type' => 'in',
            'quantity' => 2,
            'purchase_price' => 90000,
            'total_price' => 180000,
            'status' => 'draft',
        ]);

        // Physical stock should NOT increase yet while in DRAFT
        $this->assertEquals(5, $p1->fresh()->stock);
        $this->assertEquals(10, $p2->fresh()->stock);
        $this->assertEquals(0, $p3->fresh()->stock);

        // Bulk Approve all drafts for this date
        $approveResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.bulk-approve'), [
                'movement_date_id' => $movementDate->id,
                'approve_all' => 1,
            ]);

        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success');

        // Check stock updated after approval
        $this->assertEquals(7, $p1->fresh()->stock);
        $this->assertEquals(12, $p2->fresh()->stock);
        $this->assertEquals(2, $p3->fresh()->stock);

        // Status should now be approved
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $p1->id,
            'status' => 'approved',
        ]);
    }

    public function test_can_create_single_product_draft_and_approve_it(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $p1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Single Product Test',
            'sku' => 'SINGLE-001',
            'cost_price' => 50000,
            'price' => 70000,
            'stock' => 1,
        ]);

        $movementDate = MovementDate::create([
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.store'), [
                'movement_date_id' => $movementDate->id,
                'movement_date' => now()->toDateString(),
                'user_id' => $user->id,
                'type' => 'in',
                'product_id' => $p1->id,
                'quantity' => 5,
                'purchase_price' => 55000,
            ]);

        $response->assertRedirect();

        // While draft, stock is still 1
        $this->assertEquals(1, $p1->fresh()->stock);

        $movement = StockMovement::where('product_id', $p1->id)->first();
        $this->assertEquals('draft', $movement->status);

        // Now Approve single movement
        $approveResponse = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('movements.approve', $movement->id));

        $approveResponse->assertRedirect();
        $approveResponse->assertSessionHas('success');

        // After approval, stock is updated to 6
        $this->assertEquals(6, $p1->fresh()->stock);
        $this->assertEquals('approved', $movement->fresh()->status);
    }

    public function test_deleting_draft_movement_does_not_deduct_stock(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $p1 = Product::create([
            'store_id' => $store->id,
            'name' => 'Draft Delete Test',
            'sku' => 'DRAFT-001',
            'cost_price' => 50000,
            'price' => 70000,
            'stock' => 10,
        ]);

        $movement = StockMovement::create([
            'product_id' => $p1->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-99999',
            'movement_date' => now()->toDateString(),
            'type' => 'in',
            'quantity' => 5,
            'purchase_price' => 50000,
            'total_price' => 250000,
            'status' => 'draft',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->delete(route('movements.destroy', $movement->id));

        $response->assertRedirect();

        // Stock should remain 10 (not reduced to 5)
        $this->assertEquals(10, $p1->fresh()->stock);
        $this->assertDatabaseMissing('stock_movements', ['id' => $movement->id]);
    }
}
