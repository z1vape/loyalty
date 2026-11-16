<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\Store;
use App\Services\LowStockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_returns_shortage_and_restock_cost_in_priority_order(): void
    {
        $store = Store::create(['name' => 'Low Stock Store']);
        session(['active_store_id' => $store->id]);
        Product::create(['store_id' => $store->id, 'sku' => 'LOW-1', 'name' => 'Low One', 'stock' => 2, 'min_stock' => 10, 'purchase_price' => 12]);
        Product::create(['store_id' => $store->id, 'sku' => 'OK-1', 'name' => 'Healthy', 'stock' => 20, 'min_stock' => 10, 'purchase_price' => 12]);
        Product::create(['store_id' => $store->id, 'sku' => 'LOW-2', 'name' => 'Low Two', 'stock' => 0, 'min_stock' => 3, 'purchase_price' => 5]);

        $items = app(LowStockReportService::class)->products();

        $this->assertSame(['LOW-1', 'LOW-2'], $items->pluck('sku')->all());
        $this->assertSame(8, $items->first()['shortage']);
        $this->assertSame(96.0, $items->first()['estimated_restock_cost']);
        $this->assertSame(11, $items->sum('shortage'));
    }

    public function test_out_of_stock_items_can_be_excluded_and_store_data_is_isolated(): void
    {
        $store = Store::create(['name' => 'Current']);
        $other = Store::create(['name' => 'Other']);
        session(['active_store_id' => $store->id]);
        Product::create(['store_id' => $store->id, 'sku' => 'ZERO', 'name' => 'Zero', 'stock' => 0, 'min_stock' => 2]);
        Product::create(['store_id' => $store->id, 'sku' => 'LOW', 'name' => 'Low', 'stock' => 1, 'min_stock' => 2]);
        Product::create(['store_id' => $other->id, 'sku' => 'OTHER', 'name' => 'Other', 'stock' => 0, 'min_stock' => 99]);

        $items = app(LowStockReportService::class)->products(null, false);

        $this->assertSame(['LOW'], $items->pluck('sku')->all());
    }
}
