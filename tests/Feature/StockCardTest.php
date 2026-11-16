<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Permission;

class StockCardTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $permission = Permission::create([
            'name' => 'Inventory List',
            'category' => 'Inventory',
            'group_name' => 'INVENTORY',
        ]);
        $role->permissions()->attach($permission->id);

        $store = Store::create(['name' => 'Store Cabang Utama']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_user_can_view_stock_card_index_page(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-CARD-01',
            'name' => 'Produk Kartu Stok 1',
            'purchase_price' => 15000,
            'selling_price' => 25000,
            'stock' => 30,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('stock-card.index'));

        $response->assertOk();
        $response->assertSee('Buku dan Kartu Stok Produk');
        $response->assertSee('SKU-CARD-01');
        $response->assertSee('Produk Kartu Stok 1');
    }

    public function test_user_can_view_product_stock_card_with_movements_and_running_balance(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-CARD-02',
            'name' => 'Kopi Arabika 250g',
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'stock' => 15,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        // Movement 1: In 20 on 2026-10-01
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'IN-20261001-01',
            'movement_date' => '2026-10-01',
            'type' => 'in',
            'quantity' => 20,
            'purchase_price' => 50000,
            'total_price' => 1000000,
            'payment_method' => 'transfer',
            'notes' => 'Restock awal bulan',
            'store_id' => $store->id,
        ]);

        // Movement 2: Out 5 on 2026-10-03
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'INV-POS-001',
            'movement_date' => '2026-10-03',
            'type' => 'out',
            'quantity' => 5,
            'selling_price' => 75000,
            'total_price' => 375000,
            'payment_method' => 'cash',
            'notes' => 'Penjualan POS',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('inventory.stock-card', $product));

        $response->assertOk();
        $response->assertSee('Kopi Arabika 250g');
        $response->assertSee('SKU-CARD-02');
        $response->assertSee('IN-20261001-01');
        $response->assertSee('INV-POS-001');
        $response->assertSee('Restock awal bulan');
        $response->assertSee('Penjualan POS');
        $response->assertSee('Buku Kas Mutasi Stok (Ledger)');
    }

    public function test_stock_card_calculates_opening_balance_correctly_when_filtered_by_date(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-CARD-03',
            'name' => 'Gula Pasir 1kg',
            'purchase_price' => 12000,
            'selling_price' => 16000,
            'stock' => 25,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        // Sept 15: IN 50
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'IN-SEPT',
            'movement_date' => '2026-09-15',
            'type' => 'in',
            'quantity' => 50,
            'purchase_price' => 12000,
            'total_price' => 600000,
            'store_id' => $store->id,
        ]);

        // Sept 20: OUT 20 -> Ending balance at end of Sept = 30
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-SEPT',
            'movement_date' => '2026-09-20',
            'type' => 'out',
            'quantity' => 20,
            'selling_price' => 16000,
            'total_price' => 320000,
            'store_id' => $store->id,
        ]);

        // Oct 02: IN 10
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'IN-OCT',
            'movement_date' => '2026-10-02',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 12000,
            'total_price' => 120000,
            'store_id' => $store->id,
        ]);

        // Filter for October 2026
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('inventory.stock-card', [
                'product' => $product->id,
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]));

        $response->assertOk();
        // Opening balance before 2026-10-01 should be 50 - 20 = 30
        $response->assertViewHas('openingBalance', 30.0);
        // Total In in October should be 10
        $response->assertViewHas('totalIn', 10.0);
        // Ending balance should be 30 + 10 = 40
        $response->assertViewHas('endingBalance', 40.0);
    }

    public function test_stock_card_export_downloads_excel(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $product = Product::create([
            'sku' => 'SKU-CARD-EXP',
            'name' => 'Susu UHT 1L',
            'purchase_price' => 18000,
            'selling_price' => 22000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'IN-EXP-01',
            'movement_date' => '2026-10-05',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 18000,
            'total_price' => 180000,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('inventory.stock-card.export', [
                'product' => $product->id,
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-31',
            ]));

        $response->assertOk();
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition') ?? '', 'kartu-stok-SKU-CARD-EXP')
        );
    }

    public function test_user_can_filter_stock_card_index_by_category_and_variant(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $prodLiquid = Product::create([
            'sku' => 'SKU-LIQUID-60ML',
            'name' => 'Oat Drips V1',
            'category' => 'Liquid',
            'variant' => '60ml',
            'purchase_price' => 80000,
            'selling_price' => 120000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $prodDevice = Product::create([
            'sku' => 'SKU-DEVICE-POD',
            'name' => 'Oxva Xlim Pro',
            'category' => 'Device',
            'variant' => 'Pod Kit',
            'purchase_price' => 250000,
            'selling_price' => 320000,
            'stock' => 5,
            'min_stock' => 1,
            'store_id' => $store->id,
        ]);

        // Filter by category Liquid
        $responseCat = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('stock-card.index', ['category' => 'Liquid']));

        $responseCat->assertOk();
        $responseCat->assertSee('SKU-LIQUID-60ML');
        $responseCat->assertDontSee('SKU-DEVICE-POD');

        // Filter by variant Pod Kit
        $responseVar = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('stock-card.index', ['variant' => 'Pod Kit']));

        $responseVar->assertOk();
        $responseVar->assertSee('SKU-DEVICE-POD');
        $responseVar->assertDontSee('SKU-LIQUID-60ML');
    }

    public function test_user_can_filter_stock_card_detail_by_category_and_variant(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $prod1 = Product::create([
            'sku' => 'SKU-D-01',
            'name' => 'Liquid Mango Freebase',
            'category' => 'Liquid',
            'variant' => '60ml',
            'purchase_price' => 70000,
            'selling_price' => 100000,
            'stock' => 15,
            'min_stock' => 3,
            'store_id' => $store->id,
        ]);

        $prod2 = Product::create([
            'sku' => 'SKU-D-02',
            'name' => 'Aegis Legend Mod',
            'category' => 'Device',
            'variant' => 'Dual Battery',
            'purchase_price' => 450000,
            'selling_price' => 600000,
            'stock' => 4,
            'min_stock' => 1,
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('inventory.stock-card', [
                'product' => $prod1->id,
                'category' => 'Device',
            ]));

        $response->assertOk();
        $response->assertViewHas('category', 'Device');
        $allProducts = $response->viewData('allProducts');
        $this->assertTrue($allProducts->contains('id', $prod2->id));
        // Current product is always prepended to remain selectable
        $this->assertTrue($allProducts->contains('id', $prod1->id));
    }
}
