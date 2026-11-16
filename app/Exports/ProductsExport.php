<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $products;

    public function __construct($products)
    {
        $this->products = $products;
    }

    public function collection()
    {
        return $this->products;
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Name',
            'Category',
            'Variant',
            'Purchase Price',
            'Selling Price',
            'Stock',
            'Min Stock',
            'Description',
        ];
    }

    public function map($product): array
    {
        return [
            $product->sku,
            $product->name,
            $product->category,
            $product->variant,
            $product->purchase_price,
            $product->selling_price,
            $product->stock,
            $product->min_stock,
            $product->description,
        ];
    }
}
