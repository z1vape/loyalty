<?php

namespace App\Console\Commands;

use App\Models\Expense;
use App\Models\Income;
use App\Models\JournalEntry;
use App\Models\Sale;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\AccountingService;
use Illuminate\Console\Command;

class SyncHistoricalAccountingJournals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounting:sync-history 
                            {--dry-run : Pratinjau data tanpa menulis jurnal ke database}
                            {--refresh : Hapus jurnal operasional otomatis sebelumnya dan generate ulang sesuai aturan akun terbaru}
                            {--store= : Sinkronkan hanya untuk Store ID tertentu}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyinkronkan data historis (penjualan, pembelian stok, pengeluaran, pemasukan, opname) ke jurnal akuntansi otomatis';

    /**
     * Execute the console command.
     */
    public function handle(AccountingService $accountingService): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $isRefresh = (bool) $this->option('refresh');
        $storeIdFilter = $this->option('store');

        $this->info($isDryRun
            ? '🔍 [DRY-RUN] Memeriksa data historis yang belum memiliki jurnal...'
            : ($isRefresh 
                ? '🔄 [REFRESH] Menghapus dan meregenerasi jurnal otomatis sesuai aturan terbaru...' 
                : '🚀 Memulai sinkronisasi data historis ke jurnal akuntansi...'));

        $storesQuery = Store::query();
        if ($storeIdFilter) {
            $storesQuery->where('id', $storeIdFilter);
        }
        $stores = $storesQuery->get();

        if ($stores->isEmpty()) {
            $this->warn('Tidak ada toko yang ditemukan.');
            return Command::SUCCESS;
        }

        $totalInitialBalancesSynced = 0;
        $totalSalesSynced = 0;
        $totalStockInSynced = 0;
        $totalExpensesSynced = 0;
        $totalIncomesSynced = 0;
        $totalAdjustmentsSynced = 0;

        foreach ($stores as $store) {
            $this->line("<comment>▶ Memproses Toko: {$store->name} (ID: {$store->id})</comment>");

            if (!$isDryRun) {
                // Ensure chart of accounts exists for this store
                $accountingService->ensureStoreAccounts($store->id);

                if ($isRefresh) {
                    $deletedCount = JournalEntry::withoutGlobalScopes()
                        ->where('store_id', $store->id)
                        ->whereIn('source_type', ['sale', 'expense', 'income', 'stock_movement'])
                        ->delete();
                    $this->warn("  - {$deletedCount} jurnal otomatis lama dibersihkan untuk diproses ulang.");
                }
            }

            // 0. Store Initial Balance (Saldo Awal Kas & Modal)
            if ((float) ($store->initial_balance ?? 0) > 0) {
                $hasInitJournal = JournalEntry::withoutGlobalScopes()
                    ->where('store_id', $store->id)
                    ->where('source_type', 'store_initial_balance')
                    ->where('source_id', $store->id)
                    ->exists();

                if (!$hasInitJournal) {
                    $this->info("  - Ditemukan Saldo Awal Toko (Rp " . number_format($store->initial_balance, 0, ',', '.') . ") belum memiliki jurnal.");
                    if (!$isDryRun) {
                        $accountingService->recordStoreInitialBalanceJournal($store);
                        $totalInitialBalancesSynced++;
                    }
                } else {
                    if (!$isDryRun) {
                        $accountingService->recordStoreInitialBalanceJournal($store);
                    }
                }
            }

            // 1. Sales
            $existingSaleIds = JournalEntry::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('source_type', 'sale')
                ->pluck('source_id')
                ->filter()
                ->toArray();

            $unsyncedSales = Sale::withoutGlobalScopes()
                ->where(function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                    if ($store->id == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
                })
                ->when(!empty($existingSaleIds), fn($q) => $q->whereNotIn('id', $existingSaleIds))
                ->orderBy('created_at', 'asc')
                ->get();

            $salesCount = $unsyncedSales->count();
            if ($salesCount > 0) {
                $this->info("  - Ditemukan {$salesCount} data penjualan belum memiliki jurnal.");
                if (!$isDryRun) {
                    foreach ($unsyncedSales as $sale) {
                        $accountingService->recordSaleJournal($sale);
                        $totalSalesSynced++;
                    }
                }
            }

            // 2. Stock In (Approved)
            $existingStockInIds = JournalEntry::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('source_type', 'stock_movement')
                ->pluck('source_id')
                ->filter()
                ->toArray();

            $unsyncedStockIn = StockMovement::withoutGlobalScopes()
                ->where(function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                    if ($store->id == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->where('type', 'in')
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', 'approved');
                })
                ->when(!empty($existingStockInIds), fn($q) => $q->whereNotIn('id', $existingStockInIds))
                ->orderBy('created_at', 'asc')
                ->get();

            $stockInCount = $unsyncedStockIn->count();
            if ($stockInCount > 0) {
                $this->info("  - Ditemukan {$stockInCount} data stok masuk disetujui belum memiliki jurnal.");
                if (!$isDryRun) {
                    foreach ($unsyncedStockIn as $movement) {
                        $accountingService->recordStockInJournal($movement);
                        $totalStockInSynced++;
                    }
                }
            }

            // 3. Expenses
            $existingExpenseIds = JournalEntry::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('source_type', 'expense')
                ->pluck('source_id')
                ->filter()
                ->toArray();

            $unsyncedExpenses = Expense::withoutGlobalScopes()
                ->where(function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                    if ($store->id == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->when(!empty($existingExpenseIds), fn($q) => $q->whereNotIn('id', $existingExpenseIds))
                ->orderBy('created_at', 'asc')
                ->get();

            $expensesCount = $unsyncedExpenses->count();
            if ($expensesCount > 0) {
                $this->info("  - Ditemukan {$expensesCount} data pengeluaran toko belum memiliki jurnal.");
                if (!$isDryRun) {
                    foreach ($unsyncedExpenses as $expense) {
                        $accountingService->recordExpenseJournal($expense);
                        $totalExpensesSynced++;
                    }
                }
            }

            // 4. Incomes
            $existingIncomeIds = JournalEntry::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('source_type', 'income')
                ->pluck('source_id')
                ->filter()
                ->toArray();

            $unsyncedIncomes = Income::withoutGlobalScopes()
                ->where(function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                    if ($store->id == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->when(!empty($existingIncomeIds), fn($q) => $q->whereNotIn('id', $existingIncomeIds))
                ->orderBy('created_at', 'asc')
                ->get();

            $incomesCount = $unsyncedIncomes->count();
            if ($incomesCount > 0) {
                $this->info("  - Ditemukan {$incomesCount} data pemasukan toko belum memiliki jurnal.");
                if (!$isDryRun) {
                    foreach ($unsyncedIncomes as $income) {
                        $accountingService->recordIncomeJournal($income);
                        $totalIncomesSynced++;
                    }
                }
            }

            // 5. Stock Adjustments
            $existingAdjIds = JournalEntry::withoutGlobalScopes()
                ->where('store_id', $store->id)
                ->where('source_type', 'stock_adjustment')
                ->pluck('source_id')
                ->filter()
                ->toArray();

            $unsyncedAdjustments = StockAdjustment::withoutGlobalScopes()
                ->where(function ($q) use ($store) {
                    $q->where('store_id', $store->id);
                    if ($store->id == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->whereNull('reversed_at')
                ->when(!empty($existingAdjIds), fn($q) => $q->whereNotIn('id', $existingAdjIds))
                ->orderBy('created_at', 'asc')
                ->get();

            $adjCount = $unsyncedAdjustments->count();
            if ($adjCount > 0) {
                $this->info("  - Ditemukan {$adjCount} data penyesuaian stok belum memiliki jurnal.");
                if (!$isDryRun) {
                    foreach ($unsyncedAdjustments as $adj) {
                        $accountingService->recordStockAdjustmentJournal($adj);
                        $totalAdjustmentsSynced++;
                    }
                }
            }
        }

        $this->newLine();
        if ($isDryRun) {
            $this->info('🏁 [DRY-RUN SELESAI] Pratinjau selesai. Jalankan tanpa flag `--dry-run` untuk memproses jurnal.');
        } else {
            $this->info("✅ [SINKRONISASI SELESAI] Rekap entri jurnal yang berhasil dibuat:");
            $this->table(
                ['Jenis Transaksi', 'Jumlah Jurnal Dibuat'],
                [
                    ['Saldo Awal Toko (Initial Balance)', $totalInitialBalancesSynced],
                    ['Penjualan (Sales POS)', $totalSalesSynced],
                    ['Stok Masuk / Pembelian (Stock In)', $totalStockInSynced],
                    ['Pengeluaran Toko (Expenses)', $totalExpensesSynced],
                    ['Pemasukan Toko (Incomes)', $totalIncomesSynced],
                    ['Penyesuaian Stok (Stock Adjustments)', $totalAdjustmentsSynced],
                ]
            );
        }

        return Command::SUCCESS;
    }
}
