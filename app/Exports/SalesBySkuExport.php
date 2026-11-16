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

class SalesBySkuExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $data;
    protected string $storeName;

    public function __construct(array $data, string $storeName = 'Semua Toko')
    {
        $this->data = $data;
        $this->storeName = $storeName;
    }

    public function title(): string
    {
        return 'Penjualan per SKU';
    }

    public function headings(): array
    {
        $startDateFormatted = Carbon::parse($this->data['startDate'])->translatedFormat('d M Y');
        $endDateFormatted   = Carbon::parse($this->data['endDate'])->translatedFormat('d M Y');
        $categoryText       = !empty($this->data['category']) ? $this->data['category'] : 'Semua Kategori';
        $searchText         = !empty($this->data['search']) ? $this->data['search'] : 'Semua Produk';

        return [
            ['LAPORAN PENJUALAN PER SKU (PRODUK)'],
            ['Toko: ' . $this->storeName],
            ['Periode: ' . $startDateFormatted . ' s/d ' . $endDateFormatted],
            ['Filter Kategori: ' . $categoryText],
            ['Filter Pencarian: ' . $searchText],
            ['Waktu Cetak: ' . Carbon::now()->translatedFormat('d F Y H:i:s')],
            [],
            [
                'No',
                'SKU',
                'Nama Produk',
                'Varian',
                'Kategori',
                'Qty Terjual',
                'Total Harga Beli (Rp)',
                'Total Diskon (Rp)',
                'Total Penjualan (Rp)',
                'Laba Bersih (Rp)',
                'Margin Laba (%)',
                '% Kontribusi Penjualan',
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;

        foreach ($this->data['items'] as $item) {
            $rows[] = [
                $no++,
                $item['sku'],
                $item['name'],
                $item['variant'] ?? '-',
                $item['category'],
                $item['qty'],
                $item['total_purchase_price'],
                $item['total_discount'],
                $item['total_amount'],
                $item['profit'],
                $item['margin'] . '%',
                $item['contribution'] . '%',
            ];
        }

        // Summary row
        $overallMargin = $this->data['grandTotal'] > 0
            ? round(($this->data['totalProfit'] / $this->data['grandTotal']) * 100, 1)
            : 0.0;

        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            $this->data['totalQty'],
            $this->data['totalPurchaseCost'],
            $this->data['totalDiscount'],
            $this->data['grandTotal'],
            $this->data['totalProfit'],
            $overallMargin . '%',
            '100%',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 8;
        $totalItems = count($this->data['items']);
        $totalRow = $headerRow + $totalItems + 1; // +1 for the TOTAL row

        // Merge title and meta rows across table columns so they do not blow up column A width
        for ($r = 1; $r <= 6; $r++) {
            $sheet->mergeCells("A{$r}:L{$r}");
        }

        // Set fixed compact width for column 'No' and disable auto-size for it
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(7);

        // Title and meta styles
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A6')->getFont()->setBold(true)->setSize(10);

        // Header table styles (Row 8)
        $sheet->getStyle("A{$headerRow}:L{$headerRow}")->applyFromArray([
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
        $sheet->getStyle("B{$headerRow}:C{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("F{$headerRow}:L{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Data rows alignments & formatting
        if ($totalItems > 0) {
            $dataStart = $headerRow + 1;
            $dataEnd = $headerRow + $totalItems;

            // Center No, Variant, Category
            $sheet->getStyle("A{$dataStart}:A{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D{$dataStart}:E{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Left align SKU & Name
            $sheet->getStyle("B{$dataStart}:C{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Right-align numbers & percentages
            $sheet->getStyle("F{$dataStart}:L{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Number formatting for currencies and qty
            $sheet->getStyle("F{$dataStart}:F{$dataEnd}")->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle("G{$dataStart}:J{$dataEnd}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Merge TOTAL label cells A to E
        $sheet->mergeCells("A{$totalRow}:E{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');

        // TOTAL row style
        $sheet->getStyle("A{$totalRow}:L{$totalRow}")->applyFromArray([
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
        $sheet->getStyle("F{$totalRow}:L{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("F{$totalRow}:J{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Table borders (header through total row)
        $sheet->getStyle("A{$headerRow}:L{$totalRow}")->applyFromArray([
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
