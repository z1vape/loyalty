<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Store;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\PurchaseOrder;
use App\Models\ApprovalWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ApprovalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $staff;
    protected Store $store;
    protected Role $superAdminRole;
    protected Role $managerRole;
    protected Role $staffRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['name' => 'Super Admin']);
        $this->managerRole = Role::firstOrCreate(['name' => 'Manager']);
        $this->staffRole = Role::firstOrCreate(['name' => 'Staff']);

        $permRoleList = Permission::firstOrCreate(['name' => 'Role List'], ['category' => 'Roles', 'group_name' => 'ROLES']);
        $permRoleEdit = Permission::firstOrCreate(['name' => 'Role Edit'], ['category' => 'Roles', 'group_name' => 'ROLES']);
        $permSettingList = Permission::firstOrCreate(['name' => 'Setting List'], ['category' => 'Settings', 'group_name' => 'SETTINGS']);
        $permStockIn = Permission::firstOrCreate(['name' => 'Stock In Create'], ['category' => 'Purchasing', 'group_name' => 'PURCHASING']);

        $this->superAdminRole->permissions()->syncWithoutDetaching([$permRoleList->id, $permRoleEdit->id, $permSettingList->id, $permStockIn->id]);
        $this->managerRole->permissions()->syncWithoutDetaching([$permRoleList->id, $permRoleEdit->id, $permSettingList->id, $permStockIn->id]);
        $this->staffRole->permissions()->syncWithoutDetaching([$permStockIn->id]);

        $this->store = Store::create([
            'name' => 'Z1 VAPE STORE VOL 2',
            'code' => 'VOL-02',
            'initial_balance' => 0,
        ]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->roles()->attach($this->superAdminRole->id);
        $this->superAdmin->stores()->attach($this->store->id);

        $this->manager = User::factory()->create();
        $this->manager->roles()->attach($this->managerRole->id);
        $this->manager->stores()->attach($this->store->id);

        $this->staff = User::factory()->create();
        $this->staff->roles()->attach($this->staffRole->id);
        $this->staff->stores()->attach($this->store->id);
    }

    public function test_master_data_index_shows_approval_workflow_card(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('master-data.index'));

        $response->assertStatus(200);
        $response->assertSee('Approval Workflow');
        $response->assertSee(route('master-data.approval-workflows.index'));
    }

    public function test_approval_workflow_index_page_renders_workflows_and_roles(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->get(route('master-data.approval-workflows.index'));

        $response->assertStatus(200);
        $response->assertSee('Approval Workflow');
        $response->assertSee('Purchase Request (Order Barang Cabang)');
        $response->assertSee('Purchase Order (PO Supplier)');
        $response->assertSee('Super Admin');
        $response->assertSee('Manager');
        $response->assertSee('Staff');
    }

    public function test_approval_workflow_configuration_can_be_updated(): void
    {
        $wfPr = ApprovalWorkflow::where('module', 'purchase_request')->firstOrFail();

        // Update workflow to only allow Super Admin (1)
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->put(route('master-data.approval-workflows.update'), [
                'workflows' => [
                    $wfPr->id => [
                        'role_ids' => [$this->superAdminRole->id],
                        'is_active' => 1,
                    ],
                ],
            ]);

        $response->assertRedirect(route('master-data.approval-workflows.index'));
        $response->assertSessionHas('success');

        $wfPr->refresh();
        $this->assertEquals([$this->superAdminRole->id], $wfPr->role_ids);
    }

    public function test_purchase_request_approval_enforces_workflow_roles(): void
    {
        $wfPr = ApprovalWorkflow::where('module', 'purchase_request')->firstOrFail();

        $pr = PurchaseRequest::create([
            'pr_number' => 'PR-TEST-001',
            'store_id' => $this->store->id,
            'user_id' => $this->staff->id,
            'request_date' => now()->toDateString(),
            'status' => 'pending',
            'notes' => 'Test PR',
        ]);

        // Scenario 1: Only Super Admin is authorized
        $wfPr->update(['role_ids' => [$this->superAdminRole->id]]);

        // Manager tries to approve -> 403 Forbidden
        $responseManager = $this->actingAs($this->manager)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.update-status', $pr->id), [
                'status' => 'approved',
            ]);
        $responseManager->assertStatus(403);

        // Super Admin approves -> 200/Redirect
        $responseAdmin = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.update-status', $pr->id), [
                'status' => 'approved',
            ]);
        $responseAdmin->assertRedirect();
        $this->assertEquals('approved', $pr->fresh()->status);

        // Scenario 2: Add Manager to authorized roles
        $wfPr->update(['role_ids' => [$this->superAdminRole->id, $this->managerRole->id]]);

        // Reset PR back to pending
        $pr->update(['status' => 'pending']);

        // Now Manager can approve successfully
        $responseManager2 = $this->actingAs($this->manager)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.orders.update-status', $pr->id), [
                'status' => 'approved',
            ]);
        $responseManager2->assertRedirect();
        $this->assertEquals('approved', $pr->fresh()->status);
    }

    public function test_purchase_order_sent_status_enforces_workflow_roles(): void
    {
        $wfPo = ApprovalWorkflow::where('module', 'purchase_order')->firstOrFail();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'store_id' => $this->store->id,
            'created_by' => $this->staff->id,
            'supplier_name' => 'Test Supplier',
            'po_date' => now()->toDateString(),
            'status' => 'draft',
            'payment_method' => 'CASH',
        ]);

        // Only Super Admin allowed
        $wfPo->update(['role_ids' => [$this->superAdminRole->id]]);

        // Staff tries to send PO -> 403 Forbidden
        $responseStaff = $this->actingAs($this->staff)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.purchase-orders.update-status', $po->id), [
                'status' => 'sent',
            ]);
        $responseStaff->assertStatus(403);

        // Super Admin sends PO -> Success
        $responseAdmin = $this->actingAs($this->superAdmin)
            ->withSession(['active_store_id' => $this->store->id])
            ->post(route('purchasing.purchase-orders.update-status', $po->id), [
                'status' => 'sent',
            ]);
        $responseAdmin->assertRedirect(route('purchasing.purchase-orders.show', $po->id));
        $this->assertEquals('sent', $po->fresh()->status);
    }
}
