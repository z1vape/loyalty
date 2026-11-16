<?php
namespace Tests\Unit\Models;
use Tests\TestCase;
use App\Models\Product;
class ProductTest extends TestCase { public function test_product_has_store_fillable(): void { $this->assertContains('store_id', (new Product)->getFillable()); } }
