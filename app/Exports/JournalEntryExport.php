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

class JournalEntryExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected $entries;
    protected array $filters;
    protected string $storeName;
    protected int $totalRowCount = 0;

    public function __construct($entries, array $filters = [], string $storeName = 'Semua Toko')
    {
        $this->entries = $entries;
        $this->filters = $filters;
        $this->storeName = $storeName;
    }

    public function title(): string
    {
        return 'Jurnal Transaksi';
    }

    public function headings(): array
    {
        $startDateFormatted = !empty($this->filters['start_date'])
            ? Carbon::parse($this->filters['start_date'])->translatedFormat('d M Y')
            : 'Awal';
        $endDateFormatted   = !empty($this->filters['end_date'])
            ? Carbon::parse($this->filters['end_date'])->translatedFormat('d M Y')
            : 'Sekarang';
        $sourceText         = !empty($this->filters['source_type_label'])
            ? $this->filters['source_type_label']
            : (!empty($this->filters['source_type']) ? ucfirst($this->filters['source_type']) : 'Semua Sumber');
        $searchText         = !empty($this->filters['search']) ? $this->filters['search'] : '-';

        return [
            ['LAPORAN JURNAL TRANSAKSI UMUM (GENERAL JOURNAL)'],
            ['Toko: ' . $this->storeName],
            ['Periode: ' . $startDateFormatted . ' s/d ' . $endDateFormatted],
            ['Filter Sumber: ' . $sourceText . ' | Pencarian: ' . $searchText],
            ['Waktu Cetak: ' . Carbon::now()->timezone(config('app.timezone', 'Asia/Jakarta'))->translatedFormat('d F Y H:i:s') . ' WIB'],
            [],
            [
                'No',
                'Tanggal',
                'No. Jurnal',
                'No. Referensi',
                'Sumber Transaksi',
                'Keterangan Transaksi',
                'Kode Akun',
                'Nama Akun',
                'Kategori Akun',
                'Debit (Rp)',
                'Kredit (Rp)',
                'Memo / Catatan Akun',
                'Status',
                'Petugas',
            ],
        ];
    }

    public function array(): array
    {
        $rows = [];
        $no = 1;
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($this->entries as $entry) {
            $entryDate = $entry->entry_date
                ? Carbon::parse($entry->entry_date)->format('Y-m-d')
                : ($entry->created_at ? Carbon::parse($entry->created_at)->format('Y-m-d') : '-');
            $entryNumber = $entry->entry_number ?: "JRN-{$entry->id}";
            $refNumber   = $entry->reference_number ?: '-';
            $sourceLabel = $entry->source_label;
            $description = $entry->description ?: '-';
            $statusText  = $entry->is_balanced ? 'Seimbang' : 'Tidak Seimbang';
            $userName    = $entry->user?->name ?? 'Sistem';

            foreach ($entry->items as $item) {
                $account = $item->account;
                $debitVal = (float) $item->debit;
                $creditVal = (float) $item->credit;

                $totalDebit += $debitVal;
                $totalCredit += $creditVal;

                $rows[] = [
                    $no++,
                    $entryDate,
                    $entryNumber,
                    $refNumber,
                    $sourceLabel,
                    $description,
                    $account?->code ?? '-',
                    $account?->name ?? 'Akun Terhapus',
                    $account?->type_label ?? '-',
                    $debitVal,
                    $creditVal,
                    $item->memo ?: '-',
                    $statusText,
                    $userName,
                ];
            }
        }

        $this->totalRowCount = count($rows);

        // Summary row (Row TOTAL)
        $balanceText = abs($totalDebit - $totalCredit) < 0.01 ? 'SEIMBANG (BALANCED)' : 'SELISIH (UNBALANCED)';
        $rows[] = [
            'TOTAL',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            '',
            $totalDebit,
            $totalCredit,
            '',
            $balanceText,
            '',
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        $headerRow = 7;
        $totalDataRows = $this->totalRowCount;
        $totalRow = $headerRow + $totalDataRows + 1; // +1 for the TOTAL row

        // Merge title and metadata rows across columns A to N
        for ($r = 1; $r <= 5; $r++) {
            $sheet->mergeCells("A{$r}:N{$r}");
        }

        // Column No fixed compact width
        $sheet->getColumnDimension('A')->setAutoSize(false);
        $sheet->getColumnDimension('A')->setWidth(7);

        // Title and meta typography
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14)->getColor()->setARGB('FF004D34');
        $sheet->getStyle('A2:A5')->getFont()->setBold(true)->setSize(10)->getColor()->setARGB('FF374151');

        // Header table styles (Row 7)
        $sheet->getStyle("A{$headerRow}:N{$headerRow}")->applyFromArray([
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
        $sheet->getStyle("H{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle("J{$headerRow}:K{$headerRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Data rows formatting
        if ($totalDataRows > 0) {
            $dataStart = $headerRow + 1;
            $dataEnd = $headerRow + $totalDataRows;

            // Center aligned: No, Tanggal, No. Jurnal, No. Ref, Sumber, Kode Akun, Kategori, Status, Petugas
            $sheet->getStyle("A{$dataStart}:E{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$dataStart}:G{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("I{$dataStart}:I{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("M{$dataStart}:N{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Left aligned: Keterangan, Nama Akun, Memo
            $sheet->getStyle("F{$dataStart}:F{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("H{$dataStart}:H{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle("L{$dataStart}:L{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Right aligned & number format for Debit and Kredit
            $sheet->getStyle("J{$dataStart}:K{$dataEnd}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("J{$dataStart}:K{$dataEnd}")->getNumberFormat()->setFormatCode('#,##0');
        }

        // Merge TOTAL label cells A to I
        $sheet->mergeCells("A{$totalRow}:I{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'TOTAL AKUMULASI PERIODE');

        // TOTAL row style
        $sheet->getStyle("A{$totalRow}:N{$totalRow}")->applyFromArray([
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
        $sheet->getStyle("J{$totalRow}:K{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->getStyle("J{$totalRow}:K{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("M{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Table borders (header through total row)
        $sheet->getStyle("A{$headerRow}:N{$totalRow}")->applyFromArray([
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
