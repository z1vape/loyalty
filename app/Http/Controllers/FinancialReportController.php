<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalItem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

class FinancialReportController extends Controller
{
    /**
     * Profit and Loss Statement (Laporan Laba Rugi).
     */
    public function profitAndLoss(Request $request)
    {
        Gate::authorize('Accounting Report View');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());
        if ($startDate > $endDate) {
            $startDate = $endDate;
        }

        // Auto-sync any unjournaled sales, stock in, expenses, incomes in this date range
        $this->autoSyncUnjournaledTransactions($startDate, $endDate);

        $activeStoreId = session('active_store_id');
        $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : null;
        $storeName = $activeStore ? $activeStore->name : 'Semua Cabang Toko';

        // 1. Revenue Accounts (type: revenue)
        $revenueAccounts = Account::where('type', 'revenue')->orderBy('code')->get();
        $grossSales = 0;
        $salesDiscounts = 0;
        $otherIncomes = 0;

        foreach ($revenueAccounts as $acc) {
            $debit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('debit');

            $credit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('credit');

            if ($acc->code === '4101') {
                // Pendapatan Penjualan
                $grossSales += ($credit - $debit);
                $acc->period_balance = ($credit - $debit);
            } elseif ($acc->code === '4102') {
                // Diskon Penjualan (Contra)
                $salesDiscounts += ($debit - $credit);
                $acc->period_balance = ($debit - $credit);
            } else {
                $otherIncomes += ($credit - $debit);
                $acc->period_balance = ($credit - $debit);
            }
        }

        $netSales = $grossSales - $salesDiscounts;

        // 2. Cost of Goods Sold / HPP (code: 5101 or type: expense with category HPP)
        $cogsAccounts = Account::where('code', '5101')->get();
        $totalCogs = 0;
        foreach ($cogsAccounts as $cogsAccount) {
            $cogsDebit = (float) JournalItem::where('account_id', $cogsAccount->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('debit');
            $cogsCredit = (float) JournalItem::where('account_id', $cogsAccount->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('credit');
            $totalCogs += max(0, $cogsDebit - $cogsCredit);
        }

        $grossProfit = $netSales - $totalCogs;

        // 3. Operating Expenses (type: expense and code != 5101)
        $expenseAccounts = Account::where('type', 'expense')->where('code', '!=', '5101')->orderBy('code')->get();
        $totalOperatingExpenses = 0;

        foreach ($expenseAccounts as $acc) {
            $debit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('debit');

            $credit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('credit');

            $acc->period_balance = ($debit - $credit);
            $totalOperatingExpenses += $acc->period_balance;
        }

        $operatingProfit = $grossProfit - $totalOperatingExpenses;
        $netProfit = $operatingProfit + $otherIncomes;

        return view('accounting.reports.profit_and_loss', compact(
            'startDate',
            'endDate',
            'storeName',
            'grossSales',
            'salesDiscounts',
            'netSales',
            'totalCogs',
            'grossProfit',
            'expenseAccounts',
            'totalOperatingExpenses',
            'operatingProfit',
            'otherIncomes',
            'netProfit'
        ));
    }

    /**
     * Balance Sheet Statement (Laporan Neraca).
     */
    public function balanceSheet(Request $request)
    {
        Gate::authorize('Accounting Report View');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', $request->query('as_of_date', Carbon::now()->toDateString()));
        if ($startDate > $endDate) {
            $startDate = $endDate;
        }
        $asOfDate = $endDate;

        // Auto-sync unjournaled transactions up to endDate
        $this->autoSyncUnjournaledTransactions(null, $endDate);

        $activeStoreId = session('active_store_id');
        $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : null;
        $storeName = $activeStore ? $activeStore->name : 'Semua Cabang Toko';

        // 1. Assets (type: asset)
        $assetAccounts = Account::where('type', 'asset')->orderBy('code')->get();
        $totalAssets = 0;
        foreach ($assetAccounts as $acc) {
            $debit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<=', $endDate))
                ->sum('debit');
            $credit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<=', $endDate))
                ->sum('credit');
            $acc->balance = ($debit - $credit);
            $totalAssets += $acc->balance;
        }

        // 2. Liabilities (type: liability)
        $liabilityAccounts = Account::where('type', 'liability')->orderBy('code')->get();
        $totalLiabilities = 0;
        foreach ($liabilityAccounts as $acc) {
            $debit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<=', $endDate))
                ->sum('debit');
            $credit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<=', $endDate))
                ->sum('credit');
            $acc->balance = ($credit - $debit);
            $totalLiabilities += $acc->balance;
        }

        // 3. Equity (type: equity)
        $equityAccounts = Account::where('type', 'equity')->orderBy('code')->get();
        $totalBaseEquity = 0;
        foreach ($equityAccounts as $acc) {
            $debit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<=', $endDate))
                ->sum('debit');
            $credit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<=', $endDate))
                ->sum('credit');
            $acc->balance = ($credit - $debit);
            $totalBaseEquity += $acc->balance;
        }

        // 4. Retained Earnings / Prior Period Earnings (sebelum $startDate)
        $priorRevenueCredit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
            ->sum('credit');
        $priorRevenueDebit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
            ->sum('debit');
        $priorNetRevenue = $priorRevenueCredit - $priorRevenueDebit;

        $priorExpenseDebit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
            ->sum('debit');
        $priorExpenseCredit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
            ->sum('credit');
        $priorNetExpense = $priorExpenseDebit - $priorExpenseCredit;

        $retainedEarnings = $priorNetRevenue - $priorNetExpense;

        // 5. Current Period Net Earnings ($startDate s/d $endDate)
        $periodRevenueCredit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('credit');
        $periodRevenueDebit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'revenue'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('debit');
        $periodNetRevenue = $periodRevenueCredit - $periodRevenueDebit;

        $periodExpenseDebit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('debit');
        $periodExpenseCredit = (float) JournalItem::whereHas('account', fn($q) => $q->where('type', 'expense'))
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('credit');
        $periodNetExpense = $periodExpenseDebit - $periodExpenseCredit;

        $currentPeriodEarnings = $periodNetRevenue - $periodNetExpense;

        $totalEquity = $totalBaseEquity + $retainedEarnings + $currentPeriodEarnings;

        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;
        $isBalanced = abs($totalAssets - $totalLiabilitiesAndEquity) < 0.05;

        return view('accounting.reports.balance_sheet', compact(
            'startDate',
            'endDate',
            'asOfDate',
            'storeName',
            'assetAccounts',
            'totalAssets',
            'liabilityAccounts',
            'totalLiabilities',
            'equityAccounts',
            'totalBaseEquity',
            'retainedEarnings',
            'currentPeriodEarnings',
            'totalEquity',
            'totalLiabilitiesAndEquity',
            'isBalanced'
        ));
    }

    /**
     * Trial Balance Statement (Neraca Saldo).
     */
    public function trialBalance(Request $request)
    {
        Gate::authorize('Accounting Report View');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', $request->query('as_of_date', Carbon::now()->toDateString()));
        if ($startDate > $endDate) {
            $startDate = $endDate;
        }
        $asOfDate = $endDate;

        // Auto-sync unjournaled transactions in date range
        $this->autoSyncUnjournaledTransactions($startDate, $endDate);

        $activeStoreId = session('active_store_id');
        $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : null;
        $storeName = $activeStore ? $activeStore->name : 'Semua Cabang Toko';

        $accounts = Account::orderBy('code')->get();

        $totalOpeningDebit = 0;
        $totalOpeningCredit = 0;
        $totalPeriodDebit = 0;
        $totalPeriodCredit = 0;
        $totalDebitBalance = 0;
        $totalCreditBalance = 0;

        foreach ($accounts as $acc) {
            // 1. Saldo Awal (sebelum startDate)
            $priorDebit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
                ->sum('debit');
            $priorCredit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
                ->sum('credit');

            $priorNet = $priorDebit - $priorCredit;
            if ($priorNet > 0) {
                $acc->opening_debit = $priorNet;
                $acc->opening_credit = 0;
                $totalOpeningDebit += $priorNet;
            } elseif ($priorNet < 0) {
                $acc->opening_debit = 0;
                $acc->opening_credit = abs($priorNet);
                $totalOpeningCredit += abs($priorNet);
            } else {
                $acc->opening_debit = 0;
                $acc->opening_credit = 0;
            }

            $acc->opening_balance = ($acc->normal_balance === 'debit')
                ? ($priorDebit - $priorCredit)
                : ($priorCredit - $priorDebit);

            // 2. Mutasi Periode (antara startDate dan endDate)
            $acc->period_debit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('debit');

            $acc->period_credit = (float) JournalItem::where('account_id', $acc->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
                ->sum('credit');

            $totalPeriodDebit += $acc->period_debit;
            $totalPeriodCredit += $acc->period_credit;

            // 3. Saldo Akhir Kumulatif (per endDate)
            $cumDebit = $priorDebit + $acc->period_debit;
            $cumCredit = $priorCredit + $acc->period_credit;
            $netEnding = $cumDebit - $cumCredit;

            if ($netEnding > 0) {
                $acc->ending_debit = $netEnding;
                $acc->ending_credit = 0;
                $totalDebitBalance += $netEnding;
            } elseif ($netEnding < 0) {
                $acc->ending_debit = 0;
                $acc->ending_credit = abs($netEnding);
                $totalCreditBalance += abs($netEnding);
            } else {
                $acc->ending_debit = 0;
                $acc->ending_credit = 0;
            }
        }

        $isBalanced = abs($totalDebitBalance - $totalCreditBalance) < 0.05;
        $isPeriodBalanced = abs($totalPeriodDebit - $totalPeriodCredit) < 0.05;

        return view('accounting.reports.trial_balance', compact(
            'startDate',
            'endDate',
            'asOfDate',
            'storeName',
            'accounts',
            'totalOpeningDebit',
            'totalOpeningCredit',
            'totalPeriodDebit',
            'totalPeriodCredit',
            'totalDebitBalance',
            'totalCreditBalance',
            'isBalanced',
            'isPeriodBalanced'
        ));
    }

    /**
     * Cash Flow Statement (Laporan Arus Kas).
     */
    public function cashFlow(Request $request)
    {
        Gate::authorize('Accounting Report View');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());
        if ($startDate > $endDate) {
            $startDate = $endDate;
        }

        // Auto-sync unjournaled transactions up to endDate
        $this->autoSyncUnjournaledTransactions(null, $endDate);

        $activeStoreId = session('active_store_id');
        $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : null;
        $storeName = $activeStore ? $activeStore->name : 'Semua Cabang Toko';

        // Cash and Bank accounts (Kas Toko 1101, Bank 1102, Piutang QRIS 1103)
        $cashAccountIds = Account::whereIn('code', ['1101', '1102', '1103'])->pluck('id');

        // Opening cash prior to startDate
        $priorDebit = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
            ->sum('debit');
        $priorCredit = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
            ->sum('credit');
        $openingCash = $priorDebit - $priorCredit;

        // Fallback: If no journal entry exists yet for opening cash, check store initial balance
        if ($openingCash == 0) {
            $activeStoreId = session('active_store_id');
            $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : \App\Models\Store::first();
            if ($activeStore && (float) $activeStore->initial_balance > 0) {
                $cutOff = $activeStore->initial_balance_date ? Carbon::parse($activeStore->initial_balance_date) : null;
                if (!$cutOff || $cutOff->lte(Carbon::parse($startDate))) {
                    $openingCash = (float) $activeStore->initial_balance;
                }
            }
        }

        // Inflows and Outflows in period
        $salesInflows = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->where('source_type', 'sale')->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('debit');

        $capitalInflows = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->where('source_type', 'store_initial_balance')->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('debit');

        $otherInflows = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->where('source_type', 'income')->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('debit');

        $expenseOutflows = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->where('source_type', 'expense')->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('credit');

        $stockInPurchases = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->where('source_type', 'stock_movement')->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('credit');

        $debtPayments = (float) JournalItem::whereIn('account_id', $cashAccountIds)
            ->whereHas('journalEntry', fn($q) => $q->where('source_type', 'supplier_payment')->whereDate('entry_date', '>=', $startDate)->whereDate('entry_date', '<=', $endDate))
            ->sum('credit');

        $totalInflows = $salesInflows + $otherInflows + $capitalInflows;
        $totalOutflows = $expenseOutflows + $stockInPurchases + $debtPayments;
        $netCashFlow = $totalInflows - $totalOutflows;
        $endingCash = $openingCash + $netCashFlow;

        return view('accounting.reports.cash_flow', compact(
            'startDate',
            'endDate',
            'storeName',
            'openingCash',
            'salesInflows',
            'capitalInflows',
            'otherInflows',
            'totalInflows',
            'expenseOutflows',
            'stockInPurchases',
            'debtPayments',
            'totalOutflows',
            'netCashFlow',
            'endingCash'
        ));
    }

    /**
     * Manual sync action triggered from report UI.
     */
    public function syncJournals(Request $request)
    {
        Gate::authorize('Accounting Report View');

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $count = $this->autoSyncUnjournaledTransactions($startDate, $endDate);

        $msg = $count > 0 
            ? "Berhasil menyinkronkan {$count} transaksi ke jurnal akuntansi."
            : "Semua transaksi sudah tersinkronisasi ke jurnal akuntansi.";

        return back()->with('success', $msg);
    }

    /**
     * Auto-sync unjournaled transactions (sales, stock in, expenses, incomes)
     * so that financial reports are always complete and match dashboard.
     */
    protected function autoSyncUnjournaledTransactions(?string $startDate = null, ?string $endDate = null): int
    {
        $syncedCount = 0;
        $accountingService = app(\App\Services\AccountingService::class);

        $hasOutMovements = \Illuminate\Support\Facades\DB::table('stock_movements')
            ->where('type', 'out')
            ->exists();

        // 1a. List of all reference numbers that have active (non-cancelled) Stock Out movements
        $activeOutMovementRefs = collect();
        if ($hasOutMovements) {
            $activeOutMovementRefs = \Illuminate\Support\Facades\DB::table('stock_movements')
                ->where('type', 'out')
                ->where(function ($sub) {
                    $sub->whereNull('status')
                        ->orWhereNotIn('status', ['cancelled', 'canceled', 'batal', 'void']);
                })
                ->where(function ($sub) {
                    $sub->whereNull('notes')
                        ->orWhere(function ($n) {
                            $n->where('notes', 'NOT LIKE', '%[DIBATALKAN%')
                              ->where('notes', 'NOT LIKE', '%BATAL%')
                              ->where('notes', 'NOT LIKE', '%VOID%');
                        });
                })
                ->pluck('reference_number')
                ->filter()
                ->unique();
        }

        // 1c. Sync cancellation if stock_movements explicitly marked cancelled
        $cancelledMovementRefs = \Illuminate\Support\Facades\DB::table('stock_movements')
            ->where(function ($q) {
                $q->whereIn('status', ['cancelled', 'canceled', 'batal', 'void'])
                  ->orWhere('notes', 'LIKE', '%[DIBATALKAN%');
            })
            ->pluck('reference_number')
            ->filter()
            ->unique();

        if ($cancelledMovementRefs->isNotEmpty()) {
            \App\Models\Sale::withoutGlobalScopes()
                ->whereIn('reference_number', $cancelledMovementRefs)
                ->where('status', '!=', 'cancelled')
                ->update(['status' => 'cancelled']);
        }

        // 1d. Purge ANY journal entries for cancelled sales (both source_type 'sale' and 'sale_cancellation')
        $allCancelledSaleIds = \App\Models\Sale::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereIn('status', ['cancelled', 'canceled', 'batal', 'void'])
                  ->orWhereNotNull('cancelled_at')
                  ->orWhereNotNull('cancellation_reason')
                  ->orWhere('notes', 'LIKE', '%[DIBATALKAN%');
            })
            ->pluck('id');

        foreach ($allCancelledSaleIds as $saleId) {
            $accountingService->purgeJournal('sale', $saleId);
            $accountingService->purgeJournal('sale_cancellation', $saleId);
        }

        // Also purge by reference for any reference not in activeOutMovementRefs
        $allInvalidRefs = $cancelledMovementRefs->merge(
            \App\Models\Sale::withoutGlobalScopes()
                ->where('status', 'cancelled')
                ->pluck('reference_number')
        )->unique();

        foreach ($allInvalidRefs as $ref) {
            $accountingService->purgeJournalByReference($ref);
        }

        // 1f. Reconcile sale total_amount with sum of active out-movements
        if ($hasOutMovements && $activeOutMovementRefs->isNotEmpty()) {
            $activeSales = \App\Models\Sale::withoutGlobalScopes()
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
                })
                ->whereIn('reference_number', $activeOutMovementRefs)
                ->get();

            foreach ($activeSales as $activeSale) {
                $movementSum = (float) \Illuminate\Support\Facades\DB::table('stock_movements')
                    ->where('reference_number', $activeSale->reference_number)
                    ->where('type', 'out')
                    ->where(function ($sub) {
                        $sub->whereNull('status')
                            ->orWhereNotIn('status', ['cancelled', 'canceled', 'batal', 'void']);
                    })
                    ->sum(\Illuminate\Support\Facades\DB::raw('COALESCE(total_price, (quantity * selling_price) - COALESCE(discount, 0), 0)'));

                if ($movementSum > 0 && abs((float)$activeSale->total_amount - $movementSum) > 0.01) {
                    $activeSale->total_amount = $movementSum;
                    $activeSale->save();

                    $accountingService->purgeJournal('sale', $activeSale->id);
                    $accountingService->purgeJournalByReference($activeSale->reference_number);
                    $accountingService->recordSaleJournal($activeSale);
                }
            }
        }

        // 1g. Unsynced Sales (only truly active, non-cancelled sales)
        $salesQuery = \App\Models\Sale::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->where(function ($q) {
                $q->whereNull('notes')->orWhere('notes', 'NOT LIKE', '%[DIBATALKAN%');
            })
            ->whereNull('cancelled_at')
            ->whereNull('cancellation_reason')
            ->whereNotIn('id', function ($q) {
                $q->select('source_id')
                    ->from('journal_entries')
                    ->where('source_type', 'sale')
                    ->whereNotNull('source_id');
            });

        if ($startDate && $endDate) {
            $salesQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(sale_date, created_at))'), [$startDate, $endDate]);
            });
        } elseif ($endDate) {
            $salesQuery->where(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(sale_date, created_at))'), '<=', $endDate);
        }

        $unsyncedSales = $salesQuery->get();
        foreach ($unsyncedSales as $sale) {
            try {
                if ($accountingService->recordSaleJournal($sale)) {
                    $syncedCount++;
                }
            } catch (\Throwable $e) {
                // Ignore individual failure
            }
        }

        // 2. Unsynced Expenses
        $expenseQuery = \App\Models\Expense::withoutGlobalScopes()
            ->whereNotIn('id', function ($q) {
                $q->select('source_id')
                    ->from('journal_entries')
                    ->where('source_type', 'expense')
                    ->whereNotNull('source_id');
            });

        if ($startDate && $endDate) {
            $expenseQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(expense_date, created_at))'), [$startDate, $endDate]);
            });
        } elseif ($endDate) {
            $expenseQuery->where(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(expense_date, created_at))'), '<=', $endDate);
        }

        $unsyncedExpenses = $expenseQuery->get();
        foreach ($unsyncedExpenses as $expense) {
            try {
                if ($accountingService->recordExpenseJournal($expense)) {
                    $syncedCount++;
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        // 3. Unsynced Incomes
        if (\Illuminate\Support\Facades\Schema::hasTable('incomes')) {
            $incomeQuery = \App\Models\Income::withoutGlobalScopes()
                ->whereNotIn('id', function ($q) {
                    $q->select('source_id')
                        ->from('journal_entries')
                        ->where('source_type', 'income')
                        ->whereNotNull('source_id');
                });

            if ($startDate && $endDate) {
                $incomeQuery->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(income_date, created_at))'), [$startDate, $endDate]);
                });
            } elseif ($endDate) {
                $incomeQuery->where(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(income_date, created_at))'), '<=', $endDate);
            }

            $unsyncedIncomes = $incomeQuery->get();
            foreach ($unsyncedIncomes as $income) {
                try {
                    if ($accountingService->recordIncomeJournal($income)) {
                        $syncedCount++;
                    }
                } catch (\Throwable $e) {
                    // Ignore
                }
            }
        }

        // 4. Unsynced Stock In (Approved)
        $stockInQuery = \App\Models\StockMovement::withoutGlobalScopes()
            ->where('type', 'in')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'approved');
            })
            ->whereNotIn('id', function ($q) {
                $q->select('source_id')
                    ->from('journal_entries')
                    ->where('source_type', 'stock_movement')
                    ->whereNotNull('source_id');
            });

        if ($startDate && $endDate) {
            $stockInQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDate, $endDate]);
            });
        } elseif ($endDate) {
            $stockInQuery->where(\Illuminate\Support\Facades\DB::raw('DATE(COALESCE(movement_date, created_at))'), '<=', $endDate);
        }

        $unsyncedStockIn = $stockInQuery->get();
        foreach ($unsyncedStockIn as $stockIn) {
            try {
                if ($accountingService->recordStockInJournal($stockIn)) {
                    $syncedCount++;
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        return $syncedCount;
    }
}
