<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;

class JournalExportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore()
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        return [$user, $store];
    }

    public function test_can_export_journals_to_excel_with_date_range(): void
    {
        [$user, $store] = $this->createAdminWithStore();

        $cashAcc = Account::create([
            'store_id' => $store->id,
            'code' => '1101',
            'name' => 'Kas Toko',
            'type' => 'asset',
            'category' => 'Kas & Setara Kas',
            'normal_balance' => 'debit',
        ]);

        $revenueAcc = Account::create([
            'store_id' => $store->id,
            'code' => '4101',
            'name' => 'Pendapatan Penjualan',
            'type' => 'revenue',
            'category' => 'Pendapatan Operasional',
            'normal_balance' => 'credit',
        ]);

        // Entry within date range (2026-10-23)
        $entry1 = JournalEntry::create([
            'store_id' => $store->id,
            'entry_number' => 'JRN-20261023-0001',
            'entry_date' => '2026-10-23',
            'source_type' => 'sale',
            'reference_number' => 'POS-001',
            'description' => 'Penjualan POS Kasir',
            'total_debit' => 150000,
            'total_credit' => 150000,
            'is_balanced' => true,
            'user_id' => $user->id,
        ]);

        JournalItem::create([
            'journal_entry_id' => $entry1->id,
            'account_id' => $cashAcc->id,
            'debit' => 150000,
            'credit' => 0,
            'memo' => 'Penerimaan Kas',
        ]);

        JournalItem::create([
            'journal_entry_id' => $entry1->id,
            'account_id' => $revenueAcc->id,
            'debit' => 0,
            'credit' => 150000,
            'memo' => 'Pendapatan POS',
        ]);

        // Request export with date range
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('accounting.journals.export', [
                'start_date' => '2026-10-20',
                'end_date' => '2026-10-25',
                'source_type' => 'sale',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-disposition');
        $this->assertStringContainsString('jurnal_transaksi_', $response->headers->get('content-disposition'));
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_guest_cannot_export_journals(): void
    {
        $response = $this->get(route('accounting.journals.export'));
        $response->assertRedirect(route('login'));
    }
}
