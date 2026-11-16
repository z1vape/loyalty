<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModularDashboardsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::create([
            'name' => 'Zona 1 Vape Store',
            'address' => 'Jl. Test No. 1',
            'phone' => '08123456789',
            'bank_account_name' => 'BCA Toko',
            'bank_account_number' => '1234567890',
            'auto_cash_to_bank' => true,
        ]);

        $this->user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@z1.com',
            'password' => bcrypt('password'),
        ]);

        $role = Role::firstOrCreate(['name' => 'Super Admin'], ['description' => 'Super Admin Role']);
        $this->user->roles()->attach($role->id);
        $this->user->stores()->attach($this->store->id);

        $this->seed(AccountSeeder::class);
    }

    public function test_guest_is_redirected_from_all_modular_dashboards(): void
    {
        $this->get('/purchasing/dashboard')->assertRedirect('/login');
        $this->get('/finance/dashboard')->assertRedirect('/login');
        $this->get('/accounting/dashboard')->assertRedirect('/login');
    }

    public function test_authorized_user_can_access_purchasing_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get('/purchasing/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Purchasing');
        $response->assertSee('Total Belanja Stok');
        $response->assertSee('Sisa Hutang Supplier');
        $response->assertSee('Top 5 Supplier Pengadaan');
    }

    public function test_purchasing_dashboard_filters_work(): void
    {
        foreach (['today', 'this_week', 'mtd', 'last_month', 'ytd'] as $filter) {
            $this->actingAs($this->user)
                ->withSession(['active_store_id' => $this->store->id])
                ->get("/purchasing/dashboard?filter={$filter}")
                ->assertStatus(200);
        }
    }

    public function test_purchasing_dashboard_does_not_count_cash_purchases_as_debt(): void
    {
        $product = \App\Models\Product::create([
            'name' => 'Liquid Saltnic',
            'sku' => 'LIQ-001',
            'store_id' => $this->store->id,
            'current_stock' => 10,
            'selling_price' => 150000,
            'purchase_price' => 100000,
        ]);

        // 1. Cash Purchase of Rp 22.529.979 (should NOT be debt, even if paid_amount was default 0)
        \App\Models\StockMovement::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'reference_number' => 'IN-CASH-001',
            'movement_date' => now()->toDateString(),
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 2252997.9,
            'total_price' => 22529979,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'paid_amount' => 0,
            'status' => 'approved',
        ]);

        // 2. Tempo Purchase of Rp 19.815.000 (IS debt)
        \App\Models\StockMovement::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'user_id' => $this->user->id,
            'reference_number' => 'IN-TEMPO-001',
            'movement_date' => now()->toDateString(),
            'type' => 'in',
            'quantity' => 5,
            'purchase_price' => 3963000,
            'total_price' => 19815000,
            'payment_method' => 'tempo',
            'payment_status' => 'pending',
            'paid_amount' => 0,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get('/purchasing/dashboard');

        $response->assertStatus(200);
        // Sisa Hutang Supplier must be strictly 19.815.000 (tempo only), not 42.344.979 (cash + tempo)!
        $response->assertViewHas('totalRemainingDebt', 19815000.0);
        $response->assertSee('19.815.000');
    }

    public function test_authorized_user_can_access_finance_dashboard(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get('/finance/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Finance');
        $response->assertSee('Posisi Likuiditas Kas');
        $response->assertSee('Total Saldo Toko Berjalan');
        $response->assertSee('Total Kas Masuk');
        $response->assertSee('Total Kas Keluar');
    }

    public function test_finance_dashboard_filters_work(): void
    {
        foreach (['today', 'this_week', 'mtd', 'last_month', 'ytd'] as $filter) {
            $this->actingAs($this->user)
                ->withSession(['active_store_id' => $this->store->id])
                ->get("/finance/dashboard?filter={$filter}")
                ->assertStatus(200);
        }
    }

    public function test_authorized_user_can_access_accounting_dashboard(): void
    {
        $journal = \App\Models\JournalEntry::create([
            'store_id' => $this->store->id,
            'entry_number' => 'JE-20261106-0001',
            'entry_date' => now()->toDateString(),
            'source_type' => 'manual',
            'description' => 'Test Jurnal Terkini',
            'total_debit' => 150000,
            'total_credit' => 150000,
            'is_balanced' => true,
            'user_id' => $this->user->id,
        ]);

        $cashAccount = \App\Models\Account::where('code', '1101')->first();
        $capitalAccount = \App\Models\Account::where('code', '3101')->first();

        \App\Models\JournalItem::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $cashAccount->id,
            'debit' => 150000,
            'credit' => 0,
        ]);
        \App\Models\JournalItem::create([
            'journal_entry_id' => $journal->id,
            'account_id' => $capitalAccount->id,
            'debit' => 0,
            'credit' => 150000,
        ]);

        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get('/accounting/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Accounting');
        $response->assertSee('Integritas Jurnal Umum');
        $response->assertSee('Snapshot Neraca Toko');
        $response->assertSee('Ringkasan Laba Rugi Akuntansi');
        $response->assertSee('JE-20261106-0001');

        // Verify show route redirects to index with search
        $showResponse = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('accounting.journals.show', $journal));

        $showResponse->assertRedirect(route('accounting.journals.index', ['search' => 'JE-20261106-0001']));
    }

    public function test_accounting_dashboard_filters_work(): void
    {
        foreach (['today', 'this_week', 'mtd', 'last_month', 'ytd'] as $filter) {
            $this->actingAs($this->user)
                ->withSession(['active_store_id' => $this->store->id])
                ->get("/accounting/dashboard?filter={$filter}")
                ->assertStatus(200);
        }
    }

    public function test_navigation_menu_renders_all_dashboard_links(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['active_store_id' => $this->store->id])
            ->get('/');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Operasional');
        $response->assertSee('Dashboard Purchasing');
        $response->assertSee('Dashboard Finance');
        $response->assertSee('Dashboard Accounting');
    }
}
