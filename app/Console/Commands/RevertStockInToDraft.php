<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MovementDate;
use App\Models\StockMovement;
use App\Models\Product;
use App\Services\AccountingService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RevertStockInToDraft extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:revert-to-draft {identifier : ID Movement Date atau format tanggal YYYY-MM-DD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengembalikan data Stock In yang telah approved menjadi draft, mengurangi stok fisik kembali, memulihkan HPP, dan menghapus jurnal transaksi terkait.';

    /**
     * Execute the console command.
     */
    public function handle(AccountingService $accountingService): int
    {
        $identifier = trim($this->argument('identifier'));

        // Look up by ID or Date
        $movementDateQuery = MovementDate::where('type', 'in');
        if (is_numeric($identifier)) {
            $movementDateQuery->where('id', (int) $identifier);
        } else {
            $parsedDate = Carbon::parse($identifier)->format('Y-m-d');
            $movementDateQuery->where('date', $parsedDate);
        }

        $movementDate = $movementDateQuery->first();

        if (!$movementDate) {
            $this->error("Movement Date Stock In dengan identifier '{$identifier}' tidak ditemukan.");
            return self::FAILURE;
        }

        $formattedDate = Carbon::parse($movementDate->date)->format('Y-m-d');
        $movements = StockMovement::where('movement_date', $formattedDate)
            ->where('type', 'in')
            ->where('status', 'approved')
            ->get();

        if ($movements->isEmpty()) {
            $this->info("Tidak ada transaksi Stock In berstatus 'approved' pada tanggal {$formattedDate}.");
            return self::SUCCESS;
        }

        $count = $movements->count();
        $this->warn("Ditemukan {$count} item mutasi approved pada tanggal {$formattedDate}.");

        if (!$this->confirm("Apakah Anda yakin ingin mengembalikan {$count} item mutasi menjadi DRAFT? (Stok akan dikurangi kembali dan jurnal transaksi akan dihapus)", true)) {
            $this->info('Operasi dibatalkan.');
            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            $revertedCount = 0;
            $bar = $this->output->createProgressBar($count);
            $bar->start();

            foreach ($movements as $movement) {
                if ($movement->supplierPayments()->exists()) {
                    DB::rollBack();
                    $this->newLine();
                    $this->error("Gagal: Movement #{$movement->reference_number} memiliki catatan pembayaran hutang/supplier.");
                    return self::FAILURE;
                }

                $product = Product::find($movement->product_id);
                if ($product) {
                    if ($product->stock < $movement->quantity) {
                        DB::rollBack();
                        $this->newLine();
                        $this->error("Gagal: Stok produk {$product->name} saat ini ({$product->stock} unit) tidak mencukupi untuk dikurangi {$movement->quantity} unit.");
                        return self::FAILURE;
                    }

                    $product->revertStockInMovingAverage((float) $movement->quantity, $movement->purchase_price);
                }

                $movement->status = 'draft';
                $movement->save();

                $accountingService->purgeJournal('stock_movement', $movement->id);

                $revertedCount++;
                $bar->advance();
            }

            DB::commit();
            $bar->finish();
            $this->newLine(2);

            $this->info("Berhasil mengembalikan {$revertedCount} item mutasi Stock In ke status DRAFT.");
            $this->line("- Stok produk telah disesuaikan kembali.");
            $this->line("- Jurnal transaksi terkait telah dibersihkan.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->newLine();
            $this->error('Terjadi kesalahan: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
