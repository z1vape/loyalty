<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ExpenseController extends Controller
{
    /**
     * Helper to get selectable accounts for store expenses.
     */
    protected function getSelectableAccounts(int $storeId)
    {
        app(AccountingService::class)->ensureStoreAccounts($storeId);

        return Account::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('type', 'expense')->where('code', '!=', '5101');
                })->orWhereIn('code', ['1104', '1105', '2101', '2102', '2103', '2105', '3101', '3102']);
            })
            ->orderBy('code')
            ->get();
    }

    /**
     * Display a listing of store expenses.
     */
    public function index(Request $request)
    {
        if (!Auth::user()->can('Expense List')) {
            abort(403, 'Anda tidak memiliki akses ke menu Pengeluaran Toko.');
        }

        $activeStoreId = session('active_store_id') ?? Auth::user()->stores()->first()?->id ?? 1;
        $selectableAccounts = $this->getSelectableAccounts($activeStoreId);

        $query = Expense::with(['user', 'account']);

        // Filter: Date Range
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween('expense_date', [
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
        $totalFilteredExpenses = (clone $query)->sum('amount');

        $currentMonthExpenses = Expense::whereMonth('expense_date', now()->month)
            ->whereYear('expense_date', now()->year)
            ->sum('amount');

        $expenses = $query->orderBy('expense_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Get category names from selectable accounts + existing
        $categories = $selectableAccounts->pluck('name')->toArray();
        $existingCategories = Expense::distinct()->pluck('category')->toArray();
        $categories = array_unique(array_merge($categories, $existingCategories));

        return view('expenses.index', compact(
            'expenses',
            'totalFilteredExpenses',
            'currentMonthExpenses',
            'categories',
            'selectableAccounts',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show the form for creating a new store expense.
     */
    public function create()
    {
        if (!Auth::user()->can('Expense Create')) {
            abort(403, 'Anda tidak memiliki akses untuk menambah pengeluaran.');
        }

        $activeStoreId = session('active_store_id') ?? Auth::user()->stores()->first()?->id ?? 1;
        $accounts = $this->getSelectableAccounts($activeStoreId);

        // Group accounts for clear UX
        $operatingExpenses = $accounts->where('type', 'expense');
        $receivableAccounts = $accounts->whereIn('code', ['1104', '1105']);
        $payableAccounts = $accounts->whereIn('code', ['2101', '2102', '2103', '2105']);
        $equityAccounts = $accounts->whereIn('code', ['3101', '3102']);

        return view('expenses.create', compact(
            'accounts',
            'operatingExpenses',
            'receivableAccounts',
            'payableAccounts',
            'equityAccounts'
        ));
    }

    /**
     * Store a newly created expense in storage.
     */
    public function store(Request $request)
    {
        if (!Auth::user()->can('Expense Create')) {
            abort(403, 'Anda tidak memiliki akses untuk menambah pengeluaran.');
        }

        $validated = $request->validate([
            'expense_date' => 'required|date',
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

        // Automatically synchronize category name with Account name if account_id is selected
        if (!empty($validated['account_id'])) {
            $account = Account::withoutGlobalScopes()->find($validated['account_id']);
            if ($account) {
                $validated['category'] = $account->name;
            }
        } elseif (empty($validated['category'])) {
            $validated['category'] = 'Operasional';
        }

        $expense = Expense::create($validated);

        // Record double-entry journal
        app(AccountingService::class)->recordExpenseJournal($expense);

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran toko berhasil ditambahkan dan tercatat pada jurnal akuntansi.');
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense)
    {
        if (!Auth::user()->can('Expense Edit')) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pengeluaran.');
        }

        $storeId = $expense->store_id ?? session('active_store_id') ?? 1;
        $accounts = $this->getSelectableAccounts($storeId);

        $operatingExpenses = $accounts->where('type', 'expense');
        $receivableAccounts = $accounts->whereIn('code', ['1104', '1105']);
        $payableAccounts = $accounts->whereIn('code', ['2101', '2102', '2103', '2105']);
        $equityAccounts = $accounts->whereIn('code', ['3101', '3102']);

        return view('expenses.edit', compact(
            'expense',
            'accounts',
            'operatingExpenses',
            'receivableAccounts',
            'payableAccounts',
            'equityAccounts'
        ));
    }

    /**
     * Update the specified expense in storage.
     */
    public function update(Request $request, Expense $expense)
    {
        if (!Auth::user()->can('Expense Edit')) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah pengeluaran.');
        }

        $validated = $request->validate([
            'expense_date' => 'required|date',
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

        if (empty($expense->store_id)) {
            $validated['store_id'] = session('active_store_id')
                ?? (!empty($validated['account_id']) ? Account::withoutGlobalScopes()->where('id', $validated['account_id'])->value('store_id') : null)
                ?? 1;
        }

        $expense->update($validated);

        // Synchronize and update accounting journal
        app(AccountingService::class)->recordExpenseJournal($expense);

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran toko berhasil diperbarui dan disinkronkan ke jurnal akuntansi.');
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Expense $expense)
    {
        if (!Auth::user()->can('Expense Destroy')) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus pengeluaran.');
        }

        // Purge accounting journal
        app(AccountingService::class)->purgeJournal('expense', $expense->id);

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Pengeluaran toko berhasil dihapus beserta jurnalnya.');
    }
}
