<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class StockMovementTest extends TestCase { use RefreshDatabase; public function test_guest_cannot_create_movement(): void { $this->post('/movements', [])->assertRedirect('/login'); } }
