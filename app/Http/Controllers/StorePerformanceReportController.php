<?php

namespace App\Http\Controllers;

use App\Exports\StorePerformanceExport;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class StorePerformanceReportController extends Controller
{
    /**
     * Display the Executive BI Store Performance Dashboard.
     */
    public function index(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->getStorePerformanceData($request);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json($data);
        }

        return view('reports.store_performance', $data);
    }

    /**
     * Export the BI Store Performance Report to Excel (.xlsx).
     */
    public function export(Request $request)
    {
        $this->authorizeAccess();

        $data = $this->getStorePerformanceData($request);
        $fileName = 'laporan-bi-performa-toko-' . ($data['startDate'] ?? date('Y-m-d')) . '-sd-' . ($data['endDate'] ?? date('Y-m-d')) . '.xlsx';

        return Excel::download(
            new StorePerformanceExport($data),
            $fileName
        );
    }

    /**
     * Check if current user has permission to access store performance report.
     */
    protected function authorizeAccess(): void
    {
        $user = auth()->user();

        $hasAccess = $user->hasRole('Super Admin')
            || $user->can('Report List')
            || $user->can('Report Store Performance')
            || $user->can('Report SKU')
            || $user->can('Report Category')
            || $user->can('Report Staff')
            || $user->can('Store List')
            || $user->can('Dashboard List')
            || $user->can('Accounting Report View');

        if (!$hasAccess) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melihat Laporan BI Performa Toko.');
        }
    }

    /**
     * Gather all BI performance metrics, comparisons, and chart datasets across stores.
     */
    public function getStorePerformanceData(Request $request): array
    {
        // 1. Parse Period Filter
        $filter = $request->input('filter', 'mtd'); // default: this month
        $startDate = null;
        $endDate = null;

        if ($filter === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
            $filterLabel = 'Hari Ini (' . $startDate->isoFormat('D MMMM Y') . ')';
        } elseif ($filter === 'yesterday') {
            $startDate = Carbon::yesterday()->startOfDay();
            $endDate = Carbon::yesterday()->endOfDay();
            $filterLabel = 'Kemarin (' . $startDate->isoFormat('D MMMM Y') . ')';
        } elseif ($filter === 'this_week') {
            $startDate = Carbon::now()->subDays(6)->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $filterLabel = '7 Hari Terakhir (' . $startDate->isoFormat('D MMM') . ' - ' . $endDate->isoFormat('D MMM Y') . ')';
        } elseif ($filter === 'last_month') {
            $startDate = Carbon::now()->subMonth()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->subMonth()->endOfMonth()->endOfDay();
            $filterLabel = 'Bulan Lalu (' . $startDate->isoFormat('MMMM Y') . ')';
        } elseif ($filter === 'ytd') {
            $startDate = Carbon::now()->startOfYear()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
            $filterLabel = 'Tahun Ini (' . $startDate->format('Y') . ')';
        } elseif ($filter === 'custom') {
            $startStr = $request->input('start_date');
            $endStr = $request->input('end_date');

            $startDate = $startStr ? Carbon::parse($startStr)->startOfDay() : Carbon::today()->startOfDay();
            $endDate = $endStr ? Carbon::parse($endStr)->endOfDay() : Carbon::today()->endOfDay();
            $filterLabel = $startDate->isoFormat('D MMM Y') . ' - ' . $endDate->isoFormat('D MMM Y');
        } else {
            $filter = 'mtd';
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
            $filterLabel = 'Bulan Ini (' . Carbon::now()->isoFormat('MMMM Y') . ')';
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();

        // 2. Retrieve All Active Stores
        $allStores = Store::withoutGlobalScopes()->orderBy('id')->get();
        $selectedStoreId = $request->input('store_id', 'all');

        $storesData = [];

        foreach ($allStores as $store) {
            $sId = $store->id;

            // --- Sales & Revenue ---
            $sales = Sale::withoutGlobalScopes()
                ->where('store_id', $sId)
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
                })
                ->whereBetween(DB::raw('DATE(COALESCE(sale_date, created_at))'), [$startDateStr, $endDateStr])
                ->get();

            $totalSales = (float) $sales->sum('total_amount');
            $trxCount = $sales->count();

            // Calculate Payment Methods Breakdown
            $paymentCash = 0;
            $paymentTransfer = 0;
            $paymentQris = 0;
            $paymentSplit = 0;

            foreach ($sales as $sale) {
                $m = strtolower($sale->payment_method ?? 'cash');
                $amt = (float) $sale->total_amount;

                if ($m === 'split' && !empty($sale->payment_splits) && is_array($sale->payment_splits)) {
                    $paymentSplit += $amt;
                    foreach ($sale->payment_splits as $split) {
                        $spMethod = strtolower($split['method'] ?? 'cash');
                        $spAmt = (float) ($split['amount'] ?? 0);
                        if ($spMethod === 'transfer') {
                            $paymentTransfer += $spAmt;
                        } elseif (in_array($spMethod, ['qris', 'debit', 'credit', 'edc'])) {
                            $paymentQris += $spAmt;
                        } else {
                            $paymentCash += $spAmt;
                        }
                    }
                } elseif ($m === 'transfer') {
                    $paymentTransfer += $amt;
                } elseif (in_array($m, ['qris', 'debit', 'credit', 'edc'])) {
                    $paymentQris += $amt;
                } else {
                    $paymentCash += $amt;
                }
            }

            // --- COGS (HPP) & Units Sold ---
            $itemsData = DB::table('sale_items')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->leftJoin('products', 'sale_items.product_id', '=', 'products.id')
                ->where('sales.store_id', $sId)
                ->where(function ($q) {
                    $q->whereNull('sales.status')->orWhere('sales.status', '!=', 'cancelled');
                })
                ->whereBetween(DB::raw('DATE(COALESCE(sales.sale_date, sales.created_at))'), [$startDateStr, $endDateStr])
                ->selectRaw('
                    COALESCE(SUM(sale_items.quantity), 0) as total_units,
                    COALESCE(SUM(sale_items.quantity * COALESCE(products.purchase_price, 0)), 0) as total_cogs
                ')
                ->first();

            $totalUnitsSold = (int) ($itemsData->total_units ?? 0);
            $totalCogs = (float) ($itemsData->total_cogs ?? 0);

            // Fallback for COGS if sales items were not linked to product purchase prices
            if ($totalSales > 0 && $totalCogs <= 0) {
                // Approximate standard retail HPP as 75% if purchase_price is zero
                $totalCogs = $totalSales * 0.75;
            }

            $grossProfit = $totalSales - $totalCogs;
            $grossMargin = $totalSales > 0 ? ($grossProfit / $totalSales) * 100 : 0;
            $aov = $trxCount > 0 ? ($totalSales / $trxCount) : 0;

            // --- Operating Expenses ---
            $expensesQuery = Expense::withoutGlobalScopes()
                ->where(function ($q) use ($sId) {
                    $q->where('store_id', $sId);
                    if ($sId == 1) {
                        $q->orWhereNull('store_id');
                    }
                })
                ->where(function ($q) {
                    $q->whereHas('account', function ($aq) {
                        $aq->where('type', 'expense')->where('code', '!=', '5101');
                    })->orWhere(function ($oq) {
                        $oq->whereNull('account_id')
                            ->where(function ($sq) {
                                $sq->whereNull('category')
                                   ->orWhereRaw("LOWER(category) NOT LIKE '%hutang%' AND LOWER(category) NOT LIKE '%piutang%' AND LOWER(category) NOT LIKE '%talangan%' AND LOWER(category) NOT LIKE '%kasbon%'");
                            });
                    });
                })
                ->whereBetween(DB::raw('DATE(COALESCE(expense_date, created_at))'), [$startDateStr, $endDateStr]);

            $totalExpenses = (float) $expensesQuery->sum('amount');

            // --- Store Incomes ---
            $totalIncomes = 0;
            if (Schema::hasTable('incomes')) {
                $totalIncomes = (float) Income::withoutGlobalScopes()
                    ->where('store_id', $sId)
                    ->whereBetween(DB::raw('DATE(COALESCE(income_date, created_at))'), [$startDateStr, $endDateStr])
                    ->sum('amount');
            }

            // --- Net Profit & Net Margin ---
            $netProfit = $grossProfit - $totalExpenses + $totalIncomes;
            $netMargin = $totalSales > 0 ? ($netProfit / $totalSales) * 100 : 0;

            // --- Inventory Status ---
            $inventoryValuation = (float) Product::withoutGlobalScopes()
                ->where('store_id', $sId)
                ->sum(DB::raw('stock * purchase_price'));

            $totalStockUnits = (int) Product::withoutGlobalScopes()
                ->where('store_id', $sId)
                ->sum('stock');

            $totalActiveSkus = Product::withoutGlobalScopes()
                ->where('store_id', $sId)
                ->count();

            $lowStockCount = Product::withoutGlobalScopes()
                ->where('store_id', $sId)
                ->whereColumn('stock', '<', 'min_stock')
                ->count();

            // --- Top Cashier in Store ---
            $topCashier = DB::table('sales')
                ->leftJoin('users', 'sales.user_id', '=', 'users.id')
                ->where('sales.store_id', $sId)
                ->where(function ($q) {
                    $q->whereNull('sales.status')->orWhere('sales.status', '!=', 'cancelled');
                })
                ->whereBetween(DB::raw('DATE(COALESCE(sales.sale_date, sales.created_at))'), [$startDateStr, $endDateStr])
                ->select('users.name', DB::raw('SUM(sales.total_amount) as total_sales'), DB::raw('COUNT(sales.id) as trx_count'))
                ->groupBy('sales.user_id', 'users.name')
                ->orderByDesc('total_sales')
                ->first();

            $storesData[$sId] = [
                'id' => $sId,
                'store' => $store,
                'store_name' => $store->name,
                'store_code' => $store->code ?: "STR-{$sId}",
                'bank_name' => $store->bank_name,
                'bank_account_number' => $store->bank_account_number,
                'bank_account_name' => $store->bank_account_name,
                'ending_balance' => (float) $store->ending_balance,
                'staff_count' => $store->users()->count(),

                // Period Performance
                'total_sales' => $totalSales,
                'trx_count' => $trxCount,
                'total_units_sold' => $totalUnitsSold,
                'total_cogs' => $totalCogs,
                'gross_profit' => $grossProfit,
                'gross_margin' => $grossMargin,
                'aov' => $aov,
                'total_expenses' => $totalExpenses,
                'total_incomes' => $totalIncomes,
                'net_profit' => $netProfit,
                'net_margin' => $netMargin,

                // Payment Breakdown
                'payment_cash' => $paymentCash,
                'payment_transfer' => $paymentTransfer,
                'payment_qris' => $paymentQris,
                'payment_split' => $paymentSplit,

                // Inventory Metrics
                'inventory_valuation' => $inventoryValuation,
                'total_stock_units' => $totalStockUnits,
                'total_active_skus' => $totalActiveSkus,
                'low_stock_count' => $lowStockCount,

                // Top Staff
                'top_cashier_name' => $topCashier?->name ?: 'Belum ada',
                'top_cashier_sales' => (float) ($topCashier?->total_sales ?? 0),
                'top_cashier_trx' => (int) ($topCashier?->trx_count ?? 0),
            ];
        }

        // 3. Consolidated Totals
        $chainTotalSales = array_sum(array_column($storesData, 'total_sales'));
        $chainTotalTrx = array_sum(array_column($storesData, 'trx_count'));
        $chainTotalUnits = array_sum(array_column($storesData, 'total_units_sold'));
        $chainTotalCogs = array_sum(array_column($storesData, 'total_cogs'));
        $chainGrossProfit = $chainTotalSales - $chainTotalCogs;
        $chainGrossMargin = $chainTotalSales > 0 ? ($chainGrossProfit / $chainTotalSales) * 100 : 0;
        $chainTotalExpenses = array_sum(array_column($storesData, 'total_expenses'));
        $chainTotalIncomes = array_sum(array_column($storesData, 'total_incomes'));
        $chainNetProfit = $chainGrossProfit - $chainTotalExpenses + $chainTotalIncomes;
        $chainNetMargin = $chainTotalSales > 0 ? ($chainNetProfit / $chainTotalSales) * 100 : 0;
        $chainAov = $chainTotalTrx > 0 ? ($chainTotalSales / $chainTotalTrx) : 0;
        $chainTotalValuation = array_sum(array_column($storesData, 'inventory_valuation'));
        $chainTotalStockUnits = array_sum(array_column($storesData, 'total_stock_units'));
        $chainTotalLowStock = array_sum(array_column($storesData, 'low_stock_count'));
        $chainTotalEndingBalance = array_sum(array_column($storesData, 'ending_balance'));
        $chainPaymentCash = array_sum(array_column($storesData, 'payment_cash'));
        $chainPaymentTransfer = array_sum(array_column($storesData, 'payment_transfer'));
        $chainPaymentQris = array_sum(array_column($storesData, 'payment_qris'));

        // Calculate sales contribution % and add rankings
        foreach ($storesData as $sId => &$sData) {
            $sData['sales_contribution'] = $chainTotalSales > 0 ? ($sData['total_sales'] / $chainTotalSales) * 100 : 0;
            $sData['profit_contribution'] = $chainNetProfit > 0 ? ($sData['net_profit'] / $chainNetProfit) * 100 : 0;
        }
        unset($sData);

        // Sort by sales descending
        uasort($storesData, fn($a, $b) => $b['total_sales'] <=> $a['total_sales']);

        $rank = 1;
        $topRevenueStoreId = null;
        $mostProfitableStoreId = null;
        $maxSales = -1;
        $maxProfit = -INF;

        foreach ($storesData as $sId => &$sData) {
            $sData['rank'] = $rank++;
            if ($sData['total_sales'] > $maxSales) {
                $maxSales = $sData['total_sales'];
                $topRevenueStoreId = $sId;
            }
            if ($sData['net_profit'] > $maxProfit) {
                $maxProfit = $sData['net_profit'];
                $mostProfitableStoreId = $sId;
            }
        }
        unset($sData);

        // 4. Daily Sales Trajectory per Store (For Multi-line Trend BI Chart)
        $cursor = $startDate->copy()->startOfDay();
        $endCursor = $endDate->copy()->startOfDay();
        $dateLabels = [];
        $dailySeries = [];

        foreach ($allStores as $store) {
            $dailySeries[$store->id] = [
                'name' => $store->name,
                'data' => []
            ];
        }

        // Preload daily sales by date and store
        $dailySalesRaw = Sale::withoutGlobalScopes()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->whereBetween(DB::raw('DATE(COALESCE(sale_date, created_at))'), [$startDateStr, $endDateStr])
            ->selectRaw('store_id, DATE(COALESCE(sale_date, created_at)) as d, SUM(total_amount) as rev')
            ->groupBy('store_id', 'd')
            ->get();

        while ($cursor->lte($endCursor)) {
            $dStr = $cursor->toDateString();
            $label = $cursor->format('d M');
            $dateLabels[] = $label;

            foreach ($allStores as $store) {
                $rec = $dailySalesRaw->first(fn($item) => $item->store_id == $store->id && $item->d == $dStr);
                $dailySeries[$store->id]['data'][] = $rec ? (float) $rec->rev : 0;
            }

            $cursor->addDay();
        }

        // 5. Category Breakdown per Store (Top Categories in Multi-Store)
        $categoryBreakdown = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->where(function ($q) {
                $q->whereNull('sales.status')->orWhere('sales.status', '!=', 'cancelled');
            })
            ->whereBetween(DB::raw('DATE(COALESCE(sales.sale_date, sales.created_at))'), [$startDateStr, $endDateStr])
            ->selectRaw('COALESCE(NULLIF(products.category, ""), "Uncategorized") as cat, SUM(sale_items.quantity * sale_items.selling_price) as total_cat_revenue')
            ->groupBy('cat')
            ->orderByDesc('total_cat_revenue')
            ->take(6)
            ->get();

        $totals = [
            'total_sales' => $chainTotalSales,
            'trx_count' => $chainTotalTrx,
            'total_units_sold' => $chainTotalUnits,
            'total_cogs' => $chainTotalCogs,
            'gross_profit' => $chainGrossProfit,
            'gross_margin' => $chainGrossMargin,
            'total_expenses' => $chainTotalExpenses,
            'total_incomes' => $chainTotalIncomes,
            'net_profit' => $chainNetProfit,
            'net_margin' => $chainNetMargin,
            'aov' => $chainAov,
            'inventory_valuation' => $chainTotalValuation,
            'total_stock_units' => $chainTotalStockUnits,
            'low_stock_count' => $chainTotalLowStock,
            'total_ending_balance' => $chainTotalEndingBalance,
            'payment_cash' => $chainPaymentCash,
            'payment_transfer' => $chainPaymentTransfer,
            'payment_qris' => $chainPaymentQris,
            'stores_count' => count($storesData),
        ];

        return [
            'filter' => $filter,
            'filterLabel' => $filterLabel,
            'startDate' => $startDateStr,
            'endDate' => $endDateStr,
            'selectedStoreId' => $selectedStoreId,
            'allStores' => $allStores,
            'storesData' => $storesData,
            'totals' => $totals,
            'topRevenueStoreId' => $topRevenueStoreId,
            'mostProfitableStoreId' => $mostProfitableStoreId,
            'dateLabels' => $dateLabels,
            'dailySeries' => array_values($dailySeries),
            'categoryBreakdown' => $categoryBreakdown,
        ];
    }
}

