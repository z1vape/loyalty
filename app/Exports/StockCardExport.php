<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockCardExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $product;
    protected $rows;
    protected $openingBalance;
    protected $dateFrom;
    protected $dateTo;
    protected $storeName;

    public function __construct($product, array $rows, float $openingBalance, ?string $dateFrom, ?string $dateTo, ?string $storeName = null)
    {
        $this->product = $product;
        $this->rows = $rows;
        $this->openingBalance = $openingBalance;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->storeName = $storeName ?: ($product->store ? $product->store->name : 'Semua Toko');
    }

    public function title(): string
    {
        return 'Kartu Stok - ' . substr($this->product->sku, 0, 20);
    }

    public function headings(): array
    {
        return [
            ['KARTU STOK PRODUK (STOCK LEDGER)'],
            ['Toko: ' . $this->storeName],
            ['Produk: ' . $this->product->name . ' (' . $this->product->sku . ')'],
            ['Periode: ' . ($this->dateFrom ?: 'Awal') . ' s/d ' . ($this->dateTo ?: 'Sekarang')],
            [],
            [
                'No',
                'Tanggal',
                'Waktu',
                'No. Referensi',
                'Tipe Transaksi',
                'Masuk (Qty)',
                'Keluar (Qty)',
                'Saldo Akhir (Qty)',
                'Harga Satuan (Rp)',
                'Total Nilai (Rp)',
                'Metode Bayar',
                'Petugas',
                'Keterangan',
            ],
        ];
    }

    public function array(): array
    {
        $data = [];
        $no = 1;

        // Row 0: Opening Balance
        $data[] = [
            '-',
            $this->dateFrom ?: '-',
            '-',
            '-',
            'SALDO AWAL PERIODE',
            0,
            0,
            $this->openingBalance,
            $this->product->purchase_price,
            $this->openingBalance * $this->product->purchase_price,
            '-',
            '-',
            'Saldo awal sebelum periode pencarian',
        ];

        $totalIn = 0;
        $totalOut = 0;
        $totalValueIn = 0;
        $totalValueOut = 0;

        foreach ($this->rows as $item) {
            $m = $item['movement'];
            $qtyIn = $item['qty_in'];
            $qtyOut = $item['qty_out'];
            $running = $item['running_balance'];

            $totalIn += $qtyIn;
            $totalOut += $qtyOut;

            $unitPrice = ($m->type === 'in')
                ? ($m->purchase_price ?: $this->product->purchase_price)
                : ($m->selling_price ?: $this->product->selling_price);

            $totalVal = $m->total_price ?: (($qtyIn > 0 ? $qtyIn : $qtyOut) * $unitPrice);

            $typeLabel = match ($m->type) {
                'in' => 'Masuk (In)',
                'out' => 'Keluar (Out)',
                'adjustment' => 'Penyesuaian (Adj)',
                default => ucfirst($m->type),
            };

            $data[] = [
                $no++,
                \Carbon\Carbon::parse($m->movement_date)->format('Y-m-d'),
                $m->created_at ? $m->created_at->format('H:i:s') : '-',
                $m->reference_number ?: '-',
                $typeLabel,
                $qtyIn > 0 ? $qtyIn : 0,
                $qtyOut > 0 ? $qtyOut : 0,
                $running,
                $unitPrice,
                $totalVal,
                strtoupper($m->payment_method ?: '-'),
                $m->user ? $m->user->name : '-',
                $m->notes ?: '-',
            ];
        }

        // Summary Row at the bottom
        $data[] = [];
        $data[] = [
            'TOTAL / RINGKASAN',
            '',
            '',
            '',
            'TOTAL MUTASI',
            $totalIn,
            $totalOut,
            $this->rows ? end($this->rows)['running_balance'] : $this->openingBalance,
            '',
            '',
            '',
            '',
            '',
        ];

        return $data;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true]],
            3 => ['font' => ['bold' => true]],
            4 => ['font' => ['bold' => true]],
            6 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF006C49'],
                ],
            ],
            7 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FFF0FDF4'],
                ],
            ],
        ];
    }
}
