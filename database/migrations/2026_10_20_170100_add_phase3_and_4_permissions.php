<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\Permission;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissions = [
            [
                'name' => 'Accounting Report View',
                'category' => 'Laporan Keuangan',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to view Profit & Loss, Balance Sheet, and Trial Balance',
            ],
            [
                'name' => 'Accounting Transfer Manage',
                'category' => 'Kas & Bank Transfer',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to record internal fund transfers between cash and bank',
            ],
            [
                'name' => 'Accounting Payable Manage',
                'category' => 'Hutang Supplier',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to view and record repayments for supplier debts',
            ],
            [
                'name' => 'Accounting Period Manage',
                'category' => 'Tutup Buku / Periode',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to close and reopen accounting periods',
            ],
        ];

        foreach ($permissions as $pData) {
            $permission = Permission::firstOrCreate(
                ['name' => $pData['name']],
                $pData
            );

            // Give permission to Super Admin and Manager
            $roles = Role::whereIn('name', ['Super Admin', 'Manager'])->get();
            foreach ($roles as $role) {
                if (!$role->permissions->contains('id', $permission->id)) {
                    $role->permissions()->attach($permission->id);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'Accounting Report View',
            'Accounting Transfer Manage',
            'Accounting Payable Manage',
            'Accounting Period Manage',
        ])->delete();
    }
};
