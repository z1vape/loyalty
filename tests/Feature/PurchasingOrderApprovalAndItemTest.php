<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingOrderApprovalAndItemTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $staff;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['name' => 'Super Admin', 'description' => 'Super Admin']);
        $staffRole = Role::firstOrCreate(['name' => 'Staff', 'description' => 'Staff']);

        \App\Models\ApprovalWorkflow::where('module', 'purchase_request')
            ->update(['role_ids' => [$superAdminRole->id]]);

        $purchasingPermission = Permission::firstOrCreate([
            'name' => 'Stock In Create',
            'category' => 'Operation',
            'group_name' => 'STOCK IN',
            'description' => 'Ability to create stock in'
        ]);

        $staffRole->permissions()->sync([$purchasingPermission->id]);

        $this->store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'address' => 'Jl. Test No. 1',
            'phone' => '08123456789',
        ]);

        $this->superAdmin = User::factory()->create([
            'name' => 'Rizky Reynaldi',
            'email' => 'admin@test.com',
        ]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);
        $this->superAdmin->stores()->sync([$this->store->id]);

        $this->staff = User::factory()->create([
            'name' => 'Staff Toko',
            'email' => 'staff@test.com',
        ]);
        $this->staff->roles()->sync([$staffRole->id]);
        $this->staff->stores()->sync([$this->store->id]);
    }

    public function test_only_super_admin_can_approve_order_barang(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PO-20261103-0001',
            'store_id' => $this->store->id,
            'user_id' => $this->staff->id,
            'request_date' => '2026-11-03',
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        // Non-super admin staff attempts to approve -> 403 Forbidden
        $response = $this->actingAs($this->staff)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.update-status', $pr->id), [
                'status' => 'approved',
            ]);
        $response->assertStatus(403);
        $this->assertEquals('pending', $pr->fresh()->status);

        // Super Admin approves -> Success
        $responseSuper = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.update-status', $pr->id), [
                'status' => 'approved',
            ]);
        $responseSuper->assertRedirect();
        $this->assertEquals('approved', $pr->fresh()->status);
        $this->assertEquals($this->superAdmin->id, $pr->fresh()->approved_by);
    }

    public function test_cannot_add_or_delete_items_when_order_is_approved(): void
    {
        $product = Product::create([
            'name' => 'Liquid Saltnic',
            'sku' => 'LIQ-001',
            'store_id' => $this->store->id,
            'purchase_price' => 50000,
            'selling_price' => 75000,
            'stock' => 10,
        ]);

        $pr = PurchaseRequest::create([
            'pr_number' => 'PO-20261103-0002',
            'store_id' => $this->store->id,
            'user_id' => $this->superAdmin->id,
            'request_date' => '2026-11-03',
            'status' => 'approved',
            'priority' => 'normal',
        ]);

        $item = PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'product_id' => $product->id,
            'quantity_requested' => 5,
        ]);

        // Attempt to add item to approved order -> Blocked with session error
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.add-item', $pr->id), [
                'product_id' => $product->id,
                'quantity_requested' => 2,
            ]);
        $response->assertRedirect(route('purchasing.orders.show', $pr->id));
        $response->assertSessionHas('error');
        $this->assertCount(1, $pr->fresh()->items);

        // Attempt to delete item from approved order -> Blocked with session error
        $deleteResponse = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->delete(route('purchasing.orders.destroy-item', $item->id));
        $deleteResponse->assertRedirect(route('purchasing.orders.show', $pr->id));
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('purchase_request_items', ['id' => $item->id]);
    }

    public function test_tambah_barang_button_hidden_in_view_when_approved(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PO-20261103-0003',
            'store_id' => $this->store->id,
            'user_id' => $this->superAdmin->id,
            'request_date' => '2026-11-03',
            'status' => 'approved',
            'priority' => 'normal',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('purchasing.orders.show', $pr->id));
        $response->assertStatus(200);
        // The top button "Tambah Barang" and table button "Tambah Item" must NOT be in HTML
        $response->assertDontSee('Tambah Barang</span>', false);
        $response->assertDontSee('Tambah Item</span>', false);
        // The "Hapus Order" button must NOT be in HTML when approved
        $response->assertDontSee('Hapus Order</span>', false);
        $response->assertSee('Order Telah Disetujui');
        // Super Admin must see "Revisi ke Pending"
        $response->assertSee('Revisi ke Pending');
    }

    public function test_super_admin_can_revise_approved_order_to_pending(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PO-20261103-0009',
            'store_id' => $this->store->id,
            'user_id' => $this->superAdmin->id,
            'request_date' => '2026-11-03',
            'status' => 'approved',
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
            'priority' => 'normal',
        ]);

        // Non-super admin staff attempts to revert to pending -> 403 Forbidden
        $responseStaff = $this->actingAs($this->staff)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.update-status', $pr->id), [
                'status' => 'pending',
            ]);
        $responseStaff->assertStatus(403);
        $this->assertEquals('approved', $pr->fresh()->status);

        // Super Admin reverts to pending -> Success
        $responseSuper = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.update-status', $pr->id), [
                'status' => 'pending',
            ]);
        $responseSuper->assertRedirect();
        $this->assertEquals('pending', $pr->fresh()->status);
        $this->assertNull($pr->fresh()->approved_by);
        $this->assertNull($pr->fresh()->approved_at);
    }

    public function test_order_can_be_deleted_via_controller_and_artisan_command(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PO-20261103-0004',
            'store_id' => $this->store->id,
            'user_id' => $this->superAdmin->id,
            'request_date' => '2026-11-03',
            'status' => 'rejected',
            'priority' => 'normal',
        ]);

        PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'custom_item_name' => 'Coil Master',
            'quantity_requested' => 10,
        ]);

        $this->assertDatabaseHas('purchase_requests', ['pr_number' => 'PO-20261103-0004']);
        $this->assertDatabaseHas('purchase_request_items', ['custom_item_name' => 'Coil Master']);

        // Test delete via Artisan command
        $this->artisan('order:delete', ['identifier' => 'PO-20261103-0004', '--force' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('purchase_requests', ['pr_number' => 'PO-20261103-0004']);
        $this->assertDatabaseMissing('purchase_request_items', ['custom_item_name' => 'Coil Master']);
    }

    public function test_delete_button_present_in_index_and_show_for_super_admin(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PO-20261103-0005',
            'store_id' => $this->store->id,
            'user_id' => $this->superAdmin->id,
            'request_date' => '2026-11-03',
            'status' => 'rejected',
            'priority' => 'normal',
        ]);

        // Check index page
        $indexResponse = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('purchasing.orders.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee(route('purchasing.orders.destroy', $pr->id));

        // Check show page
        $showResponse = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('purchasing.orders.show', $pr->id));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Hapus Order');
        $showResponse->assertSee(route('purchasing.orders.destroy', $pr->id));
    }

    public function test_pending_order_with_zero_items_has_delete_action_and_can_be_deleted(): void
    {
        // An order created by someone else, pending, 0 items
        $otherUser = User::factory()->create();
        $otherUser->stores()->sync([$this->store->id]);

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-20261104-0002',
            'store_id' => $this->store->id,
            'user_id' => $otherUser->id,
            'request_date' => '2026-11-04',
            'status' => 'pending',
            'priority' => 'normal',
        ]);

        // Staff views the index list -> delete button must be visible because status=Pending & total items=0
        $response = $this->actingAs($this->staff)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('purchasing.orders.index'));
        $response->assertStatus(200);
        $response->assertSee(route('purchasing.orders.destroy', $pr->id));

        // Staff deletes this pending 0-item order -> Success
        $deleteResponse = $this->actingAs($this->staff)
            ->withSession(['active_store_id' => $this->store->id])
            ->delete(route('purchasing.orders.destroy', $pr->id));
        $deleteResponse->assertRedirect(route('purchasing.orders.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('purchase_requests', ['pr_number' => 'PR-20261104-0002']);
    }

    public function test_can_add_custom_item_to_po_and_sync_to_pr(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-20261106-0001',
            'store_id' => $this->store->id,
            'user_id' => $this->superAdmin->id,
            'request_date' => '2026-11-06',
            'status' => 'approved',
            'priority' => 'normal',
        ]);

        $po = \App\Models\PurchaseOrder::create([
            'po_number' => 'PO-20261106-0001',
            'purchase_request_id' => $pr->id,
            'store_id' => $this->store->id,
            'supplier_name' => 'Supplier Alpha',
            'po_date' => '2026-11-06',
            'payment_method' => 'tempo',
            'payment_term_days' => 14,
            'status' => 'sent',
            'total_amount' => 0,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.purchase-orders.items.add', $po->id), [
                'item_type' => 'custom',
                'custom_item_name' => 'Barang Baru Spesial',
                'custom_sku' => 'SPECIAL-01',
                'quantity_ordered' => 10,
                'unit_price' => 50000,
                'selling_price' => 75000,
                'notes' => 'Tambahan rasa baru',
            ]);

        $response->assertRedirect(route('purchasing.purchase-orders.show', $po->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $po->id,
            'custom_item_name' => 'Barang Baru Spesial',
            'custom_sku' => 'SPECIAL-01',
            'quantity_ordered' => 10,
            'unit_price' => 50000,
            'selling_price' => 75000,
            'subtotal' => 500000,
            'is_cancelled' => false,
        ]);

        // Assert also synchronized to linked PR
        $this->assertDatabaseHas('purchase_request_items', [
            'purchase_request_id' => $pr->id,
            'custom_item_name' => 'Barang Baru Spesial',
            'custom_sku' => 'SPECIAL-01',
            'quantity_requested' => 10,
            'added_from_po' => true,
            'is_cancelled' => false,
        ]);

        $this->assertEquals(500000, (float) $po->fresh()->total_amount);
    }

    public function test_can_toggle_cancel_po_item_and_sync_cancellation_to_pr(): void
    {
        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-20261106-0002',
            'store_id' => $this->store->id,
            'user_id' => $this->superAdmin->id,
            'request_date' => '2026-11-06',
            'status' => 'approved',
            'priority' => 'normal',
        ]);

        $prItem = PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'custom_item_name' => 'Liquid Coklat',
            'estimated_price' => 40000,
            'quantity_requested' => 5,
            'quantity_approved' => 5,
            'is_cancelled' => false,
        ]);

        $po = \App\Models\PurchaseOrder::create([
            'po_number' => 'PO-20261106-0002',
            'purchase_request_id' => $pr->id,
            'store_id' => $this->store->id,
            'supplier_name' => 'Supplier Beta',
            'po_date' => '2026-11-06',
            'payment_method' => 'cash',
            'status' => 'sent',
            'total_amount' => 200000,
            'created_by' => $this->superAdmin->id,
        ]);

        $poItem = \App\Models\PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'purchase_request_item_id' => $prItem->id,
            'custom_item_name' => 'Liquid Coklat',
            'quantity_ordered' => 5,
            'quantity_received' => 0,
            'unit_price' => 40000,
            'subtotal' => 200000,
            'is_cancelled' => false,
        ]);

        // Cancel the item
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.purchase-orders.items.toggle-cancel', [$po->id, $poItem->id]));

        $response->assertRedirect(route('purchasing.purchase-orders.show', $po->id));
        $this->assertTrue((bool) $poItem->fresh()->is_cancelled);
        $this->assertTrue((bool) $prItem->fresh()->is_cancelled);
        // Total active amount becomes 0 because item was cancelled
        $this->assertEquals(0, (float) $po->fresh()->total_amount);
    }

    public function test_receiving_custom_po_item_creates_product_and_updates_prices(): void
    {
        $po = \App\Models\PurchaseOrder::create([
            'po_number' => 'PO-20261106-0003',
            'store_id' => $this->store->id,
            'supplier_name' => 'Supplier Gamma',
            'po_date' => '2026-11-06',
            'payment_method' => 'cash',
            'status' => 'sent',
            'total_amount' => 300000,
            'created_by' => $this->superAdmin->id,
        ]);

        $poItem = \App\Models\PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'custom_item_name' => 'Coil Special V2',
            'custom_sku' => 'COIL-SPEC-02',
            'quantity_ordered' => 10,
            'quantity_received' => 0,
            'unit_price' => 30000,
            'selling_price' => 45000,
            'subtotal' => 300000,
            'is_cancelled' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.purchase-orders.receive', $po->id), [
                'received_date' => '2026-11-06',
                'notes' => 'Diterima fisik dengan harga faktur naik sedikit',
                'items' => [
                    $poItem->id => [
                        'received_qty' => 10,
                        'unit_price' => 32000, // actual faktur price changed
                        'selling_price' => 48000, // new selling price
                    ]
                ]
            ]);

        $response->assertRedirect(route('purchasing.purchase-orders.show', $po->id));
        $response->assertSessionHas('success');

        // Product was automatically created in master products table
        $createdProduct = Product::where('store_id', $this->store->id)
            ->where('sku', 'COIL-SPEC-02')
            ->first();
        $this->assertNotNull($createdProduct);
        $this->assertEquals('Coil Special V2', $createdProduct->name);
        $this->assertEquals(32000, (float) $createdProduct->purchase_price);
        $this->assertEquals(48000, (float) $createdProduct->selling_price);
        $this->assertEquals(10, $createdProduct->stock);

        // Stock movement was recorded with the actual prices
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $createdProduct->id,
            'quantity' => 10,
            'purchase_price' => 32000,
            'selling_price' => 48000,
            'type' => 'in',
            'reference_number' => $po->po_number,
        ]);

        $this->assertEquals('received', $po->fresh()->status);
    }
}
