<?php

namespace App\Console\Commands;

use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\AccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RestoreAutoCancelledSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:restore-cancelled
                            {--store= : Filter toko berdasarkan Nama Toko (misal "Belitung") atau Store ID}
                            {--apply : Terapkan pemulihan langsung ke database (tanpa opsi ini hanya pratinjau dry-run)}
                            {--sync-journals : Sinkronkan jurnal akuntansi untuk transaksi yang dipulihkan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memulihkan transaksi penjualan yang terbatalkan otomatis secara massal (tanpa metadata pembatalan kasir)';

    /**
     * Execute the console command.
     */
    public function handle(AccountingService $accountingService): int
    {
        $storeFilter = trim((string) $this->option('store'));
        $apply = (bool) $this->option('apply');
        $syncJournals = (bool) $this->option('sync-journals');

        $this->info($apply
            ? '🚀 [APPLY] Memulai pemulihan transaksi penjualan yang terbatalkan...'
            : '🔍 [DRY-RUN] Pratinjau transaksi penjualan yang terbatalkan (belum ada data yang diubah)...');

        $query = DB::table('sales')
            ->leftJoin('stores', 'sales.store_id', '=', 'stores.id')
            ->whereIn('sales.status', ['cancelled', 'canceled', 'batal', 'void'])
            ->whereNull('sales.cancelled_at')
            ->whereNull('sales.cancelled_by')
            ->where(function ($q) {
                $q->whereNull('sales.cancellation_reason')
                  ->orWhere('sales.cancellation_reason', '');
            })
            ->where(function ($q) {
                $q->whereNull('sales.notes')
                  ->orWhere(function ($n) {
                      $n->where('sales.notes', 'NOT LIKE', '%[DIBATALKAN%')
                        ->where('sales.notes', 'NOT LIKE', '%[BATAL%')
                        ->where('sales.notes', 'NOT LIKE', '%[VOID%');
                  });
            });

        if ($storeFilter !== '') {
            $query->where(function ($q) use ($storeFilter) {
                if (is_numeric($storeFilter)) {
                    $q->where('sales.store_id', (int) $storeFilter);
                } else {
                    $q->where('stores.name', 'LIKE', '%' . $storeFilter . '%');
                }
            });
            $this->line("📌 Filter Toko: <comment>{$storeFilter}</comment>");
        }

        $sales = $query->select([
            'sales.id',
            'sales.reference_number',
            'sales.store_id',
            'stores.name as store_name',
            'sales.total_amount',
            'sales.sale_date',
            'sales.created_at',
            'sales.updated_at',
        ])->get();

        if ($sales->isEmpty()) {
            $this->warn('Tidak ditemukan transaksi penjualan yang terbatalkan otomatis sesuai kriteria.');
            return Command::SUCCESS;
        }

        $totalSales = $sales->count();
        $totalAmount = (float) $sales->sum('total_amount');
        $ids = $sales->pluck('id')->all();
        $refs = $sales->pluck('reference_number')->filter()->unique()->values()->all();

        // Breakdown per store
        $storeGroups = $sales->groupBy(function ($item) {
            return ($item->store_name ?: 'Toko ID ' . ($item->store_id ?: '1'));
        });

        $tableData = [];
        foreach ($storeGroups as $storeName => $items) {
            $tableData[] = [
                'Toko' => $storeName,
                'Jumlah Transaksi' => number_format($items->count(), 0, ',', '.'),
                'Total Nilai (Rp)' => 'Rp ' . number_format($items->sum('total_amount'), 0, ',', '.'),
                'Tanggal Awal' => $items->min('sale_date') ?: substr($items->min('created_at'), 0, 10),
                'Tanggal Akhir' => $items->max('sale_date') ?: substr($items->max('created_at'), 0, 10),
                'Contoh No. Struk' => $items->take(3)->pluck('reference_number')->implode(', '),
            ];
        }

        $this->table(['Toko', 'Jumlah Transaksi', 'Total Nilai (Rp)', 'Tanggal Awal', 'Tanggal Akhir', 'Contoh No. Struk'], $tableData);
        $this->line("📊 Total Ditemukan: <info>{$totalSales} transaksi</info> (Rp " . number_format($totalAmount, 0, ',', '.') . ")");

        // Check matching stock movements
        $movQuery = DB::table('stock_movements')
            ->whereIn('reference_number', $refs)
            ->where('type', 'out')
            ->whereIn('status', ['cancelled', 'canceled', 'batal', 'void'])
            ->where(function ($q) {
                $q->whereNull('notes')
                  ->orWhere(function ($n) {
                      $n->where('notes', 'NOT LIKE', '%[DIBATALKAN%')
                        ->where('notes', 'NOT LIKE', '%[BATAL%')
                        ->where('notes', 'NOT LIKE', '%[VOID%');
                  });
            });

        $movIds = $movQuery->pluck('id')->all();
        $this->line("📦 Stock Movement terkait berstatus cancelled: <info>" . count($movIds) . " records</info>");

        if (!$apply) {
            $this->newLine();
            $this->warn('⚠️ Ini adalah simulasi (DRY-RUN). Tidak ada perubahan data yang disimpan.');
            $this->line('👉 Jalankan dengan menambahkan opsi <info>--apply</info> untuk memulihkan transaksi:');
            if ($storeFilter !== '') {
                $this->line("   <comment>php artisan sales:restore-cancelled --store=\"{$storeFilter}\" --apply</comment>");
            } else {
                $this->line("   <comment>php artisan sales:restore-cancelled --apply</comment>");
            }
            return Command::SUCCESS;
        }

        // Apply restoration
        Log::warning('sales:restore-cancelled started', [
            'store_filter' => $storeFilter,
            'sales_count' => count($ids),
            'total_amount' => $totalAmount,
            'movement_count' => count($movIds)
        ]);

        $this->info("Menyimpan perubahan ke database...");

        DB::beginTransaction();
        try {
            // 1. Update sales to completed
            foreach (array_chunk($ids, 500) as $chunk) {
                DB::table('sales')->whereIn('id', $chunk)->update([
                    'status' => 'completed',
                    'updated_at' => now(),
                ]);
            }

            // 2. Update stock movements back to approved
            if (!empty($movIds)) {
                foreach (array_chunk($movIds, 500) as $chunk) {
                    DB::table('stock_movements')->whereIn('id', $chunk)->update([
                        'status' => 'approved',
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::commit();
            $this->info("✅ Berhasil memulihkan {$totalSales} transaksi penjualan ke status 'completed'!");
            if (!empty($movIds)) {
                $this->info("✅ Berhasil mengembalikan " . count($movIds) . " stock movements ke status 'approved'!");
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("❌ Terjadi kegagalan saat memperbarui data: " . $e->getMessage());
            Log::error('sales:restore-cancelled failed', ['error' => $e->getMessage()]);
            return Command::FAILURE;
        }

        // 3. Sync journals if requested
        if ($syncJournals) {
            $this->info("Menerbitkan ulang jurnal akuntansi untuk transaksi yang dipulihkan...");
            $syncedCount = 0;
            $salesModels = Sale::withoutGlobalScopes()->whereIn('id', $ids)->get();
            foreach ($salesModels as $saleModel) {
                try {
                    $accountingService->recordSaleJournal($saleModel);
                    $syncedCount++;
                } catch (\Throwable $t) {
                    // ignore journal generation individual error
                }
            }
            $this->info("✅ Jurnal akuntansi berhasil disinkronkan ({$syncedCount} transaksi).");
        } else {
            $this->line("💡 Catatan: Untuk menerbitkan ulang jurnal akuntansi pembukuan, Anda dapat menjalankan:");
            $this->line("   <comment>php artisan accounting:sync-history</comment>");
            $this->line("   atau panggil endpoint <comment>/run-migration-auto-sync</comment>");
        }

        return Command::SUCCESS;
    }
}

