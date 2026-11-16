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

class StorePerformanceExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function title(): string
    {
        return 'BI Performa Toko';
    }

    public function headings(): array
    {
        return [
            ['ZONA 1 VAPE - EXECUTIVE BI: LAPORAN PERFORMA SELURUH CABANG TOKO'],
            ['Periode: ' . ($this->data['filterLabel'] ?? 'Semua') . ' | Tanggal Cetak: ' . date('d/m/Y H:i')],
            [],
            [
                'Rank',
                'Nama Cabang / Toko',
                'Total Omzet (Rp)',
                'Kontribusi Omzet (%)',
                'HPP / Modal (Rp)',
                'Laba Kotor (Rp)',
                'Gross Margin (%)',
                'Beban Toko (Rp)',
                'Pemasukan Lain (Rp)',
                'Laba Bersih (Rp)',
                'Net Margin (%)',
                'Jml Transaksi',
                'AOV / Rata-rata Belanja (Rp)',
                'Item Terjual (Qty)',
                'Pembayaran Tunai (Rp)',
                'Pembayaran Transfer (Rp)',
                'Pembayaran QRIS/EDC (Rp)',
                'Valuasi Stok Aset (Rp)',
                'Total Unit Stok',
                'Stok Kritis (Item)',
                'Saldo Toko Berjalan (Rp)',
                'Kasir Terbaik'
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        $storesData = $this->data['storesData'] ?? [];

        foreach ($storesData as $item) {
            $rows[] = [
                $item['rank'],
                $item['store_name'],
                $item['total_sales'],
                round($item['sales_contribution'], 2) . '%',
                $item['total_cogs'],
                $item['gross_profit'],
                round($item['gross_margin'], 2) . '%',
                $item['total_expenses'],
                $item['total_incomes'],
                $item['net_profit'],
                round($item['net_margin'], 2) . '%',
                $item['trx_count'],
                round($item['aov']),
                $item['total_units_sold'],
                $item['payment_cash'],
                $item['payment_transfer'],
                $item['payment_qris'],
                $item['inventory_valuation'],
                $item['total_stock_units'],
                $item['low_stock_count'],
                $item['ending_balance'],
                $item['top_cashier_name'] ?? '-'
            ];
        }

        // Summary Row
        $totals = $this->data['totals'] ?? [];
        $rows[] = [
            'TOTAL',
            'KONSOLIDASI SELURUH CABANG',
            $totals['total_sales'] ?? 0,
            '100%',
            $totals['total_cogs'] ?? 0,
            $totals['gross_profit'] ?? 0,
            round($totals['gross_margin'] ?? 0, 2) . '%',
            $totals['total_expenses'] ?? 0,
            $totals['total_incomes'] ?? 0,
            $totals['net_profit'] ?? 0,
            round($totals['net_margin'] ?? 0, 2) . '%',
            $totals['trx_count'] ?? 0,
            round($totals['aov'] ?? 0),
            $totals['total_units_sold'] ?? 0,
            $totals['payment_cash'] ?? 0,
            $totals['payment_transfer'] ?? 0,
            $totals['payment_qris'] ?? 0,
            $totals['inventory_valuation'] ?? 0,
            $totals['total_stock_units'] ?? 0,
            $totals['low_stock_count'] ?? 0,
            $totals['total_ending_balance'] ?? 0,
            '-'
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->mergeCells('A1:V1');
        $sheet->mergeCells('A2:V2');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('006C49'));
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('555555'));

        $sheet->getStyle('A4:V4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '006C49']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);

        $lastRow = count($this->data['storesData'] ?? []) + 5;

        // Total Row Styling
        $sheet->getStyle("A{$lastRow}:V{$lastRow}")->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F4EA']
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE],
            ]
        ]);

        // Currency Formatting
        $currencyCols = ['C', 'E', 'F', 'H', 'I', 'J', 'M', 'O', 'P', 'Q', 'R', 'U'];
        foreach ($currencyCols as $col) {
            $sheet->getStyle("{$col}5:{$col}{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0');
        }

        return [];
    }
}

