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

class ReportSkuExcelExportTest extends TestCase
{
    use RefreshDatabase;

    private function createAuthorizedUser(string $permissionName = 'Report SKU'): array
    {
        $role = Role::create(['name' => 'Store Manager']);
        $permission = Permission::create([
            'name' => $permissionName,
            'category' => 'Reports',
            'group_name' => 'REPORTS',
        ]);
        $role->permissions()->attach($permission->id);

        $store = Store::create(['name' => 'Toko Cabang Test']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_unauthenticated_user_redirected_from_sku_report_and_export(): void
    {
        $this->get(route('reports.by-sku'))->assertRedirect(route('login'));
        $this->get(route('reports.by-sku.export'))->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_sku_report_export(): void
    {
        $role = Role::create(['name' => 'Cashier']);
        $store = Store::create(['name' => 'Toko Cabang Test']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('reports.by-sku.export'));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_sku_report_page(): void
    {
        [$user, $store] = $this->createAuthorizedUser('Report SKU');

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('reports.by-sku'));

        $response->assertOk();
        $response->assertSee('Penjualan berdasarkan SKU');
        $response->assertSee('Export Excel');
        $response->assertSee('Kategori');
        $response->assertSee('Cari Produk / SKU');
    }

    public function test_sku_report_export_downloads_excel_successfully(): void
    {
        [$user, $store] = $this->createAuthorizedUser('Report SKU');

        $product = Product::create([
            'sku' => 'TEST-SKU-001',
            'name' => 'Liquid Saltnic 30ml',
            'category' => 'Liquid',
            'variant' => 'Mango Ice',
            'purchase_price' => 70000,
            'selling_price' => 120000,
            'stock' => 50,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-TEST-001',
            'movement_date' => '2026-10-10',
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 70000,
            'selling_price' => 120000,
            'discount' => 10000,
            'total_price' => 230000,
            'status' => 'completed',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('reports.by-sku.export', [
                'start_date' => '2026-10-01',
                'end_date' => '2026-10-31',
            ]));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition') ?? '';
        $this->assertStringContainsString('penjualan-sku-2026-10-01-sd-2026-10-31.xlsx', $disposition);
    }

    public function test_sku_report_export_adjusts_based_on_category_filter(): void
    {
        [$user, $store] = $this->createAuthorizedUser('Report List');

        $prodLiquid = Product::create([
            'sku' => 'SKU-LIQ-01',
            'name' => 'Liquid Freebase 60ml',
            'category' => 'Liquid',
            'purchase_price' => 80000,
            'selling_price' => 140000,
            'stock' => 20,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        $prodDevice = Product::create([
            'sku' => 'SKU-DEV-01',
            'name' => 'Pod Kit V2',
            'category' => 'Device',
            'purchase_price' => 200000,
            'selling_price' => 300000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $prodLiquid->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-LIQ-01',
            'movement_date' => '2026-10-12',
            'type' => 'out',
            'quantity' => 5,
            'purchase_price' => 80000,
            'selling_price' => 140000,
            'total_price' => 700000,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $prodDevice->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-DEV-01',
            'movement_date' => '2026-10-12',
            'type' => 'out',
            'quantity' => 1,
            'purchase_price' => 200000,
            'selling_price' => 300000,
            'total_price' => 300000,
            'store_id' => $store->id,
        ]);

        // Filter by category: Liquid only
        $reportController = app(\App\Http\Controllers\ReportController::class);
        $request = new \Illuminate\Http\Request([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'category' => 'Liquid',
        ]);

        session(['active_store_id' => $store->id]);
        $data = $reportController->getSalesBySkuData($request);

        $this->assertEquals(1, $data['totalProducts']);
        $this->assertEquals('SKU-LIQ-01', $data['items'][0]['sku']);
        $this->assertEquals(5, $data['totalQty']);
        $this->assertEquals(700000, $data['grandTotal']);
    }

    public function test_sku_report_export_adjusts_based_on_search_filter(): void
    {
        [$user, $store] = $this->createAuthorizedUser('Report SKU');

        $prodA = Product::create([
            'sku' => 'COIL-01',
            'name' => 'Coil 0.6 Ohm Mesh',
            'category' => 'Accessories',
            'purchase_price' => 25000,
            'selling_price' => 45000,
            'stock' => 50,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        $prodB = Product::create([
            'sku' => 'COTTON-01',
            'name' => 'Cotton Bacon Prime',
            'category' => 'Accessories',
            'purchase_price' => 30000,
            'selling_price' => 55000,
            'stock' => 30,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $prodA->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-A',
            'movement_date' => '2026-10-12',
            'type' => 'out',
            'quantity' => 4,
            'purchase_price' => 25000,
            'selling_price' => 45000,
            'total_price' => 180000,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $prodB->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-B',
            'movement_date' => '2026-10-12',
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 30000,
            'selling_price' => 55000,
            'total_price' => 110000,
            'store_id' => $store->id,
        ]);

        // Search for 'Coil'
        $reportController = app(\App\Http\Controllers\ReportController::class);
        $request = new \Illuminate\Http\Request([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
            'search' => 'Coil',
        ]);

        session(['active_store_id' => $store->id]);
        $data = $reportController->getSalesBySkuData($request);

        $this->assertEquals(1, $data['totalProducts']);
        $this->assertEquals('COIL-01', $data['items'][0]['sku']);
        $this->assertEquals(4, $data['totalQty']);
        $this->assertEquals(180000, $data['grandTotal']);
    }

    public function test_sku_report_export_excludes_cancelled_movements(): void
    {
        [$user, $store] = $this->createAuthorizedUser('Report SKU');

        $product = Product::create([
            'sku' => 'SKU-CANCEL-01',
            'name' => 'Produk Tes Batal',
            'category' => 'Test',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 10,
            'min_stock' => 2,
            'store_id' => $store->id,
        ]);

        // Active movement
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-ACT-01',
            'movement_date' => '2026-10-12',
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'total_price' => 40000,
            'status' => 'completed',
            'store_id' => $store->id,
        ]);

        // Cancelled movement
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'OUT-CNC-01',
            'movement_date' => '2026-10-12',
            'type' => 'out',
            'quantity' => 5,
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'total_price' => 100000,
            'status' => 'cancelled',
            'store_id' => $store->id,
        ]);

        $reportController = app(\App\Http\Controllers\ReportController::class);
        $request = new \Illuminate\Http\Request([
            'start_date' => '2026-10-01',
            'end_date' => '2026-10-31',
        ]);

        session(['active_store_id' => $store->id]);
        $data = $reportController->getSalesBySkuData($request);

        $this->assertEquals(2, $data['totalQty']);
        $this->assertEquals(40000, $data['grandTotal']);
    }

    public function test_sku_report_export_detail_downloads_excel_successfully(): void
    {
        [$user, $store] = $this->createAuthorizedUser('Report SKU');

        $product = Product::create([
            'sku' => 'SKU-CT-FOOM',
            'name' => 'CT FOOM',
            'category' => 'Cartridge',
            'variant' => '0.8 Ohm',
            'purchase_price' => 25000,
            'selling_price' => 40000,
            'stock' => 50,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'SLS-20261019-001',
            'movement_date' => '2026-10-19',
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 25000,
            'selling_price' => 40000,
            'discount' => 0,
            'total_price' => 80000,
            'status' => 'completed',
            'store_id' => $store->id,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('reports.by-sku.export-detail', [
                'start_date' => '2026-10-19',
                'end_date' => '2026-10-19',
            ]));

        $response->assertOk();
        $disposition = $response->headers->get('content-disposition') ?? '';
        $this->assertStringContainsString('penjualan-detail-produk-2026-10-19-sd-2026-10-19.xlsx', $disposition);
    }

    public function test_sku_report_export_detail_breaks_down_quantity_into_individual_rows(): void
    {
        [$user, $store] = $this->createAuthorizedUser('Report SKU');

        $product = Product::create([
            'sku' => 'SKU-CT-FOOM',
            'name' => 'CT FOOM',
            'category' => 'Cartridge',
            'variant' => '0.8 Ohm',
            'purchase_price' => 25000,
            'selling_price' => 40000,
            'stock' => 50,
            'min_stock' => 5,
            'store_id' => $store->id,
        ]);

        // Sale of 2 qty CT FOOM on 19 October 2026
        $movement = StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TRX-20261019-0001',
            'movement_date' => '2026-10-19',
            'type' => 'out',
            'quantity' => 2,
            'purchase_price' => 25000,
            'selling_price' => 40000,
            'discount' => 10000, // 5.000 per unit discount
            'total_price' => 70000,
            'payment_method' => 'qris',
            'status' => 'completed',
            'store_id' => $store->id,
        ]);

        $export = new \App\Exports\ProductSalesDetailExport(
            collect([$movement->load(['product', 'user'])]),
            [
                'startDate' => '2026-10-19',
                'endDate' => '2026-10-19',
            ],
            $store->name
        );

        $rows = $export->array();

        // 2 individual breakdown rows + 1 TOTAL row = 3 rows
        $this->assertCount(3, $rows);

        // Row 1: First CT FOOM unit
        $this->assertEquals(1, $rows[0][0]); // No
        $this->assertEquals('19/10/2026', $rows[0][1]); // Tanggal
        $this->assertEquals('TRX-20261019-0001', $rows[0][3]); // No. Ref
        $this->assertEquals('SKU-CT-FOOM', $rows[0][4]); // SKU
        $this->assertEquals('CT FOOM', $rows[0][5]); // Nama
        $this->assertEquals(1, $rows[0][8]); // Qty (broken down to 1)
        $this->assertEquals(25000, $rows[0][9]); // Purchase price
        $this->assertEquals(40000, $rows[0][10]); // Selling price
        $this->assertEquals(5000, $rows[0][11]); // Diskon per unit
        $this->assertEquals(35000, $rows[0][12]); // Total penjualan unit
        $this->assertEquals(10000, $rows[0][13]); // Profit (35.000 - 25.000)

        // Row 2: Second CT FOOM unit
        $this->assertEquals(2, $rows[1][0]); // No
        $this->assertEquals('19/10/2026', $rows[1][1]);
        $this->assertEquals('TRX-20261019-0001', $rows[1][3]);
        $this->assertEquals('SKU-CT-FOOM', $rows[1][4]);
        $this->assertEquals('CT FOOM', $rows[1][5]);
        $this->assertEquals(1, $rows[1][8]); // Qty (broken down to 1)
        $this->assertEquals(25000, $rows[1][9]);
        $this->assertEquals(40000, $rows[1][10]);
        $this->assertEquals(5000, $rows[1][11]);
        $this->assertEquals(35000, $rows[1][12]);
        $this->assertEquals(10000, $rows[1][13]);

        // Row 3: TOTAL row
        $this->assertEquals('TOTAL', $rows[2][0]);
        $this->assertEquals(2, $rows[2][8]); // Total Qty = 2
        $this->assertEquals(50000, $rows[2][9]); // Total purchase = 50.000
        $this->assertEquals(80000, $rows[2][10]); // Total selling = 80.000
        $this->assertEquals(10000, $rows[2][11]); // Total discount = 10.000
        $this->assertEquals(70000, $rows[2][12]); // Total sales = 70.000
        $this->assertEquals(20000, $rows[2][13]); // Total profit = 20.000
    }

    public function test_unauthorized_user_cannot_access_sku_report_export_detail(): void
    {
        $role = Role::create(['name' => 'Cashier']);
        $store = Store::create(['name' => 'Toko Cabang Test']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('reports.by-sku.export-detail'));

        $response->assertStatus(403);
    }
}

