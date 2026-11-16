<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Store;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LowStockReportExport;

class LowStockReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Manager']);
        $p1 = Permission::create(['name' => 'Report List', 'category' => 'Reports', 'group_name' => 'REPORTS']);
        $p2 = Permission::create(['name' => 'Inventory List', 'category' => 'Inventory', 'group_name' => 'INVENTORY']);

        $role->permissions()->attach([$p1->id, $p2->id]);

        $this->store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'code' => 'VOL-02',
            'initial_balance' => 0,
        ]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($role->id);
        $this->user->stores()->attach($this->store->id);
    }

    public function test_low_stock_page_renders_blade_view_successfully(): void
    {
        Product::create([
            'store_id' => $this->store->id,
            'sku' => 'DEVICE-01',
            'name' => 'OXVA XLIM PRO 2',
            'category' => 'Device',
            'stock' => 0,
            'min_stock' => 10,
            'purchase_price' => 415000,
            'selling_price' => 500000,
        ]);

        Product::create([
            'store_id' => $this->store->id,
            'sku' => 'COIL-01',
            'name' => 'CT OXVA XLIM 0.8',
            'category' => 'OCC/Coil',
            'stock' => 2,
            'min_stock' => 10,
            'purchase_price' => 32000,
            'selling_price' => 45000,
        ]);

        // Product with healthy stock
        Product::create([
            'store_id' => $this->store->id,
            'sku' => 'HEALTHY-01',
            'name' => 'Cotton Bacon Prime',
            'category' => 'Accessories',
            'stock' => 50,
            'min_stock' => 10,
            'purchase_price' => 25000,
            'selling_price' => 40000,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.low-stock'));

        $response->assertOk();
        // Ensure it renders HTML blade view, NOT raw JSON!
        $response->assertViewIs('reports.low_stock');
        $response->assertSee('Laporan Stok Kritis');
        $response->assertSee('OXVA XLIM PRO 2');
        $response->assertSee('CT OXVA XLIM 0.8');
        $response->assertDontSee('Cotton Bacon Prime');
        $response->assertSee('Habis (0 Pcs)');
        $response->assertSee('Menipis');
        $response->assertSee('Z1 VAPE STORE VOL 2');
    }

    public function test_low_stock_api_json_response_when_requested_as_json(): void
    {
        Product::create([
            'store_id' => $this->store->id,
            'sku' => 'DEVICE-01',
            'name' => 'OXVA XLIM PRO 2',
            'category' => 'Device',
            'stock' => 0,
            'min_stock' => 10,
            'purchase_price' => 415000,
            'selling_price' => 500000,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->getJson(route('reports.low-stock'));

        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'count',
            'total_shortage',
            'estimated_restock_cost',
        ]);
        $this->assertSame(1, $response->json('count'));
    }

    public function test_low_stock_export_excel_downloads(): void
    {
        Excel::fake();

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('reports.low-stock.export'));

        $response->assertOk();
        Excel::assertDownloaded('laporan-stok-kritis-' . date('Y-m-d') . '.xlsx', function (LowStockReportExport $export) {
            return true;
        });
    }
}

