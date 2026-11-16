<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackdatedExpenseDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_backdated_expense_is_included_in_selected_date_dashboard(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create([
            'name' => 'Main Store',
            'code' => 'STR-01',
            'address' => 'Test Address',
            'phone' => '08123456789',
        ]);
        $admin = User::factory()->create();
        $admin->roles()->attach($role->id);
        $admin->stores()->attach($store->id);

        // Expense recorded on 8 October with transaction date 5 October
        $expense = Expense::create([
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'title' => 'Biaya listrik tgl 5 input tgl 8',
            'category' => 'operasional',
            'amount' => 75000,
            'expense_date' => '2026-10-05',
        ]);
        // Simulate creation timestamp on 8 October
        $expense->created_at = Carbon::parse('2026-10-08 14:00:00');
        $expense->save();

        // 1. Check dashboard for 2026-10-05
        $responseOct5 = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('dashboard', [
                'filter' => 'custom',
                'start_date' => '2026-10-05',
                'end_date' => '2026-10-05',
            ]));

        $responseOct5->assertStatus(200);
        $responseOct5->assertViewHas('monthlyExpenses', 75000.0);

        // 2. Check dashboard for 2026-10-08 (input date)
        $responseOct8 = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('dashboard', [
                'filter' => 'custom',
                'start_date' => '2026-10-08',
                'end_date' => '2026-10-08',
            ]));

        $responseOct8->assertStatus(200);
        $responseOct8->assertViewHas('monthlyExpenses', 0.0);
    }

    public function test_debt_payment_and_liability_accounts_are_excluded_from_dashboard_operating_expenses(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create([
            'name' => 'Main Store 2',
            'code' => 'STR-02',
            'address' => 'Test Address 2',
            'phone' => '08123456788',
        ]);
        $admin = User::factory()->create();
        $admin->roles()->attach($role->id);
        $admin->stores()->attach($store->id);

        $payableAccount = \App\Models\Account::create([
            'store_id' => $store->id,
            'code' => '2101',
            'name' => 'Hutang Dagang / Supplier',
            'type' => 'liability',
            'category' => 'Hutang Lancar',
            'normal_balance' => 'credit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $operationalAccount = \App\Models\Account::create([
            'store_id' => $store->id,
            'code' => '6103',
            'name' => 'Beban Listrik, Air & Internet',
            'type' => 'expense',
            'category' => 'Beban Operasional',
            'normal_balance' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);

        // 1. Debt payment expense: Rp 5.655.040
        Expense::create([
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'account_id' => $payableAccount->id,
            'title' => 'Pelunasan Hutang Supplier',
            'category' => 'Hutang Dagang / Supplier',
            'amount' => 5655040,
            'expense_date' => now()->toDateString(),
        ]);

        // 2. Real operational expense: Rp 500.000
        Expense::create([
            'store_id' => $store->id,
            'user_id' => $admin->id,
            'account_id' => $operationalAccount->id,
            'title' => 'Tagihan Listrik Toko',
            'category' => 'Beban Listrik, Air & Internet',
            'amount' => 500000,
            'expense_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('dashboard'));

        $response->assertStatus(200);
        // Only operational expense (500.000) should be included, debt payment (5.655.040) must be excluded
        $response->assertViewHas('monthlyExpenses', 500000.0);
    }
}
