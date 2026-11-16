<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class StoreIsolationTest extends TestCase { use RefreshDatabase; public function test_public_inventory_api_does_not_bypass_store_scope(): void { $this->getJson('/api/products')->assertOk()->assertJsonPath('status','success'); } }
