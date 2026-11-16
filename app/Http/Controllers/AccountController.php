<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * Display the Chart of Accounts list.
     */
    public function index(Request $request)
    {
        Gate::authorize('Accounting CoA List');

        $activeStoreId = session('active_store_id');
        if ($activeStoreId) {
            app(\App\Services\AccountingService::class)->ensureStoreAccounts($activeStoreId);
        }

        $query = Account::query();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%")
                  ->orWhere('category', 'LIKE', "%{$search}%");
            });
        }

        $accounts = $query->orderBy('code', 'asc')->get();

        // Calculate current balances and group by type
        $accountsGrouped = $accounts->groupBy('type');

        // Summary counts
        $totalAccounts = $accounts->count();
        $totalAssets = $accounts->where('type', 'asset')->count();
        $totalLiabilities = $accounts->where('type', 'liability')->count();
        $totalEquity = $accounts->where('type', 'equity')->count();
        $totalRevenue = $accounts->where('type', 'revenue')->count();
        $totalExpenses = $accounts->where('type', 'expense')->count();

        return view('accounting.accounts.index', compact(
            'accounts',
            'accountsGrouped',
            'totalAccounts',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'totalRevenue',
            'totalExpenses'
        ));
    }

    /**
     * Show form for creating a new account.
     */
    public function create()
    {
        Gate::authorize('Accounting CoA Create');

        $types = [
            'asset' => 'Aset / Aktiva',
            'liability' => 'Kewajiban / Hutang',
            'equity' => 'Ekuitas / Modal',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban / Biaya',
        ];

        return view('accounting.accounts.create', compact('types'));
    }

    /**
     * Store a newly created account.
     */
    public function store(Request $request)
    {
        Gate::authorize('Accounting CoA Create');

        $storeId = session('active_store_id') ?? 1;

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('accounts')->where(fn ($q) => $q->where('store_id', $storeId)),
            ],
            'name' => 'required|string|max:150',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'category' => 'required|string|max:100',
            'normal_balance' => 'required|in:debit,credit',
            'description' => 'nullable|string|max:1000',
        ], [
            'code.unique' => 'Nomor kode akun sudah digunakan pada toko aktif.',
        ]);

        $validated['store_id'] = $storeId;
        $validated['is_system'] = false;
        $validated['is_active'] = true;

        Account::create($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Akun {$validated['code']} - {$validated['name']} berhasil ditambahkan.");
    }

    /**
     * Show form for editing an existing account.
     */
    public function edit(Account $account)
    {
        Gate::authorize('Accounting CoA Edit');

        $types = [
            'asset' => 'Aset / Aktiva',
            'liability' => 'Kewajiban / Hutang',
            'equity' => 'Ekuitas / Modal',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban / Biaya',
        ];

        return view('accounting.accounts.edit', compact('account', 'types'));
    }

    /**
     * Update an existing account.
     */
    public function update(Request $request, Account $account)
    {
        Gate::authorize('Accounting CoA Edit');

        $storeId = $account->store_id;

        $rules = [
            'name' => 'required|string|max:150',
            'category' => 'required|string|max:100',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ];

        if (!$account->is_system) {
            $rules['code'] = [
                'required',
                'string',
                'max:50',
                Rule::unique('accounts')->where(fn ($q) => $q->where('store_id', $storeId))->ignore($account->id),
            ];
            $rules['type'] = 'required|in:asset,liability,equity,revenue,expense';
            $rules['normal_balance'] = 'required|in:debit,credit';
        }

        $validated = $request->validate($rules);
        $validated['is_active'] = $request->boolean('is_active', true);

        $account->update($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Akun {$account->code} - {$account->name} berhasil diperbarui.");
    }

    /**
     * Delete a custom non-system account.
     */
    public function destroy(Account $account)
    {
        Gate::authorize('Accounting CoA Delete');

        if ($account->is_system) {
            return back()->withErrors(['error' => 'Akun sistem tidak dapat dihapus untuk menjaga integritas pembukuan.']);
        }

        if ($account->journalItems()->exists()) {
            return back()->withErrors(['error' => 'Akun ini tidak dapat dihapus karena sudah memiliki riwayat transaksi jurnal. Anda dapat menonaktifkannya melalui menu edit.']);
        }

        $account->delete();

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Akun {$account->code} berhasil dihapus.");
    }
}
