<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseStoreBalanceAndPriveTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        $this->store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'initial_balance' => 10000000,
            'initial_balance_date' => '2026-11-01',
        ]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($superAdminRole->id);
        $this->user->stores()->attach($this->store->id);

        app(AccountingService::class)->ensureStoreAccounts($this->store->id);
    }

    public function test_prive_expense_reduces_store_ending_balance(): void
    {
        $initialEndingBalance = $this->store->ending_balance;
        $this->assertEquals(10000000, $initialEndingBalance);

        $priveAccount = Account::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('code', '3102')
            ->first();

        $this->assertNotNull($priveAccount);
        $this->assertEquals('equity', $priveAccount->type);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('expenses.store'), [
                'expense_date' => '2026-11-09',
                'account_id' => $priveAccount->id,
                'title' => 'Tarik Prive Pemilik',
                'amount' => 1000000,
                'payment_method' => 'transfer',
                'notes' => 'Bagi hasil pemilik',
            ]);

        $response->assertRedirect(route('expenses.index'));

        $expense = Expense::withoutGlobalScopes()->where('title', 'Tarik Prive Pemilik')->first();
        $this->assertNotNull($expense);
        $this->assertEquals($this->store->id, $expense->store_id);
        $this->assertEquals(1000000, $expense->amount);

        // Ending balance of store must decrease by 1,000,000 (10,000,000 - 1,000,000 = 9,000,000)
        $this->store->refresh();
        $this->assertEquals(9000000, $this->store->ending_balance);
    }

    public function test_expense_created_without_active_store_session_infers_from_account(): void
    {
        $priveAccount = Account::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('code', '3102')
            ->first();

        // Post without active_store_id in session
        $response = $this->withoutMiddleware(\App\Http\Middleware\CheckActiveStore::class)
            ->actingAs($this->user)
            ->post(route('expenses.store'), [
                'expense_date' => '2026-11-09',
                'account_id' => $priveAccount->id,
                'title' => 'Bagi Hasil Manual',
                'amount' => 1000000,
                'payment_method' => 'transfer',
            ]);

        $response->assertRedirect(route('expenses.index'));

        $expense = Expense::withoutGlobalScopes()->where('title', 'Bagi Hasil Manual')->first();
        $this->assertNotNull($expense);
        $this->assertEquals($this->store->id, $expense->store_id);

        $this->store->refresh();
        $this->assertEquals(9000000, $this->store->ending_balance);
    }
}
