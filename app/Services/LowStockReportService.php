<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class LowStockReportService
{
    /**
     * Return products whose current stock is at or below their configured threshold.
     * The Product store scope keeps this report isolated to the active store.
     */
    public function products(?string $category = null, bool $includeOutOfStock = true): Collection
    {
        return Product::query()
            ->when($category !== null && $category !== '', fn ($query) => $query->where('category', $category))
            ->whereColumn('stock', '<=', 'min_stock')
            ->when(!$includeOutOfStock, fn ($query) => $query->where('stock', '>', 0))
            ->orderByRaw('(min_stock - stock) DESC')
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
                'category' => $product->category,
                'stock' => (int) $product->stock,
                'min_stock' => (int) $product->min_stock,
                'shortage' => max(0, (int) $product->min_stock - (int) $product->stock),
                'purchase_price' => (float) $product->purchase_price,
                'estimated_restock_cost' => round(max(0, (int) $product->min_stock - (int) $product->stock) * (float) $product->purchase_price, 2),
            ]);
    }
}
