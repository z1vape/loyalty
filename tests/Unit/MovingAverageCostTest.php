<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MovingAverageCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_moving_average_calculation_on_stock_in(): void
    {
        $store = Store::create(['name' => 'Main Store']);

        // Case: Initial product bought: 10 pcs @ Rp 82.500
        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'ITEM-001',
            'name' => 'Barang Contoh',
            'stock' => 10,
            'min_stock' => 2,
            'purchase_price' => 82500.00,
            'selling_price' => 100000.00,
        ]);

        $this->assertEquals(10, $product->stock);
        $this->assertEquals(82500.00, (float) $product->purchase_price);

        // Case: User buys another 5 pcs @ Rp 85.000
        // Expected total value: (10 * 82.500) + (5 * 85.000) = 825.000 + 425.000 = 1.250.000
        // Total pcs: 15
        // Expected HPP: 1.250.000 / 15 = 83.333,33
        $product->applyStockInMovingAverage(5, 85000.00);

        $product->refresh();
        $this->assertEquals(15, $product->stock);
        $this->assertEquals(83333.33, round((float) $product->purchase_price, 2));
    }

    public function test_moving_average_when_stock_was_zero(): void
    {
        $store = Store::create(['name' => 'Main Store']);

        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'ITEM-002',
            'name' => 'Barang Baru',
            'stock' => 0,
            'min_stock' => 2,
            'purchase_price' => 0.00,
            'selling_price' => 50000.00,
        ]);

        // First purchase of 10 pcs @ 82.500
        $product->applyStockInMovingAverage(10, 82500.00);
        $product->refresh();

        $this->assertEquals(10, $product->stock);
        $this->assertEquals(82500.00, (float) $product->purchase_price);
    }

    public function test_revert_moving_average_calculation(): void
    {
        $store = Store::create(['name' => 'Main Store']);

        $product = Product::create([
            'store_id' => $store->id,
            'sku' => 'ITEM-003',
            'name' => 'Barang Revert',
            'stock' => 10,
            'min_stock' => 2,
            'purchase_price' => 82500.00,
            'selling_price' => 100000.00,
        ]);

        // Stock in 5 pcs @ 85.000 -> stock 15, HPP 83.333,33
        $product->applyStockInMovingAverage(5, 85000.00);
        $this->assertEquals(15, $product->stock);
        $this->assertEquals(83333.33, round((float) $product->purchase_price, 2));

        // Revert 5 pcs @ 85.000 -> stock 10, HPP should be back to 82.500
        $product->revertStockInMovingAverage(5, 85000.00);
        $this->assertEquals(10, $product->stock);
        $this->assertEquals(82500.00, round((float) $product->purchase_price, 2));
    }
}
