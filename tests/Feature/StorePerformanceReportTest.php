<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Store $store1;
    protected Store $store2;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::create(['name' => 'Super Admin']);
        $this->adminUser = User::factory()->create(['name' => 'Owner Admin']);
        $this->adminUser->roles()->attach($role->id);

        $this->store1 = Store::create([
            'name' => 'Z1 VAPE STORE VOL 1',
            'code' => 'VOL-01',
            'bank_name' => 'BCA',
            'bank_account_number' => '0411685917',
            'initial_balance' => 5000000,
        ]);

        $this->store2 = Store::create([
            'name' => 'Z1 VAPE STORE BELITUNG',
            'code' => 'BELITUNG',
            'bank_name' => 'BCA',
            'bank_account_number' => '4660122406',
            'initial_balance' => 3000000,
        ]);

        $this->adminUser->stores()->attach([$this->store1->id, $this->store2->id]);
    }

    public function test_store_performance_screen_renders_successfully(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_store_id' => $this->store1->id])
            ->get(route('reports.store-performance'));

        $response->assertStatus(200);
        $response->assertSee('Performa Seluruh Cabang Toko');
        $response->assertSee('Omzet Jaringan');
        $response->assertSee('Z1 VAPE STORE VOL 1');
        $response->assertSee('Z1 VAPE STORE BELITUNG');
        $response->assertViewHas('storesData');
        $response->assertViewHas('totals');
        $response->assertViewHas('dailySeries');
    }

    public function test_store_performance_calculates_sales_and_rankings_accurately(): void
    {
        // Store 1: 2 sales = 200,000
        Sale::create([
            'reference_number' => 'POS-VOL1-01',
            'sale_date' => Carbon::now()->toDateString(),
            'total_amount' => 150000,
            'payment_method' => 'cash',
            'store_id' => $this->store1->id,
            'user_id' => $this->adminUser->id,
        ]);
        Sale::create([
            'reference_number' => 'POS-VOL1-02',
            'sale_date' => Carbon::now()->toDateString(),
            'total_amount' => 50000,
            'payment_method' => 'transfer',
            'store_id' => $this->store1->id,
            'user_id' => $this->adminUser->id,
        ]);

        // Store 2: 1 sale = 500,000 (Store 2 should rank #1)
        Sale::create([
            'reference_number' => 'POS-BEL-01',
            'sale_date' => Carbon::now()->toDateString(),
            'total_amount' => 500000,
            'payment_method' => 'qris',
            'store_id' => $this->store2->id,
            'user_id' => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_store_id' => $this->store1->id])
            ->get(route('reports.store-performance'));

        $response->assertStatus(200);
        $storesData = $response->viewData('storesData');
        $totals = $response->viewData('totals');

        // Total chain omzet: 200,000 + 500,000 = 700,000
        $this->assertEquals(700000, $totals['total_sales']);
        $this->assertEquals(3, $totals['trx_count']);

        // Store 2 ranks #1 (500,000)
        $this->assertEquals(1, $storesData[$this->store2->id]['rank']);
        $this->assertEquals(500000, $storesData[$this->store2->id]['total_sales']);

        // Store 1 ranks #2 (200,000)
        $this->assertEquals(2, $storesData[$this->store1->id]['rank']);
        $this->assertEquals(200000, $storesData[$this->store1->id]['total_sales']);
    }

    public function test_store_performance_export_generates_excel(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_store_id' => $this->store1->id])
            ->get(route('reports.store-performance.export'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition') ?? '', '.xlsx') ||
            str_contains($response->headers->get('content-type') ?? '', 'spreadsheet')
        );
    }

    public function test_unauthorized_user_is_forbidden(): void
    {
        $guestUser = User::factory()->create();
        $guestUser->stores()->attach($this->store1->id);

        $response = $this->actingAs($guestUser)
            ->withSession(['active_store_id' => $this->store1->id])
            ->get(route('reports.store-performance'));

        $response->assertStatus(403);
    }
}
