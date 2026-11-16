<?php

namespace App\Imports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure
{
    use SkipsFailures;

    public function model(array $row)
    {
        return new Product([
            'sku'            => $row['sku'],
            'name'           => $row['name'],
            'category'       => $row['category'] ?? null,
            'variant'        => $row['variant'] ?? null,
            'purchase_price' => $row['purchase_price'] ?? 0,
            'selling_price'  => $row['selling_price'] ?? 0,
            'stock'          => $row['stock'] ?? 0,
            'min_stock'      => $row['min_stock'] ?? 10,
            'description'    => $row['description'] ?? null,
        ]);
    }

    public function rules(): array
    {
        return [
            'sku' => 'required|unique:products,sku',
            'name' => 'required|string',
            'purchase_price' => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
            'stock' => 'nullable|integer',
            'min_stock' => 'nullable|integer',
        ];
    }
}
