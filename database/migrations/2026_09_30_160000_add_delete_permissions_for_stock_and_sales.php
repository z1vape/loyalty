<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
                'name' => 'Stock In Destroy',
                'category' => 'Stock In',
                'group_name' => 'STOCK IN',
                'description' => 'Ability to delete stock in records',
            ],
            [
                'name' => 'Stock Out Destroy',
                'category' => 'Stock Out',
                'group_name' => 'STOCK OUT',
                'description' => 'Ability to delete stock out records',
            ],
            [
                'name' => 'Sales Destroy',
                'category' => 'Sales',
                'group_name' => 'SALES RECORDS',
                'description' => 'Ability to delete sales records',
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
            'Stock In Destroy',
            'Stock Out Destroy',
            'Sales Destroy',
        ])->delete();
    }
};
