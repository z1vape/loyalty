<?php
namespace Tests\Unit\Models;
use Tests\TestCase;
use App\Models\StockMovement;
class StockMovementTest extends TestCase { public function test_movement_belongs_to_product_and_store(): void { $movement = new StockMovement; $this->assertSame('product_id', $movement->product()->getForeignKeyName()); $this->assertContains('store_id', $movement->getFillable()); } }
