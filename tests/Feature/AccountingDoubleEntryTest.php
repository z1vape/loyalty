<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AccountingDoubleEntryTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store1;
    protected Store $store2;
    protected User $admin;
    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store1 = Store::create([
            'name' => 'Store Jakarta',
            'initial_balance' => 1000000,
        ]);

        $this->store2 = Store::create([
            'name' => 'Store Bandung',
            'initial_balance' => 500000,
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@z1.com',
            'password' => bcrypt('password'),
        ]);

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin'], ['description' => 'Super Admin Role']);
        $this->admin->roles()->attach($superAdminRole->id);
        $this->admin->stores()->attach([$this->store1->id, $this->store2->id]);

        $this->accountingService = app(AccountingService::class);
        $this->seed(AccountSeeder::class);
    }

    public function test_default_chart_of_accounts_are_provisioned_per_store(): void
    {
        $accountsStore1 = Account::withoutGlobalScopes()->where('store_id', $this->store1->id)->count();
        $accountsStore2 = Account::withoutGlobalScopes()->where('store_id', $this->store2->id)->count();

        $this->assertGreaterThanOrEqual(15, $accountsStore1);
        $this->assertGreaterThanOrEqual(15, $accountsStore2);

        $kasStore1 = Account::withoutGlobalScopes()->where('store_id', $this->store1->id)->where('code', '1101')->first();
        $this->assertNotNull($kasStore1);
        $this->assertTrue($kasStore1->is_system);
        $this->assertEquals('debit', $kasStore1->normal_balance);
    }

    public function test_sale_auto_journal_is_balanced_with_cogs_and_revenue(): void
    {
        session(['active_store_id' => $this->store1->id]);

        $product = Product::create([
            'store_id' => $this->store1->id,
            'sku' => 'LIQ-001',
            'name' => 'Liquid Mango 60ml',
            'purchase_price' => 70000,
            'selling_price' => 100000,
            'stock' => 20,
        ]);

        $sale = Sale::create([
            'store_id' => $this->store1->id,
            'reference_number' => 'POS-TEST-001',
            'sale_date' => Carbon::now()->toDateString(),
            'customer_name' => 'Budi',
            'total_amount' => 200000,
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);

        $sale->items()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'selling_price' => 100000,
            'discount' => 0,
            'subtotal' => 200000,
        ]);

        $journal = $this->accountingService->recordSaleJournal($sale);

        $this->assertNotNull($journal);
        $this->assertTrue($journal->is_balanced);
        $this->assertEquals(340000, (float) $journal->total_debit); // 200k cash + 140k COGS
        $this->assertEquals(340000, (float) $journal->total_credit); // 200k Rev + 140k Inv

        // Check Debit Kas Toko
        $kasItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '1101'))->first();
        $this->assertEquals(200000, (float) $kasItem->debit);

        // Check Credit Pendapatan Penjualan
        $revItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '4101'))->first();
        $this->assertEquals(200000, (float) $revItem->credit);

        // Check Debit HPP
        $cogsItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '5101'))->first();
        $this->assertEquals(140000, (float) $cogsItem->debit);

        // Check Credit Persediaan
        $invItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '1201'))->first();
        $this->assertEquals(140000, (float) $invItem->credit);
    }

    public function test_sale_cancellation_reverses_journal_properly(): void
    {
        session(['active_store_id' => $this->store1->id]);

        $sale = Sale::create([
            'store_id' => $this->store1->id,
            'reference_number' => 'POS-REV-001',
            'sale_date' => Carbon::now()->toDateString(),
            'total_amount' => 150000,
            'payment_method' => 'cash',
            'user_id' => $this->admin->id,
        ]);

        $sale->items()->create([
            'product_id' => Product::create([
                'store_id' => $this->store1->id,
                'sku' => 'COIL-01',
                'name' => 'Coil 0.6 Ohm',
                'purchase_price' => 30000,
                'selling_price' => 50000,
                'stock' => 10,
            ])->id,
            'quantity' => 3,
            'selling_price' => 50000,
            'subtotal' => 150000,
        ]);

        $this->accountingService->recordSaleJournal($sale);

        $reversalJournal = $this->accountingService->reverseSaleJournal($sale, 'Salah input nominal');

        $this->assertNotNull($reversalJournal);
        $this->assertTrue($reversalJournal->is_balanced);
        $this->assertEquals('sale_cancellation', $reversalJournal->source_type);

        // Kas Toko should now be credited
        $kasReversal = $reversalJournal->items()->whereHas('account', fn($q) => $q->where('code', '1101'))->first();
        $this->assertEquals(150000, (float) $kasReversal->credit);
    }

    public function test_expense_auto_journal_creates_debit_expense_and_credit_cash(): void
    {
        session(['active_store_id' => $this->store1->id]);

        $expense = Expense::create([
            'store_id' => $this->store1->id,
            'user_id' => $this->admin->id,
            'expense_date' => Carbon::now()->toDateString(),
            'category' => 'Listrik & Air',
            'title' => 'Token Listrik Toko',
            'amount' => 250000,
        ]);

        $journal = $this->accountingService->recordExpenseJournal($expense);

        $this->assertNotNull($journal);
        $this->assertTrue($journal->is_balanced);
        $this->assertEquals(250000, (float) $journal->total_debit);
        $this->assertEquals(250000, (float) $journal->total_credit);

        // Debit Beban Listrik (6103)
        $expenseItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '6103'))->first();
        $this->assertEquals(250000, (float) $expenseItem->debit);

        // Credit Kas Toko (1101)
        $kasItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '1101'))->first();
        $this->assertEquals(250000, (float) $kasItem->credit);
    }

    public function test_income_auto_journal_creates_debit_cash_and_credit_income(): void
    {
        session(['active_store_id' => $this->store1->id]);

        $income = Income::create([
            'store_id' => $this->store1->id,
            'user_id' => $this->admin->id,
            'income_date' => Carbon::now()->toDateString(),
            'category' => 'Lain-lain',
            'title' => 'Komisi Titipan Konsinyasi',
            'amount' => 100000,
        ]);

        $journal = $this->accountingService->recordIncomeJournal($income);

        $this->assertNotNull($journal);
        $this->assertTrue($journal->is_balanced);

        // Debit Kas Toko (1101)
        $kasItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '1101'))->first();
        $this->assertEquals(100000, (float) $kasItem->debit);

        // Credit Pendapatan Lain-lain (4201)
        $incomeItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '4201'))->first();
        $this->assertEquals(100000, (float) $incomeItem->credit);
    }

    public function test_stock_adjustment_creates_loss_journal_when_quantity_is_reduced(): void
    {
        session(['active_store_id' => $this->store1->id]);

        $product = Product::create([
            'store_id' => $this->store1->id,
            'sku' => 'POD-01',
            'name' => 'Pod Cartridge',
            'purchase_price' => 25000,
            'selling_price' => 35000,
            'stock' => 10,
        ]);

        $inventoryService = app(InventoryService::class);
        $adjustment = $inventoryService->adjust($product, -2, 'damaged', 'Bocor kemasan', $this->admin->id);

        $journal = JournalEntry::withoutGlobalScopes()
            ->where('source_type', 'stock_adjustment')
            ->where('source_id', $adjustment->id)
            ->first();

        $this->assertNotNull($journal);
        $this->assertTrue($journal->is_balanced);
        $this->assertEquals(50000, (float) $journal->total_debit); // 2 * 25k

        // Debit Beban Selisih Stok (6105)
        $lossItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '6105'))->first();
        $this->assertEquals(50000, (float) $lossItem->debit);

        // Credit Persediaan Barang (1201)
        $invItem = $journal->items()->whereHas('account', fn($q) => $q->where('code', '1201'))->first();
        $this->assertEquals(50000, (float) $invItem->credit);
    }

    public function test_manual_journal_validation_rejects_unbalanced_entries(): void
    {
        $this->actingAs($this->admin);
        session(['active_store_id' => $this->store1->id]);

        $kasAcc = Account::where('store_id', $this->store1->id)->where('code', '1101')->first();
        $modalAcc = Account::where('store_id', $this->store1->id)->where('code', '3101')->first();

        // Submit unbalanced entry (Debit 500k vs Credit 400k)
        $response = $this->post(route('accounting.journals.store'), [
            'entry_date' => Carbon::now()->toDateString(),
            'description' => 'Setoran modal tambahan',
            'items' => [
                [
                    'account_id' => $kasAcc->id,
                    'debit' => 500000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $modalAcc->id,
                    'debit' => 0,
                    'credit' => 400000,
                ]
            ]
        ]);

        $response->assertSessionHasErrors('items');
        $this->assertEquals(0, JournalEntry::where('source_type', 'manual')->count());

        // Submit balanced entry (Debit 500k vs Credit 500k)
        $responseBalanced = $this->post(route('accounting.journals.store'), [
            'entry_date' => Carbon::now()->toDateString(),
            'description' => 'Setoran modal tambahan',
            'items' => [
                [
                    'account_id' => $kasAcc->id,
                    'debit' => 500000,
                    'credit' => 0,
                ],
                [
                    'account_id' => $modalAcc->id,
                    'debit' => 0,
                    'credit' => 500000,
                ]
            ]
        ]);

        $responseBalanced->assertRedirect(route('accounting.journals.index'));
        $this->assertEquals(1, JournalEntry::where('source_type', 'manual')->count());
    }

    public function test_system_account_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin);
        session(['active_store_id' => $this->store1->id]);

        $kasAcc = Account::where('store_id', $this->store1->id)->where('code', '1101')->first();
        $this->assertTrue($kasAcc->is_system);

        $response = $this->delete(route('accounting.accounts.destroy', $kasAcc->id));
        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('accounts', ['id' => $kasAcc->id]);
    }

    public function test_general_ledger_view_calculates_opening_and_closing_balance(): void
    {
        $this->actingAs($this->admin);
        session(['active_store_id' => $this->store1->id]);

        $kasAcc = Account::where('store_id', $this->store1->id)->where('code', '1101')->first();

        $response = $this->get(route('accounting.journals.ledger', [
            'account_id' => $kasAcc->id,
            'start_date' => Carbon::now()->startOfMonth()->toDateString(),
            'end_date' => Carbon::now()->endOfMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertViewHas('selectedAccount');
        $response->assertViewHas('closingBalance');
    }

    public function test_auto_cash_to_bank_routes_cash_sales_and_expenses_to_bank_toko(): void
    {
        $this->store1->update([
            'bank_name' => 'BCA',
            'bank_account_number' => '0411685917',
            'auto_cash_to_bank' => true,
        ]);
        session(['active_store_id' => $this->store1->id]);

        // 1. Cash Sale
        $sale = Sale::create([
            'store_id' => $this->store1->id,
            'user_id' => $this->admin->id,
            'reference_number' => 'INV-AUTOBANK-001',
            'sale_date' => Carbon::now()->toDateString(),
            'total_amount' => 500000,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $saleJournal = $this->accountingService->recordSaleJournal($sale);
        $this->assertNotNull($saleJournal);
        $this->assertTrue($saleJournal->is_balanced);

        // Verify Debit went to Bank Toko (1102), NOT Kas Toko (1101)
        $bankItemSale = $saleJournal->items()->whereHas('account', fn($q) => $q->where('code', '1102'))->first();
        $kasItemSale = $saleJournal->items()->whereHas('account', fn($q) => $q->where('code', '1101'))->first();
        $this->assertNotNull($bankItemSale);
        $this->assertEquals(500000, (float) $bankItemSale->debit);
        $this->assertNull($kasItemSale);

        // 2. Operational Expense
        $expense = Expense::create([
            'store_id' => $this->store1->id,
            'user_id' => $this->admin->id,
            'expense_date' => Carbon::now()->toDateString(),
            'category' => 'Listrik & Air',
            'title' => 'Tagihan Listrik Operasional',
            'amount' => 150000,
        ]);

        $expJournal = $this->accountingService->recordExpenseJournal($expense);
        $this->assertNotNull($expJournal);
        $this->assertTrue($expJournal->is_balanced);

        // Verify Credit was from Bank Toko (1102), NOT Kas Toko (1101)
        $bankItemExp = $expJournal->items()->whereHas('account', fn($q) => $q->where('code', '1102'))->first();
        $kasItemExp = $expJournal->items()->whereHas('account', fn($q) => $q->where('code', '1101'))->first();
        $this->assertNotNull($bankItemExp);
        $this->assertEquals(150000, (float) $bankItemExp->credit);
        $this->assertNull($kasItemExp);
    }
}

