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
                'name' => 'Accounting CoA List',
                'category' => 'Bagan Akun (CoA)',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to view Chart of Accounts list',
            ],
            [
                'name' => 'Accounting CoA Create',
                'category' => 'Bagan Akun (CoA)',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to create new custom account',
            ],
            [
                'name' => 'Accounting CoA Edit',
                'category' => 'Bagan Akun (CoA)',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to edit custom account details',
            ],
            [
                'name' => 'Accounting CoA Delete',
                'category' => 'Bagan Akun (CoA)',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to delete non-system account',
            ],
            [
                'name' => 'Accounting Journal List',
                'category' => 'Jurnal Transaksi',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to view journal entries',
            ],
            [
                'name' => 'Accounting Journal Create',
                'category' => 'Jurnal Transaksi',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to create manual journal entry',
            ],
            [
                'name' => 'Accounting Ledger View',
                'category' => 'Buku Besar',
                'group_name' => 'AKUNTANSI',
                'description' => 'Ability to view General Ledger',
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
            'Accounting CoA List',
            'Accounting CoA Create',
            'Accounting CoA Edit',
            'Accounting CoA Delete',
            'Accounting Journal List',
            'Accounting Journal Create',
            'Accounting Ledger View',
        ])->delete();
    }
};
