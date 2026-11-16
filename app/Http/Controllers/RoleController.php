<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        Gate::authorize('Role List');

        $roles = Role::with(['users', 'permissions'])->get();
        $permissions = Permission::all();
        $users = \App\Models\User::with('roles')->paginate(15);

        return view('roles.index', compact('roles', 'permissions', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('Role Create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create($validated);

        return redirect()->route('roles.index')->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Update permissions assigned to a role.
     */
    public function updatePermissions(Request $request, Role $role)
    {
        Gate::authorize('Role Update');

        // Prevent modifying Super Admin permissions to avoid locking out the system
        if ($role->name === 'Super Admin') {
            return redirect()->route('roles.index')->withErrors(['error' => 'Super Admin always retains all permissions and cannot be modified.']);
        }

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $permissions = $validated['permissions'] ?? [];
        $role->permissions()->sync($permissions);

        return redirect()->route('roles.index')->with('success', "Permissions for '{$role->name}' updated successfully.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        Gate::authorize('Role Destroy');

        // Prevent deleting default critical roles
        if (in_array($role->name, ['Super Admin', 'Manager', 'Staff'])) {
            return redirect()->route('roles.index')->withErrors(['error' => "The default role '{$role->name}' cannot be deleted."]);
        }

        $roleName = $role->name;
        $role->delete();

        return redirect()->route('roles.index')->with('success', "Role '{$roleName}' deleted successfully.");
    }
}
