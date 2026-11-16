<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\Sale;
use Carbon\Carbon;

class SaleDateFilterTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create(['name' => 'Admin User']);
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_sales_history_filters_accurately_by_exact_single_day(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        // Transaction on 2026-10-07
        $saleOct7 = Sale::create([
            'reference_number' => 'POS-261007-0001',
            'sale_date' => '2026-10-07',
            'customer_name' => 'Customer Oct 7',
            'total_amount' => 50000,
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
            'created_at' => Carbon::parse('2026-10-08 14:00:00'), // Backdated input on Oct 8
        ]);

        // Transaction on 2026-10-08
        $saleOct8 = Sale::create([
            'reference_number' => 'POS-261008-0002',
            'sale_date' => '2026-10-08',
            'customer_name' => 'Customer Oct 8',
            'total_amount' => 75000,
            'payment_method' => 'cash',
            'user_id' => $admin->id,
            'store_id' => $store->id,
            'created_at' => Carbon::parse('2026-10-08 10:00:00'),
        ]);

        // Filter for exactly 2026-10-07
        $responseOct7 = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index', [
                'start_date' => '2026-10-07',
                'end_date' => '2026-10-07',
            ]));

        $responseOct7->assertStatus(200);
        $responseOct7->assertSee('POS-261007-0001');
        $responseOct7->assertSee('07 Oct 2026');
        $responseOct7->assertDontSee('POS-261008-0002');

        // Filter for exactly 2026-10-08
        $responseOct8 = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index', [
                'start_date' => '2026-10-08',
                'end_date' => '2026-10-08',
            ]));

        $responseOct8->assertStatus(200);
        $responseOct8->assertSee('POS-261008-0002');
        $responseOct8->assertDontSee('POS-261007-0001');
    }

    public function test_sales_history_filters_by_date_range(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        Sale::create([
            'reference_number' => 'POS-261001-0001',
            'sale_date' => '2026-10-01',
            'total_amount' => 10000,
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        Sale::create([
            'reference_number' => 'POS-261005-0002',
            'sale_date' => '2026-10-05',
            'total_amount' => 20000,
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        Sale::create([
            'reference_number' => 'POS-261010-0003',
            'sale_date' => '2026-10-10',
            'total_amount' => 30000,
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        // Range Oct 3 to Oct 8 should only include POS-261005-0002
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index', [
                'start_date' => '2026-10-03',
                'end_date' => '2026-10-08',
            ]));

        $response->assertStatus(200);
        $response->assertSee('POS-261005-0002');
        $response->assertDontSee('POS-261001-0001');
        $response->assertDontSee('POS-261010-0003');
    }

    public function test_sales_history_filters_with_swapped_start_and_end_dates(): void
    {
        [$admin, $store] = $this->createAdminWithStore();

        Sale::create([
            'reference_number' => 'POS-261005-9999',
            'sale_date' => '2026-10-05',
            'total_amount' => 25000,
            'user_id' => $admin->id,
            'store_id' => $store->id,
        ]);

        // User enters start_date=2026-10-08 and end_date=2026-10-01 (reversed)
        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('sales.index', [
                'start_date' => '2026-10-08',
                'end_date' => '2026-10-01',
            ]));

        $response->assertStatus(200);
        $response->assertSee('POS-261005-9999');
    }
}
