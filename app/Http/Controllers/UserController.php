<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('User List');

        $query = User::with(['roles', 'stores']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('employee_number', 'like', "%{$search}%")
                  ->orWhere('position', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role_id')) {
            $roleId = $request->input('role_id');
            $query->whereHas('roles', function($q) use ($roleId) {
                $q->where('roles.id', $roleId);
            });
        }

        if ($request->filled('store_id')) {
            $storeId = $request->input('store_id');
            $query->whereHas('stores', function($q) use ($storeId) {
                $q->where('stores.id', $storeId);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        $roles = Role::all();
        $stores = \App\Models\Store::all();
        $statuses = \App\Models\Setting::where('type', 'status')->orderBy('value')->pluck('value');

        // KPI Summary Stats
        $stats = [
            'total_users' => User::count(),
            'admin_manager' => User::whereHas('roles', fn($q) => $q->whereIn('name', ['Super Admin', 'Manager']))->count(),
            'staff' => User::whereHas('roles', fn($q) => $q->where('name', 'Staff'))->count(),
            'total_stores' => \App\Models\Store::count(),
        ];

        return view('users.index', compact('users', 'roles', 'stores', 'statuses', 'stats'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        Gate::authorize('User Show');

        $user->load(['roles', 'stores']);

        if (request()->wantsJson()) {
            return response()->json([
                'user' => $user,
                'roles' => $user->roles,
                'stores' => $user->stores,
            ]);
        }

        return view('users.show', compact('user'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('User Create');

        $roles = Role::all();
        $stores = \App\Models\Store::all();
        $positions = \App\Models\Setting::where('type', 'position')->orderBy('value')->pluck('value');
        $statuses  = \App\Models\Setting::where('type', 'status')->orderBy('value')->pluck('value');
        $user = new User();

        return view('users.form', compact('roles', 'stores', 'user', 'positions', 'statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('User Store');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'address' => ['nullable', 'string'],
            'employee_number' => ['required', 'string', 'max:255', 'unique:users,employee_number'],
            'status' => ['required', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'position' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'mobile_phone' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'stores' => ['nullable', 'array'],
            'stores.*' => ['exists:stores,id'],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('users', 'public');
        }

        $userData = array_merge($validated, [
            'password' => Hash::make($validated['password']),
        ]);

        $user = User::create($userData);

        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }

        if (!empty($validated['stores'])) {
            $user->stores()->sync($validated['stores']);
        }

        return redirect()->route('users.index')->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        Gate::authorize('User Edit');

        $roles = Role::all();
        $stores = \App\Models\Store::all();
        $positions = \App\Models\Setting::where('type', 'position')->orderBy('value')->pluck('value');
        $statuses  = \App\Models\Setting::where('type', 'status')->orderBy('value')->pluck('value');
        $user->load('roles', 'stores');

        return view('users.form', compact('roles', 'stores', 'user', 'positions', 'statuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        Gate::authorize('User Update');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'address' => ['nullable', 'string'],
            'employee_number' => ['required', 'string', 'max:255', 'unique:users,employee_number,' . $user->id],
            'status' => ['required', 'string', 'max:255'],
            'join_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date'],
            'position' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'mobile_phone' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', Password::defaults()],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['exists:roles,id'],
            'stores' => ['nullable', 'array'],
            'stores.*' => ['exists:stores,id'],
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $validated['photo'] = $request->file('photo')->store('users', 'public');
        } else {
            // Keep existing photo (don't overwrite with null)
            unset($validated['photo']);
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->fill($validated);

        $user->save();

        // Sync roles
        $roles = $validated['roles'] ?? [];
        $user->roles()->sync($roles);

        // Sync stores
        $stores = $validated['stores'] ?? [];
        $user->stores()->sync($stores);

        return redirect()->route('users.index')->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('User Destroy');

        // Prevent self-deletion
        if (auth()->id() === $user->id) {
            return redirect()->route('users.index')->withErrors(['error' => 'You cannot delete your own account.']);
        }

        // Prevent deleting the last Super Admin
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole && $user->hasRole('Super Admin')) {
            $superAdminCount = User::whereHas('roles', function($q) {
                $q->where('name', 'Super Admin');
            })->count();

            if ($superAdminCount <= 1) {
                return redirect()->route('users.index')->withErrors(['error' => 'Cannot delete the last Super Admin.']);
            }
        }

        // Delete photo from storage
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', "User '{$user->name}' deleted successfully.");
    }
}
