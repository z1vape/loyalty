<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Store;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TopSuppliersExport;

class TopSuppliersReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Manager']);
        $p1 = Permission::create(['name' => 'Report List', 'category' => 'Reports', 'group_name' => 'REPORTS']);
        $p2 = Permission::create(['name' => 'Report SKU', 'category' => 'Reports', 'group_name' => 'REPORTS']);

        $role->permissions()->attach([$p1->id, $p2->id]);

        $this->store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 1',
            'code' => 'VOL-01',
            'initial_balance' => 0,
        ]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($role->id);
        $this->user->stores()->attach($this->store->id);
    }

    public function test_reports_index_redirects_to_by_sku(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.index'));

        $response->assertRedirect(route('reports.by-sku'));
    }

    public function test_top_suppliers_page_renders_12_month_matrix_table_with_all_registered_suppliers(): void
    {
        $product = Product::create([
            'sku' => 'PRD-01',
            'name' => 'Pod System X',
            'category' => 'Device',
            'purchase_price' => 50000,
            'selling_price' => 100000,
            'stock' => 10,
            'store_id' => $this->store->id,
        ]);

        // Register a supplier via Setting that has 0 purchases
        Setting::create([
            'type' => 'supplier',
            'value' => 'Supplier Terdaftar Tanpa Belanja',
        ]);

        // Supplier 1 in October 2026
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'reference_number' => 'REF-IN-OCT-01',
            'movement_date' => '2026-10-10',
            'type' => 'in',
            'quantity' => 20,
            'purchase_price' => 50000,
            'total_price' => 65120000,
            'supplier_name' => 'Mr Vape Indo - Tanggerang',
            'store_id' => $this->store->id,
        ]);

        // Supplier 2 in October 2026
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'reference_number' => 'REF-IN-OCT-02',
            'movement_date' => '2026-10-15',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 60000,
            'total_price' => 41942979,
            'supplier_name' => 'Vape Break (VB) - Jakarta',
            'store_id' => $this->store->id,
        ]);

        // Supplier 3 in October 2026
        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'reference_number' => 'REF-IN-OCT-03',
            'movement_date' => '2026-10-20',
            'type' => 'in',
            'quantity' => 5,
            'purchase_price' => 100000,
            'total_price' => 6770000,
            'supplier_name' => 'VAPEZOO',
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.top-suppliers', [
                'mode' => 'month',
                'year' => 2026,
            ]));

        $response->assertOk();
        $response->assertSee('Dashboard Utama');
        $response->assertSee('Rekap Belanja Supplier');
        $response->assertSee('Mr Vape Indo - Tanggerang');
        $response->assertSee('Vape Break (VB) - Jakarta');
        $response->assertSee('VAPEZOO');
        // Registered supplier without purchases should NOT be rendered
        $response->assertDontSee('Supplier Terdaftar Tanpa Belanja');
        $response->assertSee('January');
        $response->assertSee('Febuary');
        $response->assertSee('October');
        $response->assertSee('December');
        $response->assertSee('65.120.000');
        $response->assertSee('41.942.979');
        $response->assertSee('6.770.000');
        $response->assertSee('TOTAL PEMBELIAN');
        $response->assertSee('113.832.979'); // Total October
    }

    public function test_top_suppliers_page_renders_multi_year_matrix_table_with_all_suppliers(): void
    {
        $product = Product::create([
            'sku' => 'PRD-02',
            'name' => 'Coil Master Kit',
            'category' => 'Accessories',
            'purchase_price' => 50000,
            'selling_price' => 80000,
            'stock' => 10,
            'store_id' => $this->store->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'reference_number' => 'REF-IN-2025',
            'movement_date' => '2025-06-15',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 50000,
            'total_price' => 5000000,
            'supplier_name' => 'Mr Vape Indo - Tanggerang',
            'store_id' => $this->store->id,
        ]);

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'reference_number' => 'REF-IN-2026',
            'movement_date' => '2026-08-20',
            'type' => 'in',
            'quantity' => 20,
            'purchase_price' => 50000,
            'total_price' => 10000000,
            'supplier_name' => 'Mr Vape Indo - Tanggerang',
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.top-suppliers', [
                'mode' => 'year',
                'start_year' => 2025,
                'end_year' => 2027,
            ]));

        $response->assertOk();
        $response->assertSee('Per Tahun');
        $response->assertSee('Mr Vape Indo - Tanggerang');
        $response->assertSee('2025');
        $response->assertSee('2026');
        $response->assertSee('2027');
        $response->assertSee('5.000.000');
        $response->assertSee('10.000.000');
        $response->assertSee('TOTAL PEMBELIAN');
    }

    public function test_supplier_report_isolates_data_per_store(): void
    {
        $store2 = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'code' => 'VOL-02',
            'initial_balance' => 0,
        ]);
        $this->user->stores()->attach($store2->id);

        $product1 = Product::create([
            'sku' => 'PRD-STORE1',
            'name' => 'Product Store 1',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 10,
            'store_id' => $this->store->id,
        ]);

        $product2 = Product::create([
            'sku' => 'PRD-STORE2',
            'name' => 'Product Store 2',
            'purchase_price' => 10000,
            'selling_price' => 20000,
            'stock' => 10,
            'store_id' => $store2->id,
        ]);

        // Purchase in Store 1
        StockMovement::create([
            'product_id' => $product1->id,
            'user_id' => $this->user->id,
            'reference_number' => 'REF-S1',
            'movement_date' => '2026-05-01',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 10000,
            'total_price' => 1500000,
            'supplier_name' => 'Supplier Khusus Store 1',
            'store_id' => $this->store->id,
        ]);

        // Purchase in Store 2
        StockMovement::create([
            'product_id' => $product2->id,
            'user_id' => $this->user->id,
            'reference_number' => 'REF-S2',
            'movement_date' => '2026-05-01',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 10000,
            'total_price' => 8800000,
            'supplier_name' => 'Supplier Khusus Store 2',
            'store_id' => $store2->id,
        ]);

        // When viewing as Store 1
        $resStore1 = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.top-suppliers', ['mode' => 'month', 'year' => 2026]));

        $resStore1->assertOk();
        $resStore1->assertSee('Z1 VAPE STORE VOL 1');
        $resStore1->assertSee('1.500.000');
        // Total purchases in Store 1 should be 1.500.000, NOT 8.800.000 or 10.300.000
        $resStore1->assertDontSee('8.800.000');

        // When viewing as Store 2
        $resStore2 = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $store2->id])
            ->get(route('reports.top-suppliers', ['mode' => 'month', 'year' => 2026]));

        $resStore2->assertOk();
        $resStore2->assertSee('Z1 VAPE STORE VOL 2');
        $resStore2->assertSee('8.800.000');
        $resStore2->assertDontSee('1.500.000');
    }

    public function test_export_top_suppliers_monthly_and_yearly(): void
    {
        Excel::fake();

        // Monthly export
        $responseMonthly = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.top-suppliers.export', [
                'mode' => 'month',
                'year' => 2026,
            ]));

        $responseMonthly->assertOk();
        Excel::assertDownloaded('rekap-top-10-supplier-tahun-2026.xlsx', function (TopSuppliersExport $export) {
            return true;
        });

        // Yearly export
        $responseYearly = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.top-suppliers.export', [
                'mode' => 'year',
                'start_year' => 2025,
                'end_year' => 2027,
            ]));

        $responseYearly->assertOk();
        Excel::assertDownloaded('rekap-top-10-supplier-tahun-2025-2027.xlsx', function (TopSuppliersExport $export) {
            return true;
        });
    }
}
