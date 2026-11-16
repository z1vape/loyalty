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
        $newPermissions = [
            [
                'name' => 'Income List',
                'category' => 'Pemasukan Toko',
                'group_name' => 'PEMASUKAN TOKO',
                'description' => 'Ability to view store incomes list',
            ],
            [
                'name' => 'Income Create',
                'category' => 'Pemasukan Toko',
                'group_name' => 'PEMASUKAN TOKO',
                'description' => 'Ability to add store income',
            ],
            [
                'name' => 'Income Edit',
                'category' => 'Pemasukan Toko',
                'group_name' => 'PEMASUKAN TOKO',
                'description' => 'Ability to edit store income',
            ],
            [
                'name' => 'Income Destroy',
                'category' => 'Pemasukan Toko',
                'group_name' => 'PEMASUKAN TOKO',
                'description' => 'Ability to delete store income',
            ],
        ];

        foreach ($newPermissions as $pData) {
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
            'Income List',
            'Income Create',
            'Income Edit',
            'Income Destroy',
        ])->delete();
    }
};
