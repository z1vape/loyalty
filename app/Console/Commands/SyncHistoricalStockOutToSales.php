<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StockMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SyncHistoricalStockOutToSales extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock-out:sync-sales {--dry-run : Pratinjau data yang akan disinkronkan tanpa menulis ke database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menyinkronkan data Stock Out historis ke tabel Sales agar muncul di Sales History dan Rekap Shift';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        $this->info($isDryRun 
            ? '🔍 [DRY-RUN] Memeriksa data Stock Out historis...' 
            : '🚀 Memulai sinkronisasi data Stock Out historis ke Sales History...');

        // Ambil semua referensi yang sudah ada di tabel sales
        $existingSaleRefs = Sale::withoutGlobalScopes()
            ->pluck('reference_number')
            ->filter()
            ->unique()
            ->toArray();

        // Cari Stock Movement tipe out yang belum memiliki data di tabel sales
        $query = StockMovement::withoutGlobalScopes()
            ->with(['product', 'user'])
            ->where('type', 'out');

        if (!empty($existingSaleRefs)) {
            $query->whereNotIn('reference_number', $existingSaleRefs);
        }

        $unsyncedMovements = $query->orderBy('movement_date', 'asc')->get();

        if ($unsyncedMovements->isEmpty()) {
            $this->info('✅ Seluruh data Stock Out sudah tersinkronisasi ke Sales History. Tidak ada data yang perlu disinkronkan.');
            return Command::SUCCESS;
        }

        // Kelompokkan movement berdasarkan reference_number
        $grouped = $unsyncedMovements->groupBy('reference_number');
        $totalTransactions = $grouped->count();
        $totalItems = $unsyncedMovements->count();

        $this->warn("Ditemukan {$totalTransactions} transaksi ({$totalItems} baris item) Stock Out yang belum masuk ke Sales History.");

        if ($isDryRun) {
            $tableRows = [];
            foreach ($grouped as $refNumber => $items) {
                $first = $items->first();
                $totalAmount = $items->sum(function ($it) {
                    if (isset($it->total_price) && is_numeric($it->total_price)) {
                        return (float) $it->total_price;
                    }
                    $gross = (float) $it->quantity * (float) ($it->selling_price ?? 0);
                    return max(0, $gross - (float) ($it->discount ?? 0));
                });

                $tableRows[] = [
                    'Ref' => $refNumber,
                    'Tanggal' => $first->movement_date,
                    'User' => $first->user?->name ?? "User #{$first->user_id}",
                    'Metode Bayar' => strtoupper($first->payment_method ?? 'CASH'),
                    'Jumlah Item' => $items->count(),
                    'Total Rp' => number_format($totalAmount, 0, ',', '.'),
                    'Status' => $first->status ?? 'approved',
                ];
            }

            $this->table(['Ref Number', 'Tanggal', 'User / Kasir', 'Metode Bayar', 'Jml Item', 'Total (Rp)', 'Status'], $tableRows);
            $this->info('ℹ️ Ini adalah simulasi (--dry-run). Tidak ada perubahan data yang disimpan.');
            return Command::SUCCESS;
        }

        $createdSalesCount = 0;
        $createdItemsCount = 0;

        DB::beginTransaction();
        try {
            foreach ($grouped as $refNumber => $items) {
                $first = $items->first();
                $storeId = $first->store_id ?? $first->product?->store_id ?? 1;
                $userId = $first->user_id ?? 1;
                $paymentMethodValue = strtolower($first->payment_method ?? 'cash');
                $paymentMethodUpper = strtoupper($paymentMethodValue);
                $userName = $first->user?->name ?? 'Staff';

                $customNote = !empty($first->notes) ? " | " . $first->notes : "";
                $saleNotes = "[Stock Out - {$userName}] Bayar: {$paymentMethodUpper}{$customNote}";

                $movementDateFormatted = Carbon::parse($first->movement_date)->format('Y-m-d');
                $timePart = $first->created_at ? Carbon::parse($first->created_at)->format('H:i:s') : '12:00:00';
                $saleDateTime = Carbon::parse($movementDateFormatted . ' ' . $timePart);

                $status = ($first->status === 'cancelled') ? 'cancelled' : 'completed';

                $sale = Sale::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'reference_number' => $refNumber,
                    'sale_date' => $movementDateFormatted,
                    'customer_name' => 'Pelanggan Umum',
                    'total_amount' => 0,
                    'notes' => $saleNotes,
                    'payment_method' => $paymentMethodValue,
                    'status' => $status,
                    'user_id' => $userId,
                    'created_at' => $saleDateTime,
                    'updated_at' => $saleDateTime,
                ]);

                // Ensure timestamps are not overridden by Eloquent defaults
                DB::table('sales')->where('id', $sale->id)->update([
                    'created_at' => $saleDateTime,
                    'updated_at' => $saleDateTime,
                ]);

                $totalAmount = 0;
                foreach ($items as $item) {
                    $sellingPrice = (float) ($item->selling_price ?? $item->product?->selling_price ?? 0);
                    $discount = (float) ($item->discount ?? 0);
                    
                    if (isset($item->total_price) && is_numeric($item->total_price)) {
                        $lineTotal = (float) $item->total_price;
                    } else {
                        $lineTotal = max(0, ((float) $item->quantity * $sellingPrice) - $discount);
                    }

                    $saleItem = $sale->items()->create([
                        'product_id' => $item->product_id,
                        'quantity' => (int) $item->quantity,
                        'selling_price' => $sellingPrice,
                        'discount' => $discount,
                        'subtotal' => $lineTotal,
                        'created_at' => $saleDateTime,
                        'updated_at' => $saleDateTime,
                    ]);

                    DB::table('sale_items')->where('id', $saleItem->id)->update([
                        'created_at' => $saleDateTime,
                        'updated_at' => $saleDateTime,
                    ]);

                    $totalAmount += $lineTotal;
                    $createdItemsCount++;
                }

                $sale->total_amount = $totalAmount;
                $sale->timestamps = false;
                $sale->save();
                DB::table('sales')->where('id', $sale->id)->update([
                    'created_at' => $saleDateTime,
                    'updated_at' => $saleDateTime,
                ]);

                $createdSalesCount++;
            }

            DB::commit();

            $this->info("🎉 Berhasil menyinkronkan {$createdSalesCount} transaksi penjualan ({$createdItemsCount} item) ke Sales History & Rekap Shift!");
            return Command::SUCCESS;

        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("❌ Gagal menyinkronkan data: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
