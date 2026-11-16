<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\MovementDate;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class StockInMovementImport implements ToCollection, WithHeadingRow
{
    protected MovementDate $movementDate;
    protected int $userId;
    protected ?int $storeId;
    protected int $importedCount = 0;
    protected array $skippedRows = [];

    public function __construct(MovementDate $movementDate, int $userId, ?int $storeId = null)
    {
        $this->movementDate = $movementDate;
        $this->userId = $userId;
        $this->storeId = $storeId ?? session('active_store_id') ?? $movementDate->store_id;
    }

    public function collection(Collection $rows)
    {
        $dateFormatted = \Illuminate\Support\Carbon::parse($this->movementDate->date)->format('Y-m-d');

        DB::transaction(function () use ($rows, $dateFormatted) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // header is row 1

                // Normalize fields from possible variations in headers
                $sku = trim((string)($row['sku'] ?? $row['kode_sku'] ?? $row['kode_produk'] ?? $row['barcode'] ?? $row['item_code'] ?? ''));
                $quantity = (int)($row['quantity'] ?? $row['qty'] ?? $row['jumlah'] ?? $row['stok_masuk'] ?? $row['kuantitas'] ?? 0);
                $productName = trim((string)($row['product_name'] ?? $row['nama_produk'] ?? $row['name'] ?? $row['nama_barang'] ?? $row['nama'] ?? ''));
                $variant = trim((string)($row['variant'] ?? $row['varian'] ?? $row['variant_name'] ?? ''));
                
                $purchasePrice = null;
                if (isset($row['purchase_price']) && is_numeric($row['purchase_price'])) {
                    $purchasePrice = (float)$row['purchase_price'];
                } elseif (isset($row['harga_beli']) && is_numeric($row['harga_beli'])) {
                    $purchasePrice = (float)$row['harga_beli'];
                } elseif (isset($row['hpp']) && is_numeric($row['hpp'])) {
                    $purchasePrice = (float)$row['hpp'];
                } elseif (isset($row['harga_modal']) && is_numeric($row['harga_modal'])) {
                    $purchasePrice = (float)$row['harga_modal'];
                }

                $sellingPrice = null;
                if (isset($row['selling_price']) && is_numeric($row['selling_price'])) {
                    $sellingPrice = (float)$row['selling_price'];
                } elseif (isset($row['harga_jual']) && is_numeric($row['harga_jual'])) {
                    $sellingPrice = (float)$row['harga_jual'];
                }

                $notes = trim((string)($row['notes'] ?? $row['keterangan'] ?? $row['catatan'] ?? $row['note'] ?? ''));

                // Skip entirely empty row
                if ($sku === '' && $productName === '' && $quantity <= 0) {
                    continue;
                }

                // Validation: SKU is required
                if ($sku === '') {
                    $this->skippedRows[] = "Baris #{$rowNum}: Kolom SKU kosong.";
                    continue;
                }

                // Validation: Quantity must be > 0
                if ($quantity <= 0) {
                    $this->skippedRows[] = "Baris #{$rowNum} (SKU: {$sku}): Quantity harus lebih besar dari 0 (nilai: {$quantity}).";
                    continue;
                }

                // Look up product by SKU within active store scope
                $query = Product::where('sku', $sku);
                if ($this->storeId) {
                    $query->where('store_id', $this->storeId);
                }
                $product = $query->first();

                // If not found, auto-create if product name is provided
                if (!$product) {
                    if ($productName !== '') {
                        $product = Product::create([
                            'sku' => $sku,
                            'name' => $productName,
                            'variant' => $variant !== '' ? $variant : null,
                            'purchase_price' => ($purchasePrice !== null && $purchasePrice > 0) ? $purchasePrice : 0,
                            'selling_price' => ($sellingPrice !== null && $sellingPrice > 0) ? $sellingPrice : (($purchasePrice ?? 0) * 1.3),
                            'stock' => 0,
                            'min_stock' => 10,
                            'description' => 'Auto-created from Stock In Excel Import',
                            'store_id' => $this->storeId,
                        ]);
                    } else {
                        $this->skippedRows[] = "Baris #{$rowNum}: Produk SKU '{$sku}' tidak ditemukan di inventory. (Sertakan product_name jika ingin otomatis dibuat).";
                        continue;
                    }
                } else {
                    // If product exists but variant is empty and Excel provides a variant, update it
                    if (empty($product->variant) && $variant !== '') {
                        $product->variant = $variant;
                        $product->save();
                    }
                }

                // Generate unique reference number TXN-XXXXX
                do {
                    $refNumber = 'TXN-' . rand(10000, 99999);
                } while (StockMovement::where('reference_number', $refNumber)->exists());

                // Determine unit purchase price
                $unitPurchasePrice = ($purchasePrice !== null && $purchasePrice > 0)
                    ? (float) $purchasePrice
                    : (float) $product->purchase_price;

                $totalPrice = $unitPurchasePrice * $quantity;

                $rowSupplier = trim((string)($row['supplier'] ?? $row['supplier_name'] ?? $row['distributor'] ?? ''));
                $rowPaymentMethod = trim((string)($row['payment_method'] ?? $row['metode_pembayaran'] ?? ''));
                $rowDueDate = trim((string)($row['due_date'] ?? $row['jatuh_tempo'] ?? ''));

                $supplierName = $rowSupplier !== '' ? $rowSupplier : ($this->movementDate->supplier_name ?: null);
                if (!empty($supplierName)) {
                    \App\Models\Setting::firstOrCreate([
                        'type' => 'supplier',
                        'value' => $supplierName,
                    ]);
                }
                $paymentMethod = $rowPaymentMethod !== '' ? strtolower($rowPaymentMethod) : ($this->movementDate->payment_method ?: 'cash');
                $isTempo = in_array(strtolower($paymentMethod), ['tempo', 'hutang', 'credit']);
                $paymentStatus = $isTempo ? 'pending' : 'paid';
                $paidAmount = $isTempo ? 0 : $totalPrice;
                $dueDate = $rowDueDate !== '' ? $rowDueDate : (($isTempo && $this->movementDate->due_date) ? $this->movementDate->due_date->format('Y-m-d') : null);

                // Create StockMovement record
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $this->userId,
                    'reference_number' => $refNumber,
                    'movement_date' => $dateFormatted,
                    'type' => 'in',
                    'quantity' => $quantity,
                    'purchase_price' => $unitPurchasePrice,
                    'selling_price' => ($sellingPrice !== null && $sellingPrice > 0) ? $sellingPrice : $product->selling_price,
                    'discount' => 0,
                    'total_price' => $totalPrice,
                    'payment_method' => $paymentMethod,
                    'supplier_name' => $supplierName,
                    'payment_status' => $paymentStatus,
                    'due_date' => $dueDate,
                    'paid_amount' => $paidAmount,
                    'status' => 'draft',
                    'notes' => $notes ?: 'Imported via Excel Stock In',
                    'store_id' => $this->storeId,
                ]);

                // Update selling price if provided in Excel
                if ($sellingPrice !== null && $sellingPrice > 0) {
                    $product->selling_price = $sellingPrice;
                    $product->save();
                }

                // NOTE: Do NOT apply physical stock or Moving Average Cost (HPP) here.
                // Imported movements are created as 'draft' and will only adjust stock,
                // HPP, and accounting journals when explicitly Approved by the user.

                $this->importedCount++;
            }
        });
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }
}
