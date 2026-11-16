<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PermissionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('Permission Create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name', 'regex:/^[a-zA-Z0-9_\-]+$/'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::create($validated);

        return redirect()->route('roles.index')->with('success', "Permission '{$permission->name}' created successfully.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        Gate::authorize('Permission Destroy');

        // Prevent deleting core system permissions
        $corePermissions = [
            'Dashboard List', 
            'Inventory List', 'Inventory Create', 'Inventory Show', 'Inventory Edit', 'Inventory Store', 'Inventory Update', 'Inventory Destroy',
            'Stock In List', 'Stock In Create', 'Stock In Store', 'Stock In Destroy',
            'Stock Out List', 'Stock Out Create', 'Stock Out Store', 'Stock Out Destroy',
            'Sales List', 'Sales Create', 'Sales Show', 'Sales Destroy',
            'Movement List', 'Movement Create', 'Movement Show', 'Movement Store', 'Movement Destroy',
            'User List', 'User Create', 'User Show', 'User Edit', 'User Store', 'User Update', 'User Destroy',
            'Role List', 'Role Create', 'Role Show', 'Role Edit', 'Role Store', 'Role Update', 'Role Destroy',
            'Permission List', 'Permission Create', 'Permission Destroy',
            'Report List', 'Report SKU', 'Report Category', 'Report Staff'
        ];
        if (in_array($permission->name, $corePermissions)) {
            return redirect()->route('roles.index')->withErrors(['error' => "Core system permission '{$permission->name}' cannot be deleted."]);
        }

        $permissionName = $permission->name;
        $permission->delete();

        return redirect()->route('roles.index')->with('success', "Permission '{$permissionName}' deleted successfully.");
    }
}
