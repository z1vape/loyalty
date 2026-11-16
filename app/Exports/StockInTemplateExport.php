<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StockInTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles
{
    public function array(): array
    {
        return [
            [
                'CONTOH-SKU-001',
                'Kopi Susu Gula Aren',
                '250ml',
                50,
                15000,
                25000,
                'Pengiriman Supplier Batch 1',
            ],
            [
                'CONTOH-SKU-002',
                'Kopi Susu Gula Aren',
                '500ml',
                30,
                28000,
                45000,
                'Pengiriman Supplier Batch 1',
            ],
            [
                'CONTOH-SKU-003',
                'Croissant Butter Premium',
                '',
                20,
                12000,
                22000,
                'Barang Masuk Pagi (Tanpa Varian)',
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'sku',
            'product_name',
            'variant',
            'quantity',
            'purchase_price',
            'selling_price',
            'notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
            ],
        ];
    }
}
