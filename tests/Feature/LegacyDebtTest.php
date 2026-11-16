<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Account;
use App\Models\LegacyDebt;
use App\Models\LegacyDebtPayment;
use App\Models\JournalEntry;
use App\Services\AccountingService;
use Carbon\Carbon;

class LegacyDebtTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithStore(string $storeName = 'Z1 VAPE STORE VOL 1')
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => $storeName]);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $accountingService = app(AccountingService::class);
        $accountingService->ensureStoreAccounts($store->id);

        return [$user, $store];
    }

    private function seedLegacyDebts(): void
    {
        LegacyDebt::updateOrCreate(
            ['code' => 'LEG-VB'],
            [
                'supplier_name' => 'Vape Break (VB)',
                'location' => 'Tanggerang',
                'initial_amount' => 133595000,
                'paid_amount' => 0,
                'remaining_amount' => 133595000,
                'notes' => 'Utang warisan lama manajemen masa lalu (ditanggung bersama Vol 1, Vol 2, Belitung).',
            ]
        );

        LegacyDebt::updateOrCreate(
            ['code' => 'LEG-VZ'],
            [
                'supplier_name' => 'Vapezoo',
                'location' => 'Jakarta',
                'initial_amount' => 19000000,
                'paid_amount' => 0,
                'remaining_amount' => 19000000,
                'notes' => 'Utang warisan lama manajemen masa lalu (ditanggung bersama Vol 1, Vol 2, Belitung).',
            ]
        );

        LegacyDebt::updateOrCreate(
            ['code' => 'LEG-VO'],
            [
                'supplier_name' => 'Vape On',
                'location' => 'Surabaya',
                'initial_amount' => 377925656,
                'paid_amount' => 0,
                'remaining_amount' => 377925656,
                'notes' => 'Utang warisan lama manajemen masa lalu (ditanggung bersama Vol 1, Vol 2, Belitung).',
            ]
        );
    }

    public function test_can_view_legacy_debts_tab_with_all_3_suppliers(): void
    {
        [$user, $store] = $this->createAdminWithStore();
        $this->seedLegacyDebts();

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->get(route('accounting.payables.index', ['tab' => 'legacy']));

        $response->assertStatus(200);
        $response->assertSee('Hutang Warisan Bersama (3 Cabang)');
        $response->assertSee('Vape Break (VB)');
        $response->assertSee('Tanggerang');
        $response->assertSee('133.595.000');
        $response->assertSee('Vapezoo');
        $response->assertSee('Jakarta');
        $response->assertSee('19.000.000');
        $response->assertSee('Vape On');
        $response->assertSee('Surabaya');
        $response->assertSee('377.925.656');
        $response->assertSee('530.520.656'); // Total initial: 133.595.000 + 19.000.000 + 377.925.656
    }

    public function test_store_can_pay_legacy_debt_installment_and_generate_journal(): void
    {
        [$user, $store] = $this->createAdminWithStore('Z1 VAPE STORE VOL 1');
        $this->seedLegacyDebts();

        $debt = LegacyDebt::where('code', 'LEG-VZ')->first();
        $this->assertEquals(19000000, (float) $debt->remaining_amount);

        $bankAccount = Account::where('store_id', $store->id)
            ->where('code', '1102')
            ->first();

        $payAmount = 5000000;

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('accounting.payables.legacy.pay', $debt->id), [
                'payment_date' => Carbon::today()->toDateString(),
                'payment_account_id' => $bankAccount->id,
                'amount' => $payAmount,
                'payment_method' => 'transfer',
                'reference_number' => 'TF-LEG-001',
                'notes' => 'Cicilan pertama Vapezoo dari Vol 1',
            ]);

        $response->assertRedirect(route('accounting.payables.index', ['tab' => 'legacy']));
        $response->assertSessionHas('success');

        // Check payment created
        $this->assertDatabaseHas('legacy_debt_payments', [
            'legacy_debt_id' => $debt->id,
            'store_id' => $store->id,
            'amount' => $payAmount,
            'reference_number' => 'TF-LEG-001',
        ]);

        // Check legacy debt totals updated
        $debt->refresh();
        $this->assertEquals(5000000, (float) $debt->paid_amount);
        $this->assertEquals(14000000, (float) $debt->remaining_amount);

        // Check journal entry
        $journal = JournalEntry::where('store_id', $store->id)
            ->where('source_type', 'legacy_debt_payment')
            ->first();

        $this->assertNotNull($journal);
        $this->assertEquals($payAmount, (float) $journal->total_debit);
        $this->assertEquals($payAmount, (float) $journal->total_credit);
        $this->assertTrue((bool) $journal->is_balanced);

        // Check debit 2105 and credit 1102
        $items = $journal->items()->with('account')->get();
        $debitItem = $items->firstWhere('debit', '>', 0);
        $creditItem = $items->firstWhere('credit', '>', 0);

        $this->assertEquals('2105', $debitItem->account->code);
        $this->assertEquals($payAmount, (float) $debitItem->debit);

        $this->assertEquals($bankAccount->id, $creditItem->account_id);
        $this->assertEquals($payAmount, (float) $creditItem->credit);
    }

    public function test_cannot_pay_more_than_remaining_legacy_debt(): void
    {
        [$user, $store] = $this->createAdminWithStore();
        $this->seedLegacyDebts();

        $debt = LegacyDebt::where('code', 'LEG-VZ')->first();
        $bankAccount = Account::where('store_id', $store->id)->where('code', '1102')->first();

        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->post(route('accounting.payables.legacy.pay', $debt->id), [
                'payment_date' => Carbon::today()->toDateString(),
                'payment_account_id' => $bankAccount->id,
                'amount' => 25000000, // Exceeds 19jt
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertEquals(19000000, (float) $debt->fresh()->remaining_amount);
    }

    public function test_can_cancel_legacy_payment_and_purge_journal(): void
    {
        [$user, $store] = $this->createAdminWithStore();
        $this->seedLegacyDebts();

        $debt = LegacyDebt::where('code', 'LEG-VB')->first();
        $bankAccount = Account::where('store_id', $store->id)->where('code', '1101')->first();

        // Make payment
        $payment = LegacyDebtPayment::create([
            'legacy_debt_id' => $debt->id,
            'store_id' => $store->id,
            'user_id' => $user->id,
            'payment_date' => Carbon::today()->toDateString(),
            'amount' => 10000000,
            'payment_account_id' => $bankAccount->id,
            'payment_method' => 'cash',
        ]);
        $debt->recalculateTotals();

        $accountingService = app(AccountingService::class);
        $accountingService->recordLegacyDebtPaymentJournal($payment);

        $this->assertEquals(123595000, (float) $debt->fresh()->remaining_amount);
        $this->assertDatabaseHas('journal_entries', ['source_type' => 'legacy_debt_payment', 'source_id' => $payment->id]);

        // Now delete payment
        $response = $this->actingAs($user)
            ->withSession(['active_store_id' => $store->id])
            ->delete(route('accounting.payables.legacy.destroy-payment', $payment->id));

        $response->assertRedirect(route('accounting.payables.index', ['tab' => 'legacy']));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('legacy_debt_payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('journal_entries', ['source_type' => 'legacy_debt_payment', 'source_id' => $payment->id]);

        $this->assertEquals(133595000, (float) $debt->fresh()->remaining_amount);
        $this->assertEquals(0, (float) $debt->fresh()->paid_amount);
    }
}
