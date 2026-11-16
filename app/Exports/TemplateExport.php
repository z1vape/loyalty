<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TemplateExport implements FromArray, WithHeadings
{
    public function array(): array
    {
        return [
            [
                'EXAMPLE-SKU', 
                'Example Product', 
                'Electronics', 
                'Default', 
                10000, 
                20000, 
                50, 
                10, 
                'This is an example product.'
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'sku', 
            'name', 
            'category', 
            'variant', 
            'purchase_price', 
            'selling_price', 
            'stock', 
            'min_stock', 
            'description'
        ];
    }
}
