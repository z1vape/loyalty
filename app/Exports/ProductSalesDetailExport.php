<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class ProductSalesDetailExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $movements;
    protected array $filters;
    protected string $storeName;
    protected int $totalRowCount = 0;

    public function __construct($movements, array $filters, string $storeName = 'Semua Toko')
    {
        $this->movements = $movements;
        $this->filters = $filters;
        $this->storeName = $storeName;
    }

    public function title(): string
    {
        return 'Detail Penjualan Produk';
    }

    public function headings(): array
    {
        $startDateFormatted = !empty($this->filters['startDate'])
            ? Carbon::parse($this->filters['startDate'])->translatedFormat('d M Y')
            : '-';
        $endDateFormatted   = !empty($this->filters['endDate'])
            ? Carbon::parse($this->filters['endDate'])->translatedFormat('d M Y')
            : '-';
        $categoryText       = !empty($this->filters['category']) ? $this->filters['category'] : 'Semua Kategori';
        $searchText         = !empty($this->filters['search']) ? $this->filters['search'] : 'Semua Produk';

        return [
            ['LAPORAN DETAIL PENJUALAN PRODUK (BREAKDOWN PER UNIT)'],
            ['Toko: ' . $this->storeName],
            ['Periode: ' . $startDateFormatted . ' s/d ' . $endDateFormatted],
            ['Filter Kategori: ' . $categoryText],
            ['Filter Pencarian: ' . $searchText],
            ['Waktu Cetak: ' . Carbon::now()->translatedFormat('d F Y H:i:s')],
            [],
            [
                'No',
                'Tanggal',
                'Waktu',
                'No. Referensi',
                'SKU',
                'Nama Produk',
                'Varian',
                'Kategori',
                'Qty',
                'Harga Beli (Rp)',
                'Harga Jual (Rp)',
                'Diskon (Rp)',
                'Total Penjualan (Rp)',
                'Laba Bersih (Rp)',
                'Metode Bayar',
                'Kasir',
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        $totalQty = 0;
        $totalPurchase = 0;
        $totalSelling = 0;
        $totalDiscount = 0;
        $totalSales = 0;
        $totalProfit = 0;

        foreach ($this->movements as $m) {
            $qty = (int) $m->quantity;
            if ($qty <= 0) {
                continue;
            }

            $product = $m->product;
            $unitPurchase = (float) ($m->purchase_price ?? $product?->purchase_price ?? 0);
            $unitSelling  = (float) ($m->selling_price ?? $product?->selling_price ?? 0);
            $unitDiscount = $qty > 0 ? (float) ($m->discount ?? 0) / $qty : 0;
            $unitTotal    = $qty > 0
                ? ($m->total_price !== null ? ((float) $m->total_price / $qty) : ($unitSelling - $unitDiscount))
                : 0;
            $unitProfit   = $unitTotal - $unitPurchase;

            // Breakdown row-by-row per quantity unit
            // Example: 2 qty CT FOOM becomes 2 rows of CT FOOM (1 qty each)
            for ($i = 0; $i < $qty; $i++) {
                $rows[] = [
                    $no++,
                    $m->movement_date ? Carbon::parse($m->movement_date)->format('d/m/Y') : '-',
                    $m->created_at ? $m->created_at->format('H:i:s') : '-',
                    $m->reference_number ?? '-',
                    $product?->sku ?? '-',
                    $product?->name ?? 'Produk Dihapus',
                    $product?->variant ?? '-',
                    !empty($product?->category) ? $product->category : 'Tanpa Kategori',
                    1,
                    $unitPurchase,
                    $unitSelling,
                    $unitDiscount,
                    $unitTotal,
                    $unitProfit,
                    strtoupper($m->payment_method ?? '-'),
                    $m->user?->name ?? '-',
                ];

                $totalQty += 1;
                $totalPurchase += $unitPurchase;
                $totalSelling += $unitSelling;
                $totalDiscount += $unitDiscount;
                $totalSales += $unitTotal;
                $totalProfit += $unitProfit;
            }
        }

        $this->totalRowCount = count($rows);

        // Summary row
        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $totalQty,
            $totalPurchase,
            $totalSelling,
            $totalDiscount,
            $totalSales,
            $totalProfit,
            '',
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 8;
        $totalDataRows = $this->totalRowCount;
        $totalRow = $headerRow + $totalDataRows + 1; // +1 for the TOTAL row

        // Merge title and meta rows across table columns (A to P) so they don't widen column A
        for ($r = 1; $r <= 6; $r++) {
            $sheet->mergeCells("A{$r}:P{$r}");
        }

        // Set fixed compact width for column 'No' and disable auto-size for it
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(7);

        // Title and meta styles
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A6')->getFont()->setBold(true)->setSize(10);

        // Header table styles (Row 8)
        $sheet->getStyle("A{$headerRow}:P{$headerRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF006C49'], // Brand emerald green
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Specific alignments for header row
        $sheet->getStyle("F{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("J{$headerRow}:N{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Data rows alignments & formatting
        if ($totalDataRows > 0) {
            $dataStart = $headerRow + 1;
            $dataEnd = $headerRow + $totalDataRows;

            // Center: No, Tanggal, Waktu, No. Ref, SKU, Varian, Kategori, Qty, Metode Bayar, Kasir
            $sheet->getStyle("A{$dataStart}:E{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$dataStart}:I{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("O{$dataStart}:P{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Left align: Nama Produk
            $sheet->getStyle("F{$dataStart}:F{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Right align: Harga Beli, Harga Jual, Diskon, Total, Laba
            $sheet->getStyle("J{$dataStart}:N{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Number formatting for qty and currencies
            $sheet->getStyle("I{$dataStart}:I{$dataEnd}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("J{$dataStart}:N{$dataEnd}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Merge TOTAL label cells A to H
        $sheet->mergeCells("A{$totalRow}:H{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');

        // TOTAL row style
        $sheet->getStyle("A{$totalRow}:P{$totalRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['argb' => 'FF004D34'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FFE8F5E9'], // Soft highlight green
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("I{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("J{$totalRow}:N{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J{$totalRow}:N{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Table borders (header through total row)
        $sheet->getStyle("A{$headerRow}:P{$totalRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFD1D5DB'],
                ],
            ],
        ]);

        return [];
    }
}
