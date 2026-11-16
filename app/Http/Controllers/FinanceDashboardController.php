<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Expense;
use App\Models\FundTransfer;
use App\Models\Income;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\SupplierPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinanceDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('Income List') && !$user->can('Expense List') && !$user->can('Dashboard List')) {
            abort(403, 'Anda tidak memiliki akses ke Dashboard Finance.');
        }

        // Active Store
        $activeStoreId = session('active_store_id') ?? $user->stores()->first()?->id;
        $activeStore = $activeStoreId ? Store::find($activeStoreId) : null;

        // Date Filter Parsing
        $filter = $request->input('filter', 'mtd');
        $startDate = null;
        $endDate = null;

        if ($filter === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
            $filterLabel = 'Hari Ini';
        } elseif ($filter === 'this_week') {
            $startDate = Carbon::now()->startOfWeek()->startOfDay();
            $endDate = Carbon::now()->endOfWeek()->endOfDay();
            $filterLabel = 'Minggu Ini';
        } elseif ($filter === 'last_month') {
            $startDate = Carbon::now()->subMonth()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->subMonth()->endOfMonth()->endOfDay();
            $filterLabel = 'Bulan Lalu';
        } elseif ($filter === 'ytd') {
            $startDate = Carbon::now()->startOfYear()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $filterLabel = 'Tahun Ini (YTD)';
        } elseif ($filter === 'custom') {
            $startStr = $request->input('start_date');
            $endStr = $request->input('end_date');
            $startDate = $startStr ? Carbon::parse($startStr)->startOfDay() : Carbon::today()->startOfDay();
            $endDate = $endStr ? Carbon::parse($endStr)->endOfDay() : Carbon::today()->endOfDay();
            $filterLabel = $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y');
        } else {
            $filter = 'mtd';
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
            $filterLabel = 'Bulan Ini (' . Carbon::now()->isoFormat('MMMM Y') . ')';
        }

        $startDateStr = $startDate ? $startDate->toDateString() : null;
        $endDateStr   = $endDate ? $endDate->toDateString() : null;

        // 1. Real-time Liquidity (Cumulative Kas & Bank)
        $storeEndingBalance = $activeStore ? $activeStore->ending_balance : Store::all()->sum('ending_balance');

        $bankAccount = $activeStoreId ? Account::where('store_id', $activeStoreId)->where('code', '1102')->first() : null;
        $cashAccount = $activeStoreId ? Account::where('store_id', $activeStoreId)->where('code', '1101')->first() : null;
        $qrisAccount = $activeStoreId ? Account::where('store_id', $activeStoreId)->where('code', '1103')->first() : null;

        if ($activeStore && ($activeStore->auto_cash_to_bank || !empty($activeStore->bank_name))) {
            $bankBalance = (float) $activeStore->ending_balance;
            $cashBalance = 0.0;
        } else {
            $cutOff = $activeStore?->initial_balance_date ? Carbon::parse($activeStore->initial_balance_date)->toDateString() : null;
            $bankBalance = $bankAccount ? $bankAccount->calculateBalance($cutOff) : 0;
            $cashBalance = $cashAccount ? $cashAccount->calculateBalance($cutOff) : 0;
        }
        $qrisCutOff = $activeStore?->initial_balance_date ? Carbon::parse($activeStore->initial_balance_date)->toDateString() : null;
        $qrisBalance = $qrisAccount ? $qrisAccount->calculateBalance($qrisCutOff) : 0;

        $bankSubtitle = $activeStore && $activeStore->bank_name 
            ? "{$activeStore->bank_name} - {$activeStore->bank_account_number}" 
            : 'Belum diatur';

        // 2. Period Inflow: Sales Revenue + Incomes
        $salesInflowQuery = Sale::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });
        if ($activeStoreId) {
            $salesInflowQuery->where('store_id', $activeStoreId);
        }
        if ($startDateStr && $endDateStr) {
            $salesInflowQuery->whereBetween(DB::raw('DATE(COALESCE(sale_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $periodSalesInflow = (float) $salesInflowQuery->sum('total_amount');

        $incomesQuery = Income::withoutGlobalScopes();
        if ($activeStoreId) {
            $incomesQuery->where('store_id', $activeStoreId);
        }
        if ($startDateStr && $endDateStr) {
            $incomesQuery->whereBetween('income_date', [$startDateStr, $endDateStr]);
        }
        $periodIncomes = (float) $incomesQuery->sum('amount');
        $totalInflow = $periodSalesInflow + $periodIncomes;

        // 3. Period Outflow: Operating Expenses + Direct Cash Stock + Supplier Payments
        $expensesQuery = Expense::withoutGlobalScopes();
        if ($activeStoreId) {
            $expensesQuery->where('store_id', $activeStoreId);
        }
        if ($startDateStr && $endDateStr) {
            $expensesQuery->whereBetween('expense_date', [$startDateStr, $endDateStr]);
        }
        $periodExpenses = (float) $expensesQuery->sum('amount');

        // Supplier Payments in period
        $supplierPaymentsQuery = SupplierPayment::withoutGlobalScopes();
        if ($activeStoreId) {
            $supplierPaymentsQuery->where('store_id', $activeStoreId);
        }
        if ($startDateStr && $endDateStr) {
            $supplierPaymentsQuery->whereBetween('payment_date', [$startDateStr, $endDateStr]);
        }
        $periodSupplierPayments = (float) $supplierPaymentsQuery->sum('amount');

        // Direct cash stock in
        $directStockInQuery = StockMovement::withoutGlobalScopes()
            ->where('type', 'in')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'approved');
            })
            ->where(function ($q) {
                $q->whereNull('payment_method')
                  ->orWhereNotIn(DB::raw('LOWER(payment_method)'), ['tempo', 'hutang', 'credit']);
            });
        if ($activeStoreId) {
            $directStockInQuery->where('store_id', $activeStoreId);
        }
        if ($startDateStr && $endDateStr) {
            $directStockInQuery->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $periodDirectStockOutflow = (float) $directStockInQuery->sum(DB::raw('COALESCE(NULLIF(paid_amount, 0), total_price, quantity * purchase_price, 0)'));

        $totalOutflow = $periodExpenses + $periodSupplierPayments + $periodDirectStockOutflow;
        $netCashFlow = $totalInflow - $totalOutflow;

        // 4. Breakdown Expenses by Category
        $expensesByCategory = (clone $expensesQuery)
            ->select('category', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->orderByDesc('total_amount')
            ->get();

        // 5. Recent Expenses & Incomes
        $recentExpenses = (clone $expensesQuery)->latest('expense_date')->limit(5)->get();
        $recentIncomes  = (clone $incomesQuery)->latest('income_date')->limit(5)->get();

        // 6. Recent Fund Transfers
        $recentTransfers = FundTransfer::with(['fromAccount', 'toAccount', 'user'])
            ->when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId))
            ->latest('transfer_date')
            ->limit(5)
            ->get();

        return view('finance.dashboard', compact(
            'activeStore',
            'filter',
            'filterLabel',
            'startDate',
            'endDate',
            'storeEndingBalance',
            'bankBalance',
            'cashBalance',
            'qrisBalance',
            'bankSubtitle',
            'periodSalesInflow',
            'periodIncomes',
            'totalInflow',
            'periodExpenses',
            'periodSupplierPayments',
            'periodDirectStockOutflow',
            'totalOutflow',
            'netCashFlow',
            'expensesByCategory',
            'recentExpenses',
            'recentIncomes',
            'recentTransfers'
        ));
    }
}
