<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Income;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class IncomeController extends Controller
{
    /**
     * Helper to get selectable accounts for store incomes.
     */
    protected function getSelectableAccounts(int $storeId)
    {
        app(AccountingService::class)->ensureStoreAccounts($storeId);

        return Account::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('type', 'revenue')->whereNotIn('code', ['4101', '4102']);
                })->orWhereIn('code', ['1104', '1105', '2102', '3101']);
            })
            ->orderBy('code')
            ->get();
    }

    /**
     * Display a listing of store incomes.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('Income List')) {
            abort(403, 'Anda tidak memiliki akses ke menu Pemasukan Toko.');
        }

        $activeStoreId = session('active_store_id') ?? Auth::user()->stores()->first()?->id ?? 1;
        $selectableAccounts = $this->getSelectableAccounts($activeStoreId);

        $query = Income::with(['user', 'account']);

        // Filter: Date Range
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween('income_date', [
                Carbon::parse($startDate)->startOfDay(),
                Carbon::parse($endDate)->endOfDay()
            ]);
        }

        // Filter: Category / Account
        if ($request->filled('category')) {
            $cat = $request->category;
            $query->where(function ($q) use ($cat) {
                $q->where('category', $cat)
                  ->orWhereHas('account', fn($sq) => $sq->where('name', $cat)->orWhere('code', $cat));
            });
        }

        // Filter: Payment Method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter: Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Summary Calculations
        $totalFilteredIncomes = (clone $query)->sum('amount');

        $currentMonthIncomes = Income::whereMonth('income_date', now()->month)
            ->whereYear('income_date', now()->year)
            ->sum('amount');

        $incomes = $query->orderBy('income_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Distinct categories
        $categories = $selectableAccounts->pluck('name')->toArray();
        $existingCategories = Income::distinct()->pluck('category')->toArray();
        $categories = array_unique(array_merge($categories, $existingCategories));

        return view('incomes.index', compact(
            'incomes',
            'totalFilteredIncomes',
            'currentMonthIncomes',
            'categories',
            'selectableAccounts',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show the form for creating a new store income.
     */
    public function create()
    {
        if (!Auth::user()->can('Income Create')) {
            abort(403, 'Anda tidak memiliki akses untuk menambah pemasukan toko.');
        }

        $activeStoreId = session('active_store_id') ?? Auth::user()->stores()->first()?->id ?? 1;
        $accounts = $this->getSelectableAccounts($activeStoreId);

        $revenueAccounts = $accounts->where('type', 'revenue');
        $receivableAccounts = $accounts->whereIn('code', ['1104', '1105']);
        $capitalAccounts = $accounts->whereIn('code', ['2102', '3101']);

        return view('incomes.create', compact(
            'accounts',
            'revenueAccounts',
            'receivableAccounts',
            'capitalAccounts'
        ));
    }

    /**
     * Store a newly created income in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('Income Create')) {
            abort(403, 'Anda tidak memiliki akses untuk menambah pemasukan toko.');
        }

        $validated = $request->validate([
            'income_date' => 'required|date',
            'account_id' => 'nullable|exists:accounts,id',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['user_id'] = Auth::id();

        // Ensure store_id is accurately bound to active store or selected account
        $storeId = $request->input('store_id')
            ?? session('active_store_id')
            ?? (!empty($validated['account_id']) ? Account::withoutGlobalScopes()->where('id', $validated['account_id'])->value('store_id') : null)
            ?? Auth::user()->stores()->first()?->id
            ?? 1;
        $validated['store_id'] = $storeId;

        if (!empty($validated['account_id'])) {
            $account = Account::withoutGlobalScopes()->find($validated['account_id']);
            if ($account) {
                $validated['category'] = $account->name;
            }
        } elseif (empty($validated['category'])) {
            $validated['category'] = 'Lain-lain';
        }

        $income = Income::create($validated);

        // Record double-entry journal
        app(AccountingService::class)->recordIncomeJournal($income);

        return redirect()->route('incomes.index')
            ->with('success', 'Pemasukan toko berhasil ditambahkan dan tercatat pada jurnal akuntansi.');
    }

    /**
     * Show the form for editing the specified income.
     */
    public function edit(Income $income)
    {
        if (!Auth::user()->can('Income Edit')) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pemasukan toko.');
        }

        $storeId = $income->store_id ?? session('active_store_id') ?? 1;
        $accounts = $this->getSelectableAccounts($storeId);

        $revenueAccounts = $accounts->where('type', 'revenue');
        $receivableAccounts = $accounts->whereIn('code', ['1104', '1105']);
        $capitalAccounts = $accounts->whereIn('code', ['2102', '3101']);

        return view('incomes.edit', compact(
            'income',
            'accounts',
            'revenueAccounts',
            'receivableAccounts',
            'capitalAccounts'
        ));
    }

    /**
     * Update the specified income in storage.
     */
    public function update(Request $request, Income $income)
    {
        if (!Auth::user()->can('Income Edit')) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pemasukan toko.');
        }

        $validated = $request->validate([
            'income_date' => 'required|date',
            'account_id' => 'nullable|exists:accounts,id',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,transfer',
            'notes' => 'nullable|string|max:1000',
        ]);

        if (!empty($validated['account_id'])) {
            $account = Account::withoutGlobalScopes()->find($validated['account_id']);
            if ($account) {
                $validated['category'] = $account->name;
            }
        }

        if (empty($income->store_id)) {
            $validated['store_id'] = session('active_store_id')
                ?? (!empty($validated['account_id']) ? Account::withoutGlobalScopes()->where('id', $validated['account_id'])->value('store_id') : null)
                ?? 1;
        }

        $income->update($validated);

        // Synchronize and update accounting journal
        app(AccountingService::class)->recordIncomeJournal($income);

        return redirect()->route('incomes.index')
            ->with('success', 'Pemasukan toko berhasil diperbarui dan disinkronkan ke jurnal akuntansi.');
    }

    /**
     * Remove the specified income from storage.
     */
    public function destroy(Income $income)
    {
        if (!Auth::user()->can('Income Destroy')) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus pemasukan toko.');
        }

        // Purge accounting journal
        app(AccountingService::class)->purgeJournal('income', $income->id);

        $income->delete();

        return redirect()->route('incomes.index')
            ->with('success', 'Pemasukan toko berhasil dihapus beserta jurnalnya.');
    }
}
