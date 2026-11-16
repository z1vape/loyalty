<?php

namespace Tests\Feature;

use App\Models\JournalEntry;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\User;
use App\Services\StockCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTransferTest extends TestCase
{
    use RefreshDatabase;

    protected Store $storeA;
    protected Store $storeB;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(['name' => 'Super Admin']);

        $this->storeA = Store::create([
            'name' => 'Cabang Pusat (A)',
            'address' => 'Jl. Pusat No. 1',
            'phone' => '0811111111',
        ]);

        $this->storeB = Store::create([
            'name' => 'Cabang Selatan (B)',
            'address' => 'Jl. Selatan No. 2',
            'phone' => '0822222222',
        ]);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);
        $this->admin->stores()->attach([$this->storeA->id, $this->storeB->id]);
    }

    public function test_cannot_create_or_send_transfer_when_source_stock_is_insufficient(): void
    {
        $productA = Product::withoutGlobalScopes()->create([
            'sku' => 'LIQ-001',
            'name' => 'Liquid Oat Drips 60ml',
            'purchase_price' => 80000,
            'selling_price' => 120000,
            'stock' => 5,
            'min_stock' => 2,
            'category' => 'Liquid',
            'store_id' => $this->storeA->id,
        ]);

        // Attempt to create transfer requesting 10 units when only 5 in stock
        $response = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeA->id])
            ->post(route('stock-transfers.store'), [
                'from_store_id' => $this->storeA->id,
                'to_store_id' => $this->storeB->id,
                'transfer_date' => '2026-10-31',
                'items' => [
                    ['product_id' => $productA->id, 'quantity' => 10],
                ],
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('stock_transfers', 0);

        // Now create a valid draft of 5 units, then reduce physical stock to 3 before sending
        $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeA->id])
            ->post(route('stock-transfers.store'), [
                'from_store_id' => $this->storeA->id,
                'to_store_id' => $this->storeB->id,
                'transfer_date' => '2026-10-31',
                'items' => [
                    ['product_id' => $productA->id, 'quantity' => 5],
                ],
            ]);

        $transfer = StockTransfer::firstOrFail();
        $this->assertEquals('draft', $transfer->status);

        $productA->update(['stock' => 3]);

        $sendResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeA->id])
            ->post(route('stock-transfers.send', $transfer->id));

        $sendResponse->assertSessionHas('error');
        $this->assertEquals('draft', $transfer->fresh()->status);
        $this->assertEquals(3, $productA->fresh()->stock);
    }

    public function test_full_transfer_lifecycle_updates_stock_moving_average_cost_and_balanced_journals(): void
    {
        // Product in Store A: 20 pcs @ Rp 100.000
        $productA = Product::withoutGlobalScopes()->create([
            'sku' => 'MOD-200',
            'name' => 'Mod Centaurus M200',
            'purchase_price' => 100000,
            'selling_price' => 150000,
            'stock' => 20,
            'min_stock' => 3,
            'category' => 'Device',
            'store_id' => $this->storeA->id,
        ]);

        // Existing Product in Store B with same SKU: 10 pcs @ Rp 70.000
        $productB = Product::withoutGlobalScopes()->create([
            'sku' => 'MOD-200',
            'name' => 'Mod Centaurus M200',
            'purchase_price' => 70000,
            'selling_price' => 150000,
            'stock' => 10,
            'min_stock' => 3,
            'category' => 'Device',
            'store_id' => $this->storeB->id,
        ]);

        // Another Product in Store A that does NOT exist yet in Store B: 15 pcs @ Rp 50.000
        $newSkuProductA = Product::withoutGlobalScopes()->create([
            'sku' => 'CTG-999',
            'name' => 'Cartridge Oxva Xlim V3',
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'stock' => 15,
            'min_stock' => 5,
            'category' => 'Cartridge',
            'store_id' => $this->storeA->id,
        ]);

        // 1. Create Draft Transfer from Store A to Store B
        $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeA->id])
            ->post(route('stock-transfers.store'), [
                'from_store_id' => $this->storeA->id,
                'to_store_id' => $this->storeB->id,
                'transfer_date' => '2026-10-31',
                'notes' => 'Pengiriman stok reguler',
                'items' => [
                    ['product_id' => $productA->id, 'quantity' => 10, 'notes' => 'Box segel'],
                    ['product_id' => $newSkuProductA->id, 'quantity' => 4, 'notes' => 'SKU baru'],
                ],
            ]);

        $transfer = StockTransfer::with('items')->firstOrFail();
        $this->assertEquals('draft', $transfer->status);
        $this->assertEquals(1200000, (float) $transfer->total_cost); // (10 * 100k) + (4 * 50k)
        $this->assertEquals(20, $productA->fresh()->stock); // Not deducted yet in draft

        // 2. Send Transfer (Draft -> In-Transit)
        $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeA->id])
            ->post(route('stock-transfers.send', $transfer->id))
            ->assertSessionHas('success');

        $transfer->refresh();
        $this->assertEquals('in_transit', $transfer->status);
        $this->assertNotNull($transfer->sent_at);

        // Source stock is immediately deducted; destination stock is still untouched while in-transit
        $this->assertEquals(10, $productA->fresh()->stock);
        $this->assertEquals(11, $newSkuProductA->fresh()->stock);
        $this->assertEquals(10, $productB->fresh()->stock);

        // Verify Outgoing StockMovement & Journal in Store A
        $outMovements = StockMovement::withoutGlobalScopes()
            ->where('reference_number', $transfer->transfer_number)
            ->where('store_id', $this->storeA->id)
            ->where('type', 'out')
            ->get();
        $this->assertCount(2, $outMovements);

        $outJournal = JournalEntry::withoutGlobalScopes()
            ->with('items.account')
            ->where('store_id', $this->storeA->id)
            ->where('source_type', 'stock_transfer_out')
            ->where('source_id', $transfer->id)
            ->first();

        $this->assertNotNull($outJournal);
        $this->assertTrue((bool) $outJournal->is_balanced);
        $this->assertEquals(1200000, (float) $outJournal->total_debit);
        $this->assertEquals(1200000, (float) $outJournal->total_credit);
        $this->assertNotNull($outJournal->items->first(fn ($i) => $i->account->code === '1104' && (float) $i->debit === 1200000.0));
        $this->assertNotNull($outJournal->items->first(fn ($i) => $i->account->code === '1201' && (float) $i->credit === 1200000.0));

        // 3. Switch to Store B session, verify visibility, and Receive Transfer
        $indexResponse = $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeB->id])
            ->get(route('stock-transfers.index', ['direction' => 'incoming']));
        $indexResponse->assertOk();
        $indexResponse->assertSee($transfer->transfer_number);

        $receivePayload = [
            'received_date' => '2026-10-31',
            'notes' => 'Diterima lengkap oleh kepala toko B',
            'items' => [],
        ];
        foreach ($transfer->items as $item) {
            $receivePayload['items'][$item->id] = [
                'quantity_received' => $item->quantity_sent,
            ];
        }

        $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeB->id])
            ->post(route('stock-transfers.receive', $transfer->id), $receivePayload)
            ->assertSessionHas('success');

        $transfer->refresh();
        $this->assertEquals('received', $transfer->status);
        $this->assertNotNull($transfer->received_at);

        // Verify Store B existing product stock & Moving Average Cost:
        // Previously 10 pcs @ 70.000 (700.000) + Incoming 10 pcs @ 100.000 (1.000.000) = 20 pcs @ Rp 85.000
        $productB->refresh();
        $this->assertEquals(20, $productB->stock);
        $this->assertEquals(85000.00, (float) $productB->purchase_price);

        // Verify Store B auto-created product for CTG-999: 4 pcs @ Rp 50.000
        $autoCreatedProductB = Product::withoutGlobalScopes()
            ->where('store_id', $this->storeB->id)
            ->where('sku', 'CTG-999')
            ->first();
        $this->assertNotNull($autoCreatedProductB);
        $this->assertEquals(4, $autoCreatedProductB->stock);
        $this->assertEquals(50000.00, (float) $autoCreatedProductB->purchase_price);

        // Verify Incoming Journal in Store B (Debit 1201 Persediaan, Credit 2102 Hutang Antar Cabang)
        $inJournal = JournalEntry::withoutGlobalScopes()
            ->with('items.account')
            ->where('store_id', $this->storeB->id)
            ->where('source_type', 'stock_transfer_in')
            ->where('source_id', $transfer->id)
            ->first();

        $this->assertNotNull($inJournal);
        $this->assertTrue((bool) $inJournal->is_balanced);
        $this->assertEquals(1200000, (float) $inJournal->total_debit);
        $this->assertEquals(1200000, (float) $inJournal->total_credit);
        $this->assertNotNull($inJournal->items->first(fn ($i) => $i->account->code === '1201' && (float) $i->debit === 1200000.0));
        $this->assertNotNull($inJournal->items->first(fn ($i) => $i->account->code === '2102' && (float) $i->credit === 1200000.0));

        // Verify Stock Card label shows "Mutasi Antar Cabang"
        $cardService = app(StockCalculationService::class);
        $cardA = $cardService->stockCard($productA->fresh());
        $this->assertNotEmpty($cardA['rows']);
        $this->assertEquals('Mutasi Antar Cabang', $cardA['rows'][0]['source_label']);
    }

    public function test_draft_can_be_cancelled_and_deleted_but_sent_transfer_cannot(): void
    {
        $productA = Product::withoutGlobalScopes()->create([
            'sku' => 'COIL-01',
            'name' => 'Coil RPM 0.4 Ohm',
            'purchase_price' => 30000,
            'selling_price' => 45000,
            'stock' => 25,
            'min_stock' => 5,
            'category' => 'Coil',
            'store_id' => $this->storeA->id,
        ]);

        $this->actingAs($this->admin)
            ->withSession(['active_store_id' => $this->storeA->id])
            ->post(route('stock-transfers.store'), [
                'from_store_id' => $this->storeA->id,
                'to_store_id' => $this->storeB->id,
                'transfer_date' => '2026-10-31',
                'items' => [
                    ['product_id' => $productA->id, 'quantity' => 5],
                ],
            ]);

        $transfer = StockTransfer::firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('stock-transfers.cancel', $transfer->id))
            ->assertSessionHas('success');

        $this->assertEquals('cancelled', $transfer->fresh()->status);

        $this->actingAs($this->admin)
            ->delete(route('stock-transfers.destroy', $transfer->id))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('stock_transfers', 0);
    }
}
