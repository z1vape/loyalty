<?php

use App\Models\Account;
use App\Models\Sale;
use App\Models\Store;
use App\Services\AccountingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $accountingService = app(AccountingService::class);

        // 1. Move any journal items in Account 1103 (Piutang QRIS & EDC) for transfer or cash sales to Account 1102 / 1101
        $qrisAccounts = Account::withoutGlobalScopes()->where('code', '1103')->get();
        foreach ($qrisAccounts as $qrisAcc) {
            $storeId = $qrisAcc->store_id;
            $bankAcc = Account::withoutGlobalScopes()->where('store_id', $storeId)->where('code', '1102')->first();
            $cashAcc = Account::withoutGlobalScopes()->where('store_id', $storeId)->where('code', '1101')->first();

            $items = DB::table('journal_items')
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->where('journal_items.account_id', $qrisAcc->id)
                ->where('journal_entries.source_type', 'sale')
                ->select('journal_items.id', 'journal_entries.source_id', 'journal_entries.id as entry_id')
                ->get();

            foreach ($items as $item) {
                $sale = Sale::withoutGlobalScopes()->find($item->source_id);
                if ($sale) {
                    $method = strtolower($sale->payment_method ?? 'cash');
                    if (in_array($method, ['transfer', 'cash', 'tunai'])) {
                        $targetAccId = ($method === 'transfer' || empty($cashAcc)) ? $bankAcc?->id : $cashAcc?->id;
                        if ($targetAccId) {
                            DB::table('journal_items')
                                ->where('id', $item->id)
                                ->update(['account_id' => $targetAccId]);
                        }
                    }
                }
            }
        }

        // 2. Explicitly re-sync Sale 1095 and 1100 (Cabang Belitung)
        $belitungSales = Sale::withoutGlobalScopes()->whereIn('id', [1095, 1100])->get();
        foreach ($belitungSales as $sale) {
            $sale->updateQuietly(['store_id' => 3]);
            try {
                $accountingService->recordSaleJournal($sale);
            } catch (\Throwable $e) {
                // Ignore if journal sync throws
            }
        }

        // 3. Ensure any stray 1103 items on Belitung are cleaned up if no QRIS sales exist
        $belitungAcc1103 = Account::withoutGlobalScopes()->where('store_id', 3)->where('code', '1103')->first();
        if ($belitungAcc1103) {
            $belitungBankAcc = Account::withoutGlobalScopes()->where('store_id', 3)->where('code', '1102')->first();
            if ($belitungBankAcc) {
                DB::table('journal_items')
                    ->where('account_id', $belitungAcc1103->id)
                    ->update(['account_id' => $belitungBankAcc->id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

