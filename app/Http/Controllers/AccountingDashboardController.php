<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountingPeriod;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AccountingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('Accounting CoA List') && !$user->can('Accounting Journal List') && !$user->can('Accounting Ledger View') && !$user->can('Accounting Report View') && !$user->can('Dashboard List')) {
            abort(403, 'Anda tidak memiliki akses ke Dashboard Accounting.');
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
            $filterLabel = 'Bulan Lalu (' . Carbon::now()->subMonth()->isoFormat('MMMM Y') . ')';
        } elseif ($filter === 'ytd') {
            $startDate = Carbon::now()->startOfYear()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $filterLabel = 'Tahun Ini (YTD ' . Carbon::now()->year . ')';
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

        // Helper to query account movement in period
        $calcAccountPeriod = function (string $code) use ($activeStoreId, $startDateStr, $endDateStr) {
            $account = Account::withoutGlobalScopes()
                ->when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId))
                ->where('code', $code)
                ->first();

            return $account ? $account->calculateBalance($startDateStr, $endDateStr) : 0;
        };

        // 1. Profit & Loss Metrics in Period
        $salesRevenue = $calcAccountPeriod('4101');
        $salesDiscount = $calcAccountPeriod('4102');
        $grossRevenue = $salesRevenue - $salesDiscount;

        $cogs = $calcAccountPeriod('5101');
        $grossProfit = $grossRevenue - $cogs;
        $grossMargin = $grossRevenue > 0 ? ($grossProfit / $grossRevenue) * 100 : 0;

        // Operating Expenses (Accounts 6101 to 6105)
        $expenseAccounts = Account::withoutGlobalScopes()
            ->when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId))
            ->where('type', 'expense')
            ->orderBy('code')
            ->get();

        $operatingExpenses = 0;
        $expenseBreakdown = [];
        foreach ($expenseAccounts as $acc) {
            $bal = $acc->calculateBalance($startDateStr, $endDateStr);
            if ($bal > 0) {
                $operatingExpenses += $bal;
                $expenseBreakdown[] = [
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'amount' => $bal,
                ];
            }
        }

        $otherIncome = $calcAccountPeriod('4201');
        $operatingProfit = $grossProfit - $operatingExpenses;
        $netProfit = $operatingProfit + $otherIncome;
        $netMargin = $grossRevenue > 0 ? ($netProfit / $grossRevenue) * 100 : 0;

        // 2. Cumulative Balance Sheet Snapshot (As of Today / Real-time)
        $calcAccountCumulative = function (string $code) use ($activeStoreId) {
            $account = Account::withoutGlobalScopes()
                ->when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId))
                ->where('code', $code)
                ->first();

            return $account ? $account->calculateBalance() : 0;
        };

        $activeStore = $activeStoreId ? Store::find($activeStoreId) : null;
        $cutOffDateStr = $activeStore?->initial_balance_date ? Carbon::parse($activeStore->initial_balance_date)->toDateString() : null;

        $kasToko = ($activeStore && ($activeStore->auto_cash_to_bank || !empty($activeStore->bank_name))) 
            ? 0.0 
            : $calcAccountCumulative('1101');
        $bankToko = ($activeStore && ($activeStore->auto_cash_to_bank || !empty($activeStore->bank_name)))
            ? (float) $activeStore->ending_balance
            : $calcAccountCumulative('1102');
        $piutangQris = $calcAccountPeriod('1103') ?: ($activeStoreId ? (Account::withoutGlobalScopes()->where('store_id', $activeStoreId)->where('code', '1103')->first()?->calculateBalance($cutOffDateStr) ?? 0) : $calcAccountCumulative('1103'));
        $persediaan = $calcAccountCumulative('1201');

        $totalAssets = $kasToko + $bankToko + $piutangQris + $persediaan;
        $totalLiabilities = $calcAccountCumulative('2101'); // Hutang Dagang
        $totalEquity = $calcAccountCumulative('3101') + $calcAccountCumulative('3201') + $netProfit;

        // 3. Health Check: Audit Trial Balance Equilibrium (All Time)
        $journalQuery = JournalItem::query();
        if ($activeStoreId) {
            $journalQuery->whereHas('journalEntry', fn($q) => $q->where('store_id', $activeStoreId));
        }
        $totalDebits = (float) (clone $journalQuery)->sum('debit');
        $totalCredits = (float) (clone $journalQuery)->sum('credit');
        $isBalanced = abs($totalDebits - $totalCredits) < 0.01;

        // 4. Active Accounting Period Status
        $currentPeriod = AccountingPeriod::withoutGlobalScopes()
            ->when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId))
            ->where('start_date', '<=', Carbon::today()->toDateString())
            ->where('end_date', '>=', Carbon::today()->toDateString())
            ->first();

        // 5. Recent Journal Entries
        $recentJournals = JournalEntry::withoutGlobalScopes()
            ->when($activeStoreId, fn($q) => $q->where('store_id', $activeStoreId))
            ->with(['items.account', 'user'])
            ->latest('entry_date')
            ->latest('id')
            ->limit(6)
            ->get();

        return view('accounting.dashboard', compact(
            'activeStore',
            'filter',
            'filterLabel',
            'startDate',
            'endDate',
            'grossRevenue',
            'salesRevenue',
            'salesDiscount',
            'cogs',
            'grossProfit',
            'grossMargin',
            'operatingExpenses',
            'expenseBreakdown',
            'operatingProfit',
            'otherIncome',
            'netProfit',
            'netMargin',
            'kasToko',
            'bankToko',
            'piutangQris',
            'persediaan',
            'totalAssets',
            'totalLiabilities',
            'totalEquity',
            'totalDebits',
            'totalCredits',
            'isBalanced',
            'currentPeriod',
            'recentJournals'
        ));
    }
}
