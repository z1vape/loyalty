<?php

use App\Models\Account;
use App\Models\Expense;
use App\Models\Income;
use App\Services\AccountingService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Backfill orphaned expenses where store_id IS NULL
        $expenses = Expense::withoutGlobalScopes()->whereNull('store_id')->get();
        foreach ($expenses as $expense) {
            $storeId = null;
            if ($expense->account_id) {
                $storeId = Account::withoutGlobalScopes()->where('id', $expense->account_id)->value('store_id');
            }
            $expense->store_id = $storeId ?: 1;
            $expense->saveQuietly();

            try {
                app(AccountingService::class)->recordExpenseJournal($expense);
            } catch (\Throwable $e) {
                // Silently skip if journal fails in edge cases
            }
        }

        // 2. Backfill orphaned incomes where store_id IS NULL
        if (Schema::hasTable('incomes')) {
            $incomes = Income::withoutGlobalScopes()->whereNull('store_id')->get();
            foreach ($incomes as $income) {
                $storeId = null;
                if ($income->account_id) {
                    $storeId = Account::withoutGlobalScopes()->where('id', $income->account_id)->value('store_id');
                }
                $income->store_id = $storeId ?: 1;
                $income->saveQuietly();

                try {
                    app(AccountingService::class)->recordIncomeJournal($income);
                } catch (\Throwable $e) {
                    // Silently skip
                }
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

