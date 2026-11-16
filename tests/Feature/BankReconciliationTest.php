<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Store;
use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected Account $bankAccount;
    protected Account $expenseAccount;
    protected Account $incomeAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);

        $this->store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'code' => 'VOL-02',
            'bank_name' => 'BCA',
            'bank_account_number' => '8820192831',
            'bank_account_name' => 'Z1 STORE',
            'initial_balance' => 0,
        ]);

        $this->user = User::factory()->create();
        $this->user->roles()->attach($superAdminRole->id);
        $this->user->stores()->attach($this->store->id);

        app(AccountingService::class)->ensureStoreAccounts($this->store->id);

        $this->bankAccount = Account::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('code', '1102')
            ->firstOrFail();

        $this->expenseAccount = Account::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('code', '6110')
            ->firstOrFail();

        $this->incomeAccount = Account::withoutGlobalScopes()
            ->where('store_id', $this->store->id)
            ->where('code', '4201')
            ->firstOrFail();
    }

    public function test_reconciliation_index_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.reconciliations.index'));

        $response->assertStatus(200);
        $response->assertSee('Rekonsiliasi Bank');
        $response->assertSee('Rekening Bank Toko');
        $response->assertSee('BCA');
    }

    public function test_reconciliation_create_renders_with_accounts(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.reconciliations.create'));

        $response->assertStatus(200);
        $response->assertSee('Pencocokan');
        $response->assertSee($this->bankAccount->name);
        $response->assertSee('Beban Selisih Kas & Rekonsiliasi Bank');
        $response->assertSee('Pendapatan Selisih Kas & Rekonsiliasi Bank');
        $response->assertSee('Beban Administrasi Bank & Biaya Layanan');
        $response->assertSee('Penyesuaian Saldo Awal & Koreksi Saldo Toko');
    }

    public function test_reconciliation_with_surplus_adjusts_bank_balance_and_creates_journal(): void
    {
        // System initial balance is 0
        $this->assertEquals(0, $this->bankAccount->calculateBalance());

        // Reconcile to 5,000,000 (ATM has 5,000,000, difference is +5,000,000)
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('accounting.reconciliations.store'), [
                'account_id' => $this->bankAccount->id,
                'reconciliation_date' => now()->toDateString(),
                'actual_balance' => 5000000,
                'adjustment_account_id' => $this->incomeAccount->id,
                'notes' => 'Surplus pencocokan ATM',
            ]);

        $response->assertRedirect(route('accounting.reconciliations.index'));
        $response->assertSessionHas('success');

        $recon = BankReconciliation::latest('id')->first();
        $this->assertNotNull($recon);
        $this->assertEquals(0, (float) $recon->system_balance);
        $this->assertEquals(5000000, (float) $recon->actual_balance);
        $this->assertEquals(5000000, (float) $recon->difference);
        $this->assertEquals('adjusted', $recon->status);

        // Verify Journal Entry was created and is balanced
        $journal = JournalEntry::where('source_type', 'bank_reconciliation')
            ->where('source_id', $recon->id)
            ->first();
        $this->assertNotNull($journal);
        $this->assertEquals(5000000, (float) $journal->total_debit);
        $this->assertEquals(5000000, (float) $journal->total_credit);

        // Bank Account calculateBalance is now exactly 5,000,000
        $this->assertEquals(5000000, $this->bankAccount->calculateBalance());
    }

    public function test_reconciliation_with_deficit_adjusts_bank_balance_and_creates_journal(): void
    {
        // First set bank balance to 10,000,000
        $recon1 = BankReconciliation::create([
            'store_id' => $this->store->id,
            'reconciliation_number' => 'RECON-INIT',
            'account_id' => $this->bankAccount->id,
            'reconciliation_date' => now()->toDateString(),
            'system_balance' => 0,
            'actual_balance' => 10000000,
            'difference' => 10000000,
            'adjustment_account_id' => $this->incomeAccount->id,
            'status' => 'adjusted',
        ]);
        app(AccountingService::class)->recordBankReconciliationJournal($recon1);
        $this->assertEquals(10000000, $this->bankAccount->calculateBalance());

        // Now ATM only has 8,000,000 (e.g. 2,000,000 was spent/admin fees)
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('accounting.reconciliations.store'), [
                'account_id' => $this->bankAccount->id,
                'reconciliation_date' => now()->toDateString(),
                'actual_balance' => 8000000,
                'adjustment_account_id' => $this->expenseAccount->id,
                'notes' => 'Potongan admin bank',
            ]);

        $response->assertRedirect(route('accounting.reconciliations.index'));

        // Difference is -2,000,000
        $recon2 = BankReconciliation::latest('id')->first();
        $this->assertEquals(-2000000, (float) $recon2->difference);

        // Bank balance in system is now 8,000,000 (matches ATM)
        $this->assertEquals(8000000, $this->bankAccount->calculateBalance());
    }

    public function test_reconciliation_with_zero_difference_records_balanced_status_without_journal(): void
    {
        $this->assertEquals(0, $this->bankAccount->calculateBalance());

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('accounting.reconciliations.store'), [
                'account_id' => $this->bankAccount->id,
                'reconciliation_date' => now()->toDateString(),
                'actual_balance' => 0,
                'notes' => 'Saldo sudah pas',
            ]);

        $response->assertRedirect(route('accounting.reconciliations.index'));

        $recon = BankReconciliation::latest('id')->first();
        $this->assertEquals('balanced', $recon->status);
        $this->assertEquals(0, (float) $recon->difference);

        // No journal should be generated
        $journal = JournalEntry::where('source_type', 'bank_reconciliation')
            ->where('source_id', $recon->id)
            ->first();
        $this->assertNull($journal);
    }

    public function test_destroy_reconciliation_removes_record_and_purges_journal(): void
    {
        $recon = BankReconciliation::create([
            'store_id' => $this->store->id,
            'reconciliation_number' => 'RECON-DEL',
            'account_id' => $this->bankAccount->id,
            'reconciliation_date' => now()->toDateString(),
            'system_balance' => 0,
            'actual_balance' => 2000000,
            'difference' => 2000000,
            'adjustment_account_id' => $this->incomeAccount->id,
            'status' => 'adjusted',
        ]);
        app(AccountingService::class)->recordBankReconciliationJournal($recon);

        $this->assertEquals(2000000, $this->bankAccount->calculateBalance());

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->delete(route('accounting.reconciliations.destroy', $recon->id));

        $response->assertRedirect(route('accounting.reconciliations.index'));

        $this->assertDatabaseMissing('bank_reconciliations', ['id' => $recon->id]);
        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => 'bank_reconciliation',
            'source_id' => $recon->id,
        ]);

        // Balance restored back to 0
        $this->assertEquals(0, $this->bankAccount->calculateBalance());
    }

    public function test_store_ending_balance_reflects_reconciliation_difference(): void
    {
        $this->store->update(['initial_balance' => 10293250]);
        $this->assertEquals(10293250, $this->store->fresh()->ending_balance);

        // Record a deficit reconciliation of -261,280 (like in user screenshot)
        BankReconciliation::create([
            'store_id' => $this->store->id,
            'reconciliation_number' => 'RECON-STORE-DIFF',
            'account_id' => $this->bankAccount->id,
            'reconciliation_date' => now()->toDateString(),
            'system_balance' => 10293250,
            'actual_balance' => 10031970,
            'difference' => -261280,
            'adjustment_account_id' => $this->expenseAccount->id,
            'status' => 'adjusted',
        ]);

        // Ending balance on store should now exactly be 10,031,970
        $this->assertEquals(10031970, $this->store->fresh()->ending_balance);
    }

    public function test_reconciliation_index_and_create_use_store_ending_balance_for_bank_account(): void
    {
        $this->store->update([
            'initial_balance' => 3000000,
            'initial_balance_date' => now()->startOfMonth()->toDateString(),
            'auto_cash_to_bank' => true,
        ]);

        $sale = \App\Models\Sale::create([
            'reference_number' => 'POS-261109-9999',
            'sale_date' => now()->toDateString(),
            'customer_name' => 'Customer Belitung',
            'total_amount' => 534551,
            'payment_method' => 'transfer',
            'notes' => '[POS] Bayar: TRANSFER',
            'user_id' => $this->user->id,
            'store_id' => $this->store->id,
        ]);

        app(AccountingService::class)->recordSaleJournal($sale);

        // Store ending balance: 3,000,000 + 534,551 = 3,534,551
        $this->assertEquals(3534551, (float) $this->store->fresh()->ending_balance);

        // 1. Check index
        $indexResponse = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.reconciliations.index'));

        $indexResponse->assertStatus(200);
        $accounts = $indexResponse->viewData('accounts');
        $bankAcc = $accounts->firstWhere('code', '1102');
        $cashAcc = $accounts->firstWhere('code', '1101');
        $qrisAcc = $accounts->firstWhere('code', '1103');

        $this->assertEquals(3534551, (float) $bankAcc->current_balance);
        $this->assertEquals(0, (float) $cashAcc->current_balance);
        $this->assertEquals(0, (float) $qrisAcc->current_balance);

        // 2. Check create
        $createResponse = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.reconciliations.create'));

        $createResponse->assertStatus(200);
        $createAccounts = $createResponse->viewData('accounts');
        $selectedAccount = $createResponse->viewData('selectedAccount');

        $this->assertEquals(3534551, (float) $createAccounts->firstWhere('code', '1102')->current_balance);
        $this->assertEquals(3534551, (float) $selectedAccount->current_balance);
    }

    public function test_reconciliation_auto_fallback_uses_dedicated_recon_accounts(): void
    {
        // 1. Surplus fallback test (Actual > System)
        $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('accounting.reconciliations.store'), [
                'account_id' => $this->bankAccount->id,
                'reconciliation_date' => now()->toDateString(),
                'actual_balance' => 1500000,
                // adjustment_account_id intentionally omitted
                'notes' => 'Surplus auto-fallback',
            ]);

        $surplusRecon = BankReconciliation::latest('id')->first();
        $this->assertNotNull($surplusRecon);
        $this->assertEquals('4205', $surplusRecon->adjustmentAccount->code);

        // 2. Deficit fallback test (Actual < System)
        $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('accounting.reconciliations.store'), [
                'account_id' => $this->bankAccount->id,
                'reconciliation_date' => now()->toDateString(),
                'actual_balance' => 1000000,
                // adjustment_account_id intentionally omitted
                'notes' => 'Deficit auto-fallback',
            ]);

        $deficitRecon = BankReconciliation::latest('id')->first();
        $this->assertNotNull($deficitRecon);
        $this->assertEquals('6111', $deficitRecon->adjustmentAccount->code);
    }
}
