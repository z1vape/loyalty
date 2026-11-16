<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockAdjustment;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\StockCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class StockAdjustmentAuditTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;
    private User $user;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->store = Store::create(['name' => 'Audit Store']);
        $this->user = User::factory()->create();
        $this->user->stores()->attach($this->store);
        $this->product = Product::create([
            'store_id' => $this->store->id, 'sku' => 'AUDIT-001', 'name' => 'Audit Product',
            'stock' => 10, 'min_stock' => 1, 'purchase_price' => 100, 'selling_price' => 150,
        ]);
        $this->actingAs($this->user)->withSession(['active_store_id' => $this->store->id]);
    }

    private function grantPermissions(): void
    {
        $role = Role::create(['name' => 'Stock Auditor']);
        foreach (['Stock In Store', 'Movement List', 'Inventory List'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name], ['category' => 'Inventory', 'group_name' => 'INVENTORY']);
            $role->permissions()->attach($permission);
        }
        $this->user->roles()->attach($role);
        $this->user->unsetRelation('roles');
    }

    private function adjust(int $change, string $reason = 'correction'): StockAdjustment
    {
        return app(InventoryService::class)->adjust($this->product, $change, $reason, 'Audit notes', $this->user->id);
    }

    public function test_positive_adjustment_adds_stock_and_preserves_audit_identity(): void
    {
        $adjustment = $this->adjust(5, 'physical_count');
        $this->assertSame(15, (int) $this->product->fresh()->stock);
        $this->assertSame(10, $adjustment->quantity_before);
        $this->assertSame(5, $adjustment->quantity_change);
        $this->assertSame(15, $adjustment->quantity_after);
        $this->assertEquals($this->store->id, $adjustment->store_id);
        $this->assertEquals($this->user->id, $adjustment->user_id);
        $this->assertSame('Audit notes', $adjustment->notes);
        $this->assertNotNull($adjustment->created_at);
    }

    public function test_negative_adjustment_reduces_stock(): void
    {
        $adjustment = $this->adjust(-4, 'damaged');
        $this->assertSame(6, (int) $this->product->fresh()->stock);
        $this->assertSame(10, $adjustment->quantity_before);
        $this->assertSame(6, $adjustment->quantity_after);
    }

    public function test_negative_stock_rejection_leaves_no_partial_write(): void
    {
        try {
            $this->adjust(-11, 'lost');
            $this->fail('Expected invalid stock to be rejected.');
        } catch (LogicException $e) {
            $this->assertStringContainsString('stock balance', $e->getMessage());
        }
        $this->assertSame(10, (int) $this->product->fresh()->stock);
        $this->assertDatabaseCount('stock_adjustments', 0);
    }

    public function test_zero_balance_is_allowed_and_stale_product_is_reloaded(): void
    {
        $this->adjust(2);
        $adjustment = $this->adjust(-12, 'lost');
        $this->assertSame(12, $adjustment->quantity_before);
        $this->assertSame(0, (int) $this->product->fresh()->stock);
    }

    public function test_reversal_creates_compensating_record_without_destroying_original(): void
    {
        $original = $this->adjust(5);
        $this->adjust(-2, 'lost');
        $reversal = app(InventoryService::class)->reverse($original, $this->user->id, 'Duplicate entry');
        $this->assertSame(13, $reversal->quantity_before);
        $this->assertSame(-5, $reversal->quantity_change);
        $this->assertSame(8, $reversal->quantity_after);
        $this->assertEquals($original->id, $reversal->reversal_of_id);
        $this->assertTrue($original->fresh()->isReversed());
        $this->assertSame(15, $original->fresh()->quantity_after);
        $this->assertDatabaseCount('stock_adjustments', 3);
        foreach ([$original, $reversal] as $record) {
            try {
                app(InventoryService::class)->reverse($record, $this->user->id);
                $this->fail('Repeated or nested reversal must be rejected.');
            } catch (LogicException) {
                $this->assertSame(8, (int) $this->product->fresh()->stock);
            }
        }
        $this->assertDatabaseCount('stock_adjustments', 3);
    }

    public function test_reversal_cannot_produce_negative_stock(): void
    {
        $original = $this->adjust(5);
        $this->adjust(-14, 'lost');
        try {
            app(InventoryService::class)->reverse($original, $this->user->id);
            $this->fail('Expected reversal to reject negative stock.');
        } catch (LogicException) {
            $this->assertFalse($original->fresh()->isReversed());
        }
        $this->assertSame(1, (int) $this->product->fresh()->stock);
        $this->assertDatabaseCount('stock_adjustments', 2);
    }

    public function test_unauthorized_user_cannot_create_or_reverse_adjustment(): void
    {
        $original = $this->adjust(2);
        $this->post(route('stock-adjustments.store'), [
            'product_id' => $this->product->id, 'quantity_change' => 3, 'reason' => 'return',
        ])->assertForbidden();
        $this->post(route('stock-adjustments.reverse', $original))->assertForbidden();
        $this->assertFalse($original->fresh()->isReversed());
        $this->assertDatabaseCount('stock_adjustments', 1);
        $this->assertSame(12, (int) $this->product->fresh()->stock);
    }

    public function test_authorized_http_creation_and_reversal(): void
    {
        $this->grantPermissions();
        $this->post(route('stock-adjustments.store'), [
            'product_id' => $this->product->id, 'quantity_change' => 4, 'reason' => 'return',
        ])->assertRedirect(route('stock-adjustments.index'))->assertSessionHasNoErrors();
        $original = StockAdjustment::firstOrFail();
        $this->post(route('stock-adjustments.reverse', $original), ['notes' => 'Cancelled'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(10, (int) $this->product->fresh()->stock);
        $this->assertDatabaseCount('stock_adjustments', 2);
    }

    public function test_opname_date_session_creation_and_viewing(): void
    {
        $this->grantPermissions();
        $response = $this->post(route('stock-adjustments.store-date'), [
            'date' => '2026-10-29',
            'notes' => 'Audit Rutin Mingguan',
        ]);
        $response->assertRedirect(route('stock-adjustments.date', ['date' => '2026-10-29']));
        $this->assertDatabaseHas('movement_dates', [
            'date' => '2026-10-29',
            'type' => 'opname',
            'notes' => 'Audit Rutin Mingguan',
        ]);

        // Viewing the date folder
        $this->get(route('stock-adjustments.date', ['date' => '2026-10-29']))
            ->assertOk()
            ->assertSee('Audit Rutin Mingguan');

        // Viewing the main folders index
        $this->get(route('stock-adjustments.index'))
            ->assertOk();
    }

    public function test_batch_stock_adjustment_creation(): void
    {
        $this->grantPermissions();
        $p2 = Product::create([
            'store_id' => $this->store->id, 'sku' => 'AUDIT-002', 'name' => 'Audit Product 2',
            'stock' => 20, 'min_stock' => 1, 'purchase_price' => 50, 'selling_price' => 75,
        ]);

        $response = $this->post(route('stock-adjustments.store'), [
            'adjustment_date' => '2026-10-29',
            'redirect_to_date' => 1,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity_change' => 5,
                    'reason' => 'physical_count',
                    'notes' => 'Batch item 1',
                ],
                [
                    'product_id' => $p2->id,
                    'quantity_change' => -3,
                    'reason' => 'damaged',
                    'notes' => 'Batch item 2',
                ],
            ],
        ]);

        $response->assertRedirect(route('stock-adjustments.date', ['date' => '2026-10-29']))
            ->assertSessionHasNoErrors();

        $this->assertSame(15, (int) $this->product->fresh()->stock);
        $this->assertSame(17, (int) $p2->fresh()->stock);
        $this->assertDatabaseCount('stock_adjustments', 2);
    }

    public function test_other_store_product_and_history_are_inaccessible(): void
    {
        $this->grantPermissions();
        $otherStore = Store::create(['name' => 'Other Store']);
        $other = Product::create(['store_id' => $otherStore->id, 'sku' => 'OTHER', 'name' => 'Other',
            'stock' => 1, 'min_stock' => 0, 'purchase_price' => 1, 'selling_price' => 2]);
        $history = StockAdjustment::create(['store_id' => $otherStore->id, 'product_id' => $other->id,
            'user_id' => $this->user->id, 'quantity_before' => 0, 'quantity_change' => 1,
            'quantity_after' => 1, 'reason' => 'return']);
        $this->post(route('stock-adjustments.store'), ['product_id' => $other->id,
            'quantity_change' => 1, 'reason' => 'return'])->assertSessionHasErrors('product_id');
        $this->get(route('stock-adjustments.show', $history))->assertNotFound();
        $this->post(route('stock-adjustments.reverse', $history))->assertNotFound();
    }

    public function test_audit_snapshot_cannot_be_changed_or_deleted(): void
    {
        $adjustment = $this->adjust(1);
        try {
            $adjustment->update(['quantity_after' => 999]);
            $this->fail('Snapshot mutation must be rejected.');
        } catch (LogicException) {
            $this->assertSame(11, $adjustment->fresh()->quantity_after);
        }
        try {
            $adjustment->fresh()->delete();
            $this->fail('History deletion must be rejected.');
        } catch (LogicException) {
            $this->assertDatabaseCount('stock_adjustments', 1);
        }
    }

    public function test_report_includes_adjustments_reversals_and_only_approved_movements(): void
    {
        $this->grantPermissions();
        StockMovement::create(['store_id' => $this->store->id, 'product_id' => $this->product->id,
            'user_id' => $this->user->id, 'reference_number' => 'OPEN', 'movement_date' => '2026-10-01',
            'type' => 'in', 'quantity' => 10, 'status' => 'approved']);
        StockMovement::create(['store_id' => $this->store->id, 'product_id' => $this->product->id,
            'user_id' => $this->user->id, 'reference_number' => 'PENDING', 'movement_date' => '2026-10-01',
            'type' => 'in', 'quantity' => 99, 'status' => 'pending']);
        $this->travelTo(\Carbon\Carbon::parse('2026-10-08 10:00:00'));
        $original = $this->adjust(5);
        $this->adjust(-2, 'lost');
        app(InventoryService::class)->reverse($original, $this->user->id);
        $service = app(StockCalculationService::class);
        $this->assertSame(8, $service->balance($this->product));
        $this->assertSame(8, (int) $this->product->fresh()->stock);
        $this->assertCount(4, $service->ledger($this->product));
        $report = $service->stockCard($this->product, '2026-10-08', '2026-10-08');
        $this->assertSame(10.0, $report['openingBalance']);
        $this->assertSame(8.0, $report['endingBalance']);
        $this->assertSame(5.0, $report['totalIn']);
        $this->assertSame(7.0, $report['totalOut']);
        $this->get(route('inventory.stock-card', ['product' => $this->product->id,
            'date_from' => '2026-10-08', 'date_to' => '2026-10-08']))->assertOk()
            ->assertViewHas('endingBalance', 8.0)->assertSee('ADJ-' . $original->id);
        $this->travelBack();
    }

    public function test_history_filters_and_invalid_date_validation(): void
    {
        $this->grantPermissions();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-08 10:00:00'));
        $match = $this->adjust(-1, 'lost');
        $this->adjust(2, 'return');
        $this->get(route('stock-adjustments.index', ['product_id' => $this->product->id,
            'user_id' => $this->user->id, 'reason' => 'lost',
            'date_from' => '2026-10-08', 'date_to' => '2026-10-08']))->assertOk()
            ->assertViewHas('adjustments', fn ($page) => $page->total() === 1 && $page->first()->id === $match->id);
        $this->get(route('stock-adjustments.index', ['date_from' => 'invalid']))
            ->assertSessionHasErrors('date_from');
        $this->travelBack();
    }

    public function test_assign_stock_opname_minus_to_employee_payroll(): void
    {
        $this->grantPermissions();

        $employee = User::factory()->create(['name' => 'Store Staff']);
        $employee->stores()->attach($this->store);

        $response = $this->post(route('stock-adjustments.assign-payroll'), [
            'date'         => '2026-10-29',
            'store_id'     => $this->store->id,
            'period_month' => 10,
            'period_year'  => 2026,
            'deductions'   => [
                $employee->id => 75000,
            ],
            'note'         => 'Beban selisih barang hilang rak A',
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payrolls', [
            'user_id'          => $employee->id,
            'period_month'     => 10,
            'period_year'      => 2026,
            'other_deductions' => 75000,
        ]);

        // Re-assigning with new amount (e.g. 100,000) should update, NOT accumulate to 175,000
        $updateResponse = $this->post(route('stock-adjustments.assign-payroll'), [
            'date'         => '2026-10-29',
            'store_id'     => $this->store->id,
            'period_month' => 10,
            'period_year'  => 2026,
            'deductions'   => [
                $employee->id => 100000,
            ],
            'note'         => 'Revisi beban selisih opname',
        ]);

        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('payrolls', [
            'user_id'          => $employee->id,
            'period_month'     => 10,
            'period_year'      => 2026,
            'other_deductions' => 100000,
        ]);
    }
}
