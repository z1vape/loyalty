<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeletePurchaseOrderCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'order:delete
                            {identifier? : Nomor Order (misal PO-20261103-0001, PR-20261029-0001) atau ID}
                            {--all-rejected : Hapus semua order berstatus rejected}
                            {--force : Lewati konfirmasi}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Hapus Order Barang / Purchase Request beserta seluruh itemnya dari database';

    public function handle()
    {
        $identifier = $this->argument('identifier');
        $allRejected = $this->option('all-rejected');
        $force = $this->option('force');

        if (!$identifier && !$allRejected) {
            $this->error('Harap masukkan nomor order / ID atau gunakan opsi --all-rejected.');
            return 1;
        }

        if ($allRejected) {
            $rejectedOrders = PurchaseRequest::withoutGlobalScopes()->where('status', 'rejected')->get();
            if ($rejectedOrders->isEmpty()) {
                $this->info('Tidak ada order dengan status rejected.');
                return 0;
            }

            if (!$force && !$this->confirm("Ditemukan {$rejectedOrders->count()} order rejected. Hapus semuanya?")) {
                $this->info('Dibatalkan.');
                return 0;
            }

            DB::beginTransaction();
            try {
                $count = 0;
                foreach ($rejectedOrders as $order) {
                    $order->items()->delete();
                    $order->delete();
                    $count++;
                }
                DB::commit();
                $this->info("Berhasil menghapus {$count} order rejected.");
                return 0;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error('Gagal menghapus: ' . $e->getMessage());
                return 1;
            }
        }

        $order = PurchaseRequest::withoutGlobalScopes()
            ->where('id', $identifier)
            ->orWhere('pr_number', $identifier)
            ->first();

        // Juga periksa tabel purchase_orders jika nomor yang dimasukkan adalah PO supplier
        if (!$order) {
            $po = PurchaseOrder::where('id', $identifier)
                ->orWhere('po_number', $identifier)
                ->first();
            if ($po) {
                if (!$force && !$this->confirm("Hapus Purchase Order Supplier #{$po->po_number}?")) {
                    $this->info('Dibatalkan.');
                    return 0;
                }
                $poNumber = $po->po_number;
                $po->items()->delete();
                $po->delete();
                $this->info("Purchase Order Supplier #{$poNumber} berhasil dihapus.");
                return 0;
            }

            $this->error("Order dengan ID atau nomor '{$identifier}' tidak ditemukan.");
            return 1;
        }

        if (!$force && !$this->confirm("Hapus Order Barang #{$order->pr_number} beserta seluruh itemnya?")) {
            $this->info('Dibatalkan.');
            return 0;
        }

        DB::beginTransaction();
        try {
            $prNumber = $order->pr_number;
            $order->items()->delete();
            $order->delete();
            DB::commit();

            $this->info("Order Barang #{$prNumber} berhasil dihapus.");
            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Gagal menghapus order: ' . $e->getMessage());
            return 1;
        }
    }
}

