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

class LowStockReportExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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
        return 'Stok Kritis ' . $this->storeName;
    }

    public function headings(): array
    {
        return [
            ['LAPORAN STOK KRITIS (LOW STOCK & HABIS)'],
            ['Toko / Cabang: ' . $this->storeName],
            ['Waktu Cetak: ' . date('d/m/Y H:i:s')],
            [],
            [
                'No',
                'SKU',
                'Nama Produk',
                'Kategori',
                'Stok Saat Ini',
                'Batas Minimum',
                'Kekurangan (Shortage)',
                'Harga Beli / HPP (Rp)',
                'Estimasi Biaya Restock (Rp)',
                'Status Stok',
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        $items = $this->data['items'] ?? [];
        $no = 1;

        foreach ($items as $item) {
            $status = ($item['stock'] <= 0) ? 'HABIS (Out of Stock)' : 'MENIPIS (Low Stock)';

            $rows[] = [
                $no++,
                $item['sku'] ?? '-',
                $item['name'] ?? '-',
                $item['category'] ?? 'Tanpa Kategori',
                (int) $item['stock'],
                (int) $item['min_stock'],
                (int) $item['shortage'],
                (float) $item['purchase_price'],
                (float) $item['estimated_restock_cost'],
                $status,
            ];
        }

        // Summary Row
        $rows[] = [
            'TOTAL',
            '',
            count($items) . ' Jenis Produk',
            '',
            '',
            '',
            (int) ($this->data['totalShortage'] ?? 0),
            '',
            (float) ($this->data['totalRestockCost'] ?? 0),
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $headerRow = 5;
        $count = count($this->data['items'] ?? []);
        $lastRow = $headerRow + $count + 1;

        // Title styles
        $sheet->mergeCells('A1:J1');
        $sheet->mergeCells('A2:J2');
        $sheet->mergeCells('A3:J3');

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2:A3')->getFont()->setSize(10)->setItalic(true);

        // Header table styles
        $sheet->getStyle("A{$headerRow}:J{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '006C49'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Alignment
        $sheet->getStyle("A6:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B6:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("C6:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("D6:D{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("E6:G{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("H6:I{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J6:J{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Formats
        $sheet->getStyle("E6:G{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("H6:I{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');

        // Total row style
        $sheet->getStyle("A{$lastRow}:J{$lastRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '006C49']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E6F4EA'],
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '006C49']],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => '006C49']],
            ],
        ]);

        // Grid borders
        $sheet->getStyle("A{$headerRow}:J{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'D0D7DE'],
                ],
            ],
        ]);

        return [];
    }
}

