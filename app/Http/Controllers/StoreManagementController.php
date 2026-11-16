<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class StoreManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('Store List');

        $query = Store::withCount(['users', 'products', 'sales']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $stores = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total_stores' => Store::count(),
            'total_staff' => \App\Models\User::has('stores')->count(),
            'total_products' => \App\Models\Product::count(),
            'total_sales' => \App\Models\Sale::count(),
        ];

        return view('stores.index', compact('stores', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('Store Create');

        $store = new Store();
        $users = \App\Models\User::with('roles')->orderBy('name')->get();

        return view('stores.form', compact('store', 'users'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('Store Store');

        $this->sanitizeInitialBalance($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'auto_cash_to_bank' => ['nullable', 'boolean'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'initial_balance_date' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:50000'],
            'users' => ['nullable', 'array'],
            'users.*' => ['exists:users,id'],
        ]);

        $store = Store::create([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'auto_cash_to_bank' => $request->has('auto_cash_to_bank') ? $request->boolean('auto_cash_to_bank') : (!empty($validated['bank_name'])),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'radius_meters' => $validated['radius_meters'] ?? 100,
            'initial_balance' => $validated['initial_balance'] ?? 0,
            'initial_balance_date' => $validated['initial_balance_date'] ?? now()->toDateString(),
        ]);

        if (!empty($validated['users'])) {
            $store->users()->sync($validated['users']);
        }

        // Record opening balance journal for accounting
        app(\App\Services\AccountingService::class)->recordStoreInitialBalanceJournal($store);

        return redirect()->route('stores.index')->with('success', "Toko '{$store->name}' berhasil ditambahkan.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        Gate::authorize('Store Show');
        
        $store->load(['users.roles', 'products' => fn($q) => $q->take(8)]);
        $store->loadCount(['users', 'products', 'sales']);

        return view('stores.show', compact('store'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        Gate::authorize('Store Edit');

        $store->load('users');
        $users = \App\Models\User::with('roles')->orderBy('name')->get();

        return view('stores.form', compact('store', 'users'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        Gate::authorize('Store Update');

        $this->sanitizeInitialBalance($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'auto_cash_to_bank' => ['nullable', 'boolean'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'initial_balance_date' => ['nullable', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'radius_meters' => ['nullable', 'integer', 'min:10', 'max:50000'],
            'users' => ['nullable', 'array'],
            'users.*' => ['exists:users,id'],
        ]);

        $store->update([
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'auto_cash_to_bank' => $request->boolean('auto_cash_to_bank'),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'radius_meters' => $validated['radius_meters'] ?? 100,
            'initial_balance' => $validated['initial_balance'] ?? 0,
            'initial_balance_date' => $validated['initial_balance_date'] ?? $store->initial_balance_date ?? now()->toDateString(),
        ]);

        $store->users()->sync($validated['users'] ?? []);

        // Record or update opening balance journal for accounting
        app(\App\Services\AccountingService::class)->recordStoreInitialBalanceJournal($store);

        return redirect()->route('stores.index')->with('success', "Toko '{$store->name}' berhasil diubah.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        Gate::authorize('Store Destroy');

        if (Store::count() <= 1) {
            return redirect()->route('stores.index')->withErrors(['error' => 'Tidak dapat menghapus toko terakhir. Sistem membutuhkan minimal 1 toko aktif.']);
        }

        $storeName = $store->name;
        app(\App\Services\AccountingService::class)->purgeJournal('store_initial_balance', $store->id);
        $store->delete();

        return redirect()->route('stores.index')->with('success', "Toko '{$storeName}' berhasil dihapus.");
    }

    /**
     * Sanitize initial_balance string (e.g. from formatted Indonesian currency inputs).
     */
    private function sanitizeInitialBalance(Request $request): void
    {
        if ($request->has('initial_balance')) {
            $raw = $request->input('initial_balance');
            if (is_string($raw)) {
                $raw = trim($raw);
                if (preg_match('/\d+\.\d{3}/', $raw) || substr_count($raw, '.') > 1) {
                    $raw = str_replace('.', '', $raw);
                }
                $clean = preg_replace('/[^\d.]/', '', str_replace(',', '.', $raw));
                $request->merge(['initial_balance' => $clean !== '' ? (float) $clean : 0]);
            }
        }
    }
}
