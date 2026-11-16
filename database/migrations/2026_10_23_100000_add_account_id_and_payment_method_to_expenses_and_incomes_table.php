<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Store;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Account;
use App\Services\AccountingService;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Alter expenses table
        Schema::table('expenses', function (Blueprint $table) {
            if (!Schema::hasColumn('expenses', 'account_id')) {
                $table->foreignId('account_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('expenses', 'payment_method')) {
                $table->string('payment_method', 50)
                    ->default('cash')
                    ->after('amount');
            }

            $table->index(['store_id', 'account_id']);
        });

        // 2. Alter incomes table
        Schema::table('incomes', function (Blueprint $table) {
            if (!Schema::hasColumn('incomes', 'account_id')) {
                $table->foreignId('account_id')
                    ->nullable()
                    ->after('category')
                    ->constrained('accounts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('incomes', 'payment_method')) {
                $table->string('payment_method', 50)
                    ->default('cash')
                    ->after('amount');
            }

            $table->index(['store_id', 'account_id']);
        });

        // 3. Ensure all existing stores have the newly updated default accounts seeded
        $accountingService = app(AccountingService::class);
        $stores = Store::withoutGlobalScopes()->get();
        foreach ($stores as $store) {
            $accountingService->ensureStoreAccounts($store->id);
        }

        // 4. Migrate and sync historical expenses to their matching COA account_id
        $expenses = Expense::withoutGlobalScopes()->whereNull('account_id')->get();
        foreach ($expenses as $expense) {
            $cat = strtolower($expense->category ?? '');
            $code = match (true) {
                str_contains($cat, 'gaji') => '6101',
                str_contains($cat, 'sewa') => '6102',
                str_contains($cat, 'listrik') || str_contains($cat, 'air') || str_contains($cat, 'internet') => '6103',
                str_contains($cat, 'konsumsi') || str_contains($cat, 'makan') || str_contains($cat, 'minum') || str_contains($cat, 'dapur') => '6106',
                str_contains($cat, 'pasar') || str_contains($cat, 'iklan') || str_contains($cat, 'promosi') => '6107',
                str_contains($cat, 'transport') || str_contains($cat, 'bensin') || str_contains($cat, 'ongkir') || str_contains($cat, 'kirim') => '6108',
                str_contains($cat, 'rawat') || str_contains($cat, 'servis') || str_contains($cat, 'perbaikan') => '6109',
                str_contains($cat, 'cabang') || str_contains($cat, 'antar toko') || (str_contains($cat, 'talangan') && !str_contains($cat, 'pribadi')) => '1104',
                str_contains($cat, 'kasbon') || str_contains($cat, 'pinjam') => '1105',
                str_contains($cat, 'bayar balik') || str_contains($cat, 'lunas talangan') => '2102',
                str_contains($cat, 'pribadi') || str_contains($cat, 'reimburse') => '2103',
                str_contains($cat, 'perlengkapan') => '6104',
                default => '6104',
            };

            $matchedAccount = Account::withoutGlobalScopes()
                ->where('store_id', $expense->store_id)
                ->where('code', $code)
                ->first();

            if ($matchedAccount) {
                $expense->account_id = $matchedAccount->id;
                $expense->payment_method = $expense->payment_method ?: 'cash';
                $expense->saveQuietly();
            }

            // Sync journal entry
            $accountingService->recordExpenseJournal($expense);
        }

        // 5. Migrate and sync historical incomes to their matching COA account_id
        $incomes = Income::withoutGlobalScopes()->whereNull('account_id')->get();
        foreach ($incomes as $income) {
            $cat = strtolower($income->category ?? '');
            $code = match (true) {
                str_contains($cat, 'jasa') || str_contains($cat, 'coil') || str_contains($cat, 'kapas') || str_contains($cat, 'build') => '4103',
                str_contains($cat, 'bonus') || str_contains($cat, 'cashback') || str_contains($cat, 'rebate') => '4202',
                str_contains($cat, 'komisi') || str_contains($cat, 'konsinyasi') || str_contains($cat, 'titip') => '4203',
                str_contains($cat, 'bunga') || str_contains($cat, 'giro') => '4204',
                str_contains($cat, 'pengembalian talangan') || str_contains($cat, 'terima talangan') || str_contains($cat, 'pelunasan talangan') => '1104',
                str_contains($cat, 'kasbon') => '1105',
                str_contains($cat, 'modal') => '3101',
                default => '4201',
            };

            $matchedAccount = Account::withoutGlobalScopes()
                ->where('store_id', $income->store_id)
                ->where('code', $code)
                ->first();

            if ($matchedAccount) {
                $income->account_id = $matchedAccount->id;
                $income->payment_method = $income->payment_method ?: 'cash';
                $income->saveQuietly();
            }

            // Sync journal entry
            $accountingService->recordIncomeJournal($income);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'account_id']);
            $table->dropForeign(['account_id']);
            $table->dropColumn(['account_id', 'payment_method']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'account_id']);
            $table->dropForeign(['account_id']);
            $table->dropColumn(['account_id', 'payment_method']);
        });
    }
};
