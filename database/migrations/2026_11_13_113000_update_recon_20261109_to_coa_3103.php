<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\BankReconciliation;
use App\Models\JournalEntry;
use App\Models\Account;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Locate the specific reconciliation record (RECON-20261109-6VX8)
        $recon = BankReconciliation::withoutGlobalScopes()
            ->where('reconciliation_number', 'RECON-20261109-6VX8')
            ->first();

        if ($recon) {
            $acc3103 = Account::withoutGlobalScopes()
                ->where('store_id', $recon->store_id)
                ->where('code', '3103')
                ->first();

            if (!$acc3103) {
                $acc3103 = Account::withoutGlobalScopes()->create([
                    'store_id' => $recon->store_id,
                    'code' => '3103',
                    'name' => 'Penyesuaian Saldo Awal & Koreksi Saldo Toko',
                    'type' => 'equity',
                    'category' => 'Modal',
                    'normal_balance' => 'credit',
                    'is_system' => false,
                    'description' => 'Penyesuaian selisih saldo awal atau koreksi ekuitas historis toko agar tidak mempengaruhi laba-rugi berjalan',
                ]);
            }

            $oldAccountId = $recon->adjustment_account_id;

            // Update reconciliation model
            $recon->adjustment_account_id = $acc3103->id;
            $recon->save();

            // Locate and update the corresponding journal entry
            $journal = JournalEntry::withoutGlobalScopes()
                ->where(function ($q) use ($recon) {
                    $q->where('source_type', 'bank_reconciliation')
                      ->where('source_id', $recon->id);
                })
                ->orWhere('reference_number', $recon->reconciliation_number)
                ->orWhere('entry_number', 'JE-202611-0449')
                ->first();

            if ($journal) {
                foreach ($journal->items as $item) {
                    if ($item->account_id == $oldAccountId || ($item->account && $item->account->code === '6110')) {
                        $item->account_id = $acc3103->id;
                        $item->memo = 'Akun penyeimbang rekonsiliasi: ' . $acc3103->name;
                        $item->save();
                    }
                }
            }
        }

        // 2. Direct safety check by Journal Entry number JE-202611-0449
        $directJournal = JournalEntry::withoutGlobalScopes()
            ->where('entry_number', 'JE-202611-0449')
            ->first();

        if ($directJournal) {
            $acc3103 = Account::withoutGlobalScopes()
                ->where('store_id', $directJournal->store_id)
                ->where('code', '3103')
                ->first();

            if ($acc3103) {
                foreach ($directJournal->items as $item) {
                    if ($item->account && $item->account->code === '6110') {
                        $item->account_id = $acc3103->id;
                        $item->memo = 'Akun penyeimbang rekonsiliasi: ' . $acc3103->name;
                        $item->save();
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse migration needed
    }
};

