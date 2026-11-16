<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class TopSuppliersExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
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
        $mode = $this->data['mode'] ?? 'month';
        if ($mode === 'year') {
            return 'Rekap Supplier ' . ($this->data['startYear'] ?? '') . '-' . ($this->data['endYear'] ?? '');
        }
        return 'Rekap Supplier ' . ($this->data['year'] ?? '');
    }

    public function headings(): array
    {
        $mode = $this->data['mode'] ?? 'month';

        if ($mode === 'year') {
            $yearsList = $this->data['yearsList'] ?? [];
            $header = ['Rank', 'Nama Supplier'];
            foreach ($yearsList as $yr) {
                $header[] = (string) $yr;
            }
            return [$header];
        }

        return [
            [
                'Rank',
                'Nama Supplier',
                'January',
                'Febuary',
                'March',
                'April',
                'May',
                'June',
                'July',
                'August',
                'September',
                'October',
                'November',
                'December',
            ]
        ];
    }

    public function array(): array
    {
        $rows = [];
        $mode = $this->data['mode'] ?? 'month';
        $topSuppliers = $this->data['topSuppliers'] ?? collect();

        if ($mode === 'year') {
            $yearsList = $this->data['yearsList'] ?? [];

            foreach ($topSuppliers as $item) {
                $row = [
                    $item['rank'],
                    $item['supplier_name'],
                ];

                foreach ($yearsList as $yr) {
                    $val = $item['years'][$yr] ?? 0;
                    $row[] = (float) $val;
                }

                $rows[] = $row;
            }

            // Summary TOTAL PEMBELIAN Row
            $yearlyTotals = $this->data['yearlyTotals'] ?? [];
            $totalRow = [
                'TOTAL PEMBELIAN',
                '',
            ];
            foreach ($yearsList as $yr) {
                $totalRow[] = (float) ($yearlyTotals[$yr] ?? 0);
            }
            $rows[] = $totalRow;

            return $rows;
        }

        // Mode Per Bulan (12 Bulan)
        foreach ($topSuppliers as $item) {
            $row = [
                $item['rank'],
                $item['supplier_name'],
            ];

            for ($m = 1; $m <= 12; $m++) {
                $val = $item['months'][$m] ?? 0;
                $row[] = $val > 0 ? (float) $val : '';
            }

            $rows[] = $row;
        }

        // Summary TOTAL PEMBELIAN Row
        $monthlyTotals = $this->data['monthlyTotals'] ?? array_fill(1, 12, 0);
        $totalRow = [
            'TOTAL PEMBELIAN',
            '',
        ];
        for ($m = 1; $m <= 12; $m++) {
            $totalRow[] = (float) ($monthlyTotals[$m] ?? 0);
        }
        $rows[] = $totalRow;

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $mode = $this->data['mode'] ?? 'month';
        $headerRow = 1;
        $topSuppliersCount = count($this->data['topSuppliers'] ?? []);
        $lastRow = $headerRow + $topSuppliersCount + 1;

        if ($mode === 'year') {
            $numCols = 2 + count($this->data['yearsList'] ?? []);
        } else {
            $numCols = 14; // A..N
        }
        $lastColLetter = Coordinate::stringFromColumnIndex($numCols);

        // Header styles
        $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FFFFFF'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Alignment for rank and supplier
        $sheet->getStyle("A2:A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("B2:B{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Numeric columns C onwards
        $sheet->getStyle("C2:{$lastColLetter}{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        if ($mode === 'year') {
            $sheet->getStyle("C2:{$lastColLetter}" . ($lastRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        } else {
            $sheet->getStyle("C2:{$lastColLetter}" . ($lastRow - 1))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Merge cells for 'TOTAL PEMBELIAN' label across column A and B
        $sheet->mergeCells("A{$lastRow}:B{$lastRow}");
        $sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // TOTAL PEMBELIAN row formatting with Excel Light Olive Green #A9D08E
        $sheet->getStyle("A{$lastRow}:{$lastColLetter}{$lastRow}")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'A9D08E'],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '385723'],
                ],
                'bottom' => [
                    'borderStyle' => Border::BORDER_MEDIUM,
                    'color' => ['rgb' => '385723'],
                ],
            ],
        ]);

        // Grid borders for all cells
        $sheet->getStyle("A1:{$lastColLetter}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'A6A6A6'],
                ],
            ],
        ]);

        return [];
    }
}
