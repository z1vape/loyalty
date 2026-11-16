<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\Expense;
use App\Models\FundTransfer;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\AccountingService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountingReportsAndControlsTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected User $admin;
    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Store Pusat',
            'initial_balance' => 2000000,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@z1vape.com',
            'password' => bcrypt('password'),
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['description' => 'Super Admin Role']);
        $this->admin->roles()->attach($superAdminRole->id);
        $this->admin->stores()->attach($this->store->id);

        $this->accountingService = app(AccountingService::class);
        $this->seed(AccountSeeder::class);

        session(['active_store_id' => $this->store->id]);
    }

    public function test_profit_and_loss_report_calculates_gross_profit_and_net_profit(): void
    {
        $this->actingAs($this->admin);

        // 1. Create a product and sell
        $product = Product::create([
            'store_id' => $this->store->id,
            'sku' => 'POD-01',
            'name' => 'Pod Kit Starter',
            'purchase_price' => 150000,
            'selling_price' => 250000,
            'stock' => 10,
        ]);

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'reference_number' => 'POS-RPT-001',
            'sale_date' => Carbon::now()->toDateString(),
            'total_amount' => 480000,
            'discount' => 20000,
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 250000,
            'discount' => 10000,
            'subtotal' => 480000,
        ]);

        $this->accountingService->recordSaleJournal($sale);

        // 2. Record an expense
        $expense = Expense::create([
            'store_id' => $this->store->id,
            'expense_date' => Carbon::now()->toDateString(),
            'category' => 'Listrik & Air',
            'title' => 'Tagihan Listrik Toko',
            'amount' => 50000,
            'user_id' => $this->admin->id,
        ]);
        $this->accountingService->recordExpenseJournal($expense);

        // 3. Record an income
        $income = Income::create([
            'store_id' => $this->store->id,
            'income_date' => Carbon::now()->toDateString(),
            'category' => 'Lain-lain',
            'title' => 'Jual Kardus Bekas',
            'amount' => 15000,
            'user_id' => $this->admin->id,
        ]);
        $this->accountingService->recordIncomeJournal($income);

        $response = $this->get(route('accounting.reports.profit-loss', [
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->endOfMonth()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('grossSales', 500000.0);
        $response->assertViewHas('salesDiscounts', 20000.0);
        $response->assertViewHas('netSales', 480000.0);
        $response->assertViewHas('totalCogs', 300000.0); // 2 * 150000
        $response->assertViewHas('grossProfit', 180000.0); // 480000 - 300000
        $response->assertViewHas('totalOperatingExpenses', 50000.0);
        $response->assertViewHas('operatingProfit', 130000.0);
        $response->assertViewHas('otherIncomes', 15000.0);
        $response->assertViewHas('netProfit', 145000.0); // 130000 + 15000
    }

    public function test_balance_sheet_report_balances_assets_with_liabilities_and_equity(): void
    {
        $this->actingAs($this->admin);

        // Seed initial transactions
        $product = Product::create([
            'store_id' => $this->store->id,
            'sku' => 'COIL-01',
            'name' => 'Coil 0.6 ohm',
            'purchase_price' => 20000,
            'selling_price' => 35000,
            'stock' => 50,
        ]);

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'reference_number' => 'POS-BS-001',
            'sale_date' => Carbon::now()->toDateString(),
            'total_amount' => 175000,
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'selling_price' => 35000,
            'subtotal' => 175000,
        ]);

        $this->accountingService->recordSaleJournal($sale);

        $response = $this->get(route('accounting.reports.balance-sheet', [
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('isBalanced', true);
        $this->assertTrue($response->viewData('isBalanced'));
        $this->assertEquals(
            round($response->viewData('totalAssets'), 2),
            round($response->viewData('totalLiabilitiesAndEquity'), 2)
        );

        // Backward compatibility with as_of_date
        $legacyResponse = $this->get(route('accounting.reports.balance-sheet', [
            'as_of_date' => Carbon::now()->toDateString(),
        ]));
        $legacyResponse->assertStatus(200);
        $legacyResponse->assertViewHas('isBalanced', true);
    }

    public function test_trial_balance_report_verifies_debits_equal_credits(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('accounting.reports.trial-balance', [
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('isBalanced', true);
        $response->assertViewHas('isPeriodBalanced', true);

        // Backward compatibility with as_of_date
        $legacyResponse = $this->get(route('accounting.reports.trial-balance', [
            'as_of_date' => Carbon::now()->toDateString(),
        ]));
        $legacyResponse->assertStatus(200);
        $legacyResponse->assertViewHas('isBalanced', true);
    }

    public function test_cash_flow_statement_renders_successfully(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('accounting.reports.cash-flow', [
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertViewHas('netCashFlow');
    }

    public function test_fund_transfer_creates_balanced_journal_and_moves_cash_to_bank(): void
    {
        $this->actingAs($this->admin);

        $kasAcc = Account::where('store_id', $this->store->id)->where('code', '1101')->first();
        $bankAcc = Account::where('store_id', $this->store->id)->where('code', '1102')->first();

        $response = $this->post(route('accounting.transfers.store'), [
            'transfer_date' => Carbon::now()->toDateString(),
            'from_account_id' => $kasAcc->id,
            'to_account_id' => $bankAcc->id,
            'amount' => 500000,
            'notes' => 'Setor hasil kasir shift siang ke Bank BCA',
        ]);

        $response->assertRedirect(route('accounting.transfers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('fund_transfers', [
            'store_id' => $this->store->id,
            'from_account_id' => $kasAcc->id,
            'to_account_id' => $bankAcc->id,
            'amount' => 500000,
        ]);

        $transfer = FundTransfer::latest('id')->first();
        $this->assertNotNull($transfer);

        $journal = JournalEntry::where('source_type', 'fund_transfer')
            ->where('source_id', $transfer->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertTrue($journal->is_balanced);
        $this->assertEquals(500000, $journal->total_debit);
        $this->assertEquals(500000, $journal->total_credit);

        // Target (Bank) debit 500,000
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $journal->id,
            'account_id' => $bankAcc->id,
            'debit' => 500000,
            'credit' => 0,
        ]);

        // Source (Kas) credit 500,000
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $journal->id,
            'account_id' => $kasAcc->id,
            'debit' => 0,
            'credit' => 500000,
        ]);
    }

    public function test_supplier_payable_tracking_and_partial_and_full_repayments(): void
    {
        $this->actingAs($this->admin);

        $product = Product::create([
            'store_id' => $this->store->id,
            'sku' => 'DEV-01',
            'name' => 'Device Mod Pro',
            'purchase_price' => 500000,
            'selling_price' => 750000,
            'stock' => 0,
        ]);

        // 1. Stock In on Tempo (debt)
        $movement = StockMovement::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'movement_date' => Carbon::now()->toDateString(),
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 500000,
            'total_price' => 5000000,
            'payment_method' => 'tempo',
            'supplier_name' => 'CV Vape Distributor',
            'payment_status' => 'pending',
            'due_date' => Carbon::now()->addDays(14)->toDateString(),
            'paid_amount' => 0,
            'status' => 'approved',
            'reference_number' => 'IN-TEMPO-001',
            'user_id' => $this->admin->id,
        ]);

        // Record stock in journal
        $this->accountingService->recordStockInJournal($movement);

        $this->assertEquals(5000000, $movement->remaining_debt);

        $bankAcc = Account::where('store_id', $this->store->id)->where('code', '1102')->first();

        // 2. Partial repayment of Rp 2,000,000
        $response1 = $this->post(route('accounting.payables.store', $movement), [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_account_id' => $bankAcc->id,
            'amount' => 2000000,
            'reference_number' => 'TRF-PAY-01',
            'notes' => 'Cicilan pertama via transfer bank',
        ]);

        $response1->assertRedirect(route('accounting.payables.index'));
        $movement->refresh();

        $this->assertEquals(2000000, $movement->paid_amount);
        $this->assertEquals('partial', $movement->payment_status);
        $this->assertEquals(3000000, $movement->remaining_debt);

        // 3. Pay remaining Rp 3,000,000 to settle in full
        $response2 = $this->post(route('accounting.payables.store', $movement), [
            'payment_date' => Carbon::now()->toDateString(),
            'payment_account_id' => $bankAcc->id,
            'amount' => 3000000,
            'reference_number' => 'TRF-PAY-02',
            'notes' => 'Pelunasan sisa hutang',
        ]);

        $response2->assertRedirect(route('accounting.payables.index'));
        $movement->refresh();

        $this->assertEquals(5000000, $movement->paid_amount);
        $this->assertEquals('paid', $movement->payment_status);
        $this->assertEquals(0, $movement->remaining_debt);

        // Check payment journals
        $payments = SupplierPayment::where('stock_movement_id', $movement->id)->get();
        $this->assertCount(2, $payments);

        foreach ($payments as $pay) {
            $journal = JournalEntry::where('source_type', 'supplier_payment')
                ->where('source_id', $pay->id)
                ->first();

            $this->assertNotNull($journal);
            $this->assertTrue($journal->is_balanced);
        }
    }

    public function test_accounting_period_lock_blocks_transactions_in_closed_period(): void
    {
        $this->actingAs($this->admin);

        // Close period for last month
        $start = Carbon::now()->subMonth()->startOfMonth()->toDateString();
        $end = Carbon::now()->subMonth()->endOfMonth()->toDateString();
        $lockedDate = Carbon::now()->subMonth()->startOfMonth()->addDays(5)->toDateString();

        $period = AccountingPeriod::create([
            'store_id' => $this->store->id,
            'name' => 'Tutup Buku Bulan Lalu',
            'start_date' => $start,
            'end_date' => $end,
            'status' => 'closed',
            'closed_at' => Carbon::now(),
            'closed_by' => $this->admin->id,
        ]);

        $this->assertTrue($this->accountingService->isDateLocked($this->store->id, $lockedDate));

        // Expect exception when calling assertDateNotLocked
        $this->expectException(\LogicException::class);
        $this->accountingService->assertDateNotLocked($this->store->id, $lockedDate);
    }

    public function test_profit_and_loss_auto_syncs_unjournaled_sales(): void
    {
        $this->actingAs($this->admin);

        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Liquid Mango Ice',
            'sku' => 'MNGO-01',
            'stock' => 10,
            'purchase_price' => 50000,
            'selling_price' => 100000,
        ]);

        $saleDate = Carbon::now()->startOfMonth()->addDays(2)->toDateString();

        // Create sale directly without journal entry (simulating historical/legacy sale)
        $sale = Sale::create([
            'store_id' => $this->store->id,
            'user_id' => $this->admin->id,
            'reference_number' => 'POS-UNSYNCED-01',
            'sale_date' => $saleDate,
            'customer_name' => 'John Doe',
            'total_amount' => 100000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'selling_price' => 100000,
            'discount' => 0,
            'subtotal' => 100000,
        ]);

        // Prior to viewing report, no journal entry exists
        $this->assertEquals(0, JournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());

        // Access P&L report for this month
        $response = $this->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.reports.profit-and-loss', [
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->endOfMonth()->toDateString(),
            ]));

        $response->assertOk();
        $response->assertSee('Sinkronkan Jurnal');
        $response->assertSee('Store Pusat');

        // Verify that the journal entry was created automatically on the fly
        $this->assertEquals(1, JournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());

        // Verify that the sales figure in P&L is 100,000
        $response->assertViewHas('grossSales', 100000.0);
        $response->assertViewHas('netSales', 100000.0);
    }

    public function test_manual_sync_journals_endpoint(): void
    {
        $this->actingAs($this->admin);

        $response = $this->withSession(['active_store_id' => $this->store->id])
            ->from(route('accounting.reports.profit-and-loss'))
            ->get(route('accounting.reports.sync-journals'));

        $response->assertRedirect(route('accounting.reports.profit-and-loss'));
        $response->assertSessionHas('success');
    }

    public function test_cancelled_or_void_sales_are_purged_and_excluded_from_profit_and_loss(): void
    {
        $this->actingAs($this->admin);

        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Pod System X',
            'sku' => 'POD-X',
            'stock' => 10,
            'purchase_price' => 50000,
            'selling_price' => 45000,
        ]);

        $saleDate = Carbon::now()->startOfMonth()->addDays(3)->toDateString();

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'user_id' => $this->admin->id,
            'reference_number' => 'POS-VOID-01',
            'sale_date' => $saleDate,
            'customer_name' => 'Void Customer',
            'total_amount' => 45000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'selling_price' => 45000,
            'discount' => 0,
            'subtotal' => 45000,
        ]);

        // Record initial journal entry
        $this->accountingService->recordSaleJournal($sale);
        $this->assertEquals(1, JournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());

        // Now cancel/void the sale
        $sale->update([
            'status' => 'cancelled',
            'cancellation_reason' => 'Customer cancel',
        ]);

        // Access P&L report which triggers autoSyncUnjournaledTransactions
        $response = $this->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.reports.profit-and-loss', [
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->endOfMonth()->toDateString(),
            ]));

        $response->assertOk();

        // The journal for the cancelled/void sale must be purged
        $this->assertEquals(0, JournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());

        // Net revenue and gross sales must be 0 (excluding the voided sale)
        $response->assertViewHas('grossSales', 0.0);
        $response->assertViewHas('netSales', 0.0);
    }

    public function test_movement_cancelled_automatically_cancels_sale_and_purges_journal(): void
    {
        $this->actingAs($this->admin);

        $product = Product::create([
            'store_id' => $this->store->id,
            'name' => 'Pod System Y',
            'sku' => 'POD-Y',
            'stock' => 10,
            'purchase_price' => 50000,
            'selling_price' => 45000,
        ]);

        $saleDate = Carbon::now()->startOfMonth()->addDays(4)->toDateString();

        $sale = Sale::create([
            'store_id' => $this->store->id,
            'user_id' => $this->admin->id,
            'reference_number' => 'SALE-VOID-99',
            'sale_date' => $saleDate,
            'customer_name' => 'Void Movement Customer',
            'total_amount' => 45000,
            'payment_method' => 'cash',
            'status' => 'completed', // Sale was still marked completed!
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'selling_price' => 45000,
            'discount' => 0,
            'subtotal' => 45000,
        ]);

        // Stock movement was marked cancelled in stock_movements
        StockMovement::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'user_id' => $this->admin->id,
            'reference_number' => 'SALE-VOID-99',
            'movement_date' => $saleDate,
            'type' => 'out',
            'quantity' => 1,
            'purchase_price' => 50000,
            'selling_price' => 45000,
            'discount' => 0,
            'total_price' => 45000,
            'status' => 'cancelled',
            'notes' => '[DIBATALKAN oleh Kasir: Salah input]',
        ]);

        // Initial journal existed
        $this->accountingService->recordSaleJournal($sale);
        $this->assertEquals(1, JournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());

        // Access P&L report
        $response = $this->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.reports.profit-and-loss', [
                'start_date' => Carbon::now()->startOfMonth()->toDateString(),
                'end_date' => Carbon::now()->endOfMonth()->toDateString(),
            ]));

        $response->assertOk();

        // Sale must now be synchronized to cancelled
        $sale->refresh();
        $this->assertEquals('cancelled', $sale->status);

        // Journal entry must be purged
        $this->assertEquals(0, JournalEntry::where('source_type', 'sale')->where('source_id', $sale->id)->count());
        $response->assertViewHas('grossSales', 0.0);
        $response->assertViewHas('netSales', 0.0);
    }
}
