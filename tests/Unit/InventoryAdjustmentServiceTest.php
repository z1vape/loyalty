<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Services\InventoryAdjustmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use LogicException;
use Tests\TestCase;

class InventoryAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_positive_adjustment_records_snapshot_and_updates_stock(): void
    {
        $product = $this->makeProduct(10);
        $adjustment = app(InventoryAdjustmentService::class)->adjust($product, 5, 'physical_count');
        $this->assertSame(15, $product->fresh()->stock);
        $this->assertSame(10, $adjustment->quantity_before);
        $this->assertSame(15, $adjustment->quantity_after);
    }

    public function test_negative_adjustment_cannot_make_stock_negative(): void
    {
        $product = $this->makeProduct(2);
        $this->expectException(LogicException::class);
        app(InventoryAdjustmentService::class)->adjust($product, -3, 'lost');
        $this->assertSame(2, $product->fresh()->stock);
    }

    public function test_adjustment_can_only_be_reversed_once(): void
    {
        $adjustment = app(InventoryAdjustmentService::class)->adjust($this->makeProduct(4), 3, 'correction');
        $service = app(InventoryAdjustmentService::class);
        $service->reverse($adjustment);
        $this->expectException(LogicException::class);
        $service->reverse($adjustment);
    }

    private function makeProduct(int $stock): Product
    {
        return Product::withoutEvents(fn () => Product::create([
            'sku' => 'TEST-' . uniqid(), 'name' => 'Test product', 'purchase_price' => 1,
            'selling_price' => 2, 'stock' => $stock, 'min_stock' => 1,
        ]));
    }
}
