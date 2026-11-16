<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Store;
use App\Models\Product;
use App\Models\MovementDate;
use App\Models\StockMovement;
use App\Services\AccountingService;

class RevertStockInToDraftCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_artisan_command_reverts_approved_stock_in_to_draft(): void
    {
        $role = Role::create(['name' => 'Super Admin']);
        $store = Store::create(['name' => 'Store Utama']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);
        $user->stores()->attach($store->id);

        $product = Product::create([
            'sku' => 'CMD-001',
            'name' => 'Biskuit Kaleng',
            'purchase_price' => 20000,
            'selling_price' => 30000,
            'stock' => 5,
            'min_stock' => 1,
            'store_id' => $store->id,
        ]);

        $movementDate = MovementDate::create([
            'date' => '2026-10-23',
            'type' => 'in',
            'created_by' => $user->id,
            'store_id' => $store->id,
        ]);

        $movement = StockMovement::create([
            'store_id' => $store->id,
            'product_id' => $product->id,
            'user_id' => $user->id,
            'reference_number' => 'TXN-99999',
            'movement_date' => '2026-10-23',
            'type' => 'in',
            'quantity' => 10,
            'purchase_price' => 25000,
            'selling_price' => 30000,
            'discount' => 0,
            'total_price' => 250000,
            'payment_method' => 'cash',
            'status' => 'approved',
        ]);

        $product->applyStockInMovingAverage(10, 25000);
        $product->refresh();
        $this->assertEquals(15, $product->stock);

        // Record journal
        app(AccountingService::class)->recordStockInJournal($movement);
        $this->assertDatabaseHas('journal_entries', [
            'source_type' => 'stock_movement',
            'source_id' => $movement->id,
        ]);

        // Run artisan command
        $this->artisan('stock:revert-to-draft', ['identifier' => '2026-10-23'])
            ->expectsConfirmation('Apakah Anda yakin ingin mengembalikan 1 item mutasi menjadi DRAFT? (Stok akan dikurangi kembali dan jurnal transaksi akan dihapus)', 'yes')
            ->assertExitCode(0);

        $this->assertEquals('draft', $movement->fresh()->status);
        $this->assertEquals(5, $product->fresh()->stock);

        $this->assertDatabaseMissing('journal_entries', [
            'source_type' => 'stock_movement',
            'source_id' => $movement->id,
        ]);
    }
}
