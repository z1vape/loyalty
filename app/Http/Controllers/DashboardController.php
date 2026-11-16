<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Store;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('Dashboard List')) {
            if ($user->can('Inventory List')) {
                return redirect()->route('inventory.index');
            }
            if ($user->can('Stock In Create') || $user->can('Stock In List')) {
                return redirect()->route('movements.index', ['type' => 'in']);
            }
            if ($user->can('Stock Out Create') || $user->can('Stock Out List')) {
                return redirect()->route('movements.index', ['type' => 'out']);
            }
            if ($user->can('Sales List') || $user->can('Sales Create')) {
                return redirect()->route('sales.index');
            }
            if ($user->can('Movement List')) {
                return redirect()->route('movements.index');
            }
            if ($user->can('User List')) {
                return redirect()->route('users.index');
            }
            if ($user->can('Store List')) {
                return redirect()->route('stores.index');
            }
            if ($user->can('Role List')) {
                return redirect()->route('roles.index');
            }
            if ($user->can('Setting List')) {
                return redirect()->route('settings.index');
            }
            if ($user->can('Attendance List') || $user->can('Shift List')) {
                return redirect()->route('commercial.attendances.index');
            }
            if ($user->can('Z1 App Access')) {
                return redirect()->route('z1-app.index');
            }

            abort(403, 'Anda tidak memiliki akses ke menu manapun.');
        }

        // 0. Parse Date Filter
        $filter = $request->input('filter', 'mtd'); // default: this month
        $startDate = null;
        $endDate = null;

        if ($filter === 'today') {
            $startDate = Carbon::today()->startOfDay();
            $endDate = Carbon::today()->endOfDay();
            $filterLabel = 'Hari Ini';
        } elseif ($filter === 'yesterday') {
            $startDate = Carbon::yesterday()->startOfDay();
            $endDate = Carbon::yesterday()->endOfDay();
            $filterLabel = 'Kemarin';
        } elseif ($filter === 'custom') {
            $startStr = $request->input('start_date');
            $endStr = $request->input('end_date');
            
            if ($startStr) {
                $startDate = Carbon::parse($startStr)->startOfDay();
            } else {
                $startDate = Carbon::today()->startOfDay();
            }
            
            if ($endStr) {
                $endDate = Carbon::parse($endStr)->endOfDay();
            } else {
                $endDate = Carbon::today()->endOfDay();
            }
            $filterLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        } else {
            $filter = 'mtd';
            $startDate = Carbon::now()->startOfMonth()->startOfDay();
            $endDate = Carbon::now()->endOfMonth()->endOfDay();
            $filterLabel = 'Bulan Ini';
        }

        // 1. Total Items (Sum of stock of all products)
        $totalItems = Product::sum('stock');
        $uniqueSKUs = Product::count();

        // 2. Low Stock Alerts (Products where stock < min_stock)
        $lowStockAlerts = Product::whereColumn('stock', '<', 'min_stock')->count();

        $startDateStr = $startDate ? $startDate->toDateString() : null;
        $endDateStr   = $endDate   ? $endDate->toDateString()   : null;

        // 3. Stock In quantity (within the filtered period)
        $stockInQuery = StockMovement::where('type', 'in');
        if ($startDateStr && $endDateStr) {
            $stockInQuery->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $monthlyStockIn = $stockInQuery->sum('quantity');

        // 4. Total Sales (sum of transaction total_price or qty * selling_price for out movements within the filtered period)
        $salesQuery = StockMovement::where('type', 'out')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });
        if ($startDateStr && $endDateStr) {
            $salesQuery->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $monthlySales = $salesQuery->sum(DB::raw('COALESCE(total_price, (quantity * selling_price) - COALESCE(discount, 0), 0)')) ?? 0;

        // 4b. Total Purchase Cost & Store Expenses
        $purchaseCostQuery = StockMovement::where('stock_movements.type', 'out')
            ->where(function ($q) {
                $q->whereNull('stock_movements.status')->orWhere('stock_movements.status', '!=', 'cancelled');
            })
            ->join('products', 'stock_movements.product_id', '=', 'products.id');
        if ($startDateStr && $endDateStr) {
            $purchaseCostQuery->whereBetween(DB::raw('DATE(COALESCE(stock_movements.movement_date, stock_movements.created_at))'), [$startDateStr, $endDateStr]);
        }
        $totalPurchaseCost = $purchaseCostQuery->sum(DB::raw('stock_movements.quantity * COALESCE(stock_movements.purchase_price, products.purchase_price, 0)')) ?? 0;

        $expenseQuery = Expense::query()
            ->where(function ($q) {
                $q->whereHas('account', function ($aq) {
                    $aq->where('type', 'expense')
                       ->where('code', '!=', '5101');
                })->orWhere(function ($oq) {
                    $oq->whereNull('account_id')
                        ->where(function ($sq) {
                            $sq->whereNull('category')
                               ->orWhereRaw("LOWER(category) NOT LIKE '%hutang%' AND LOWER(category) NOT LIKE '%piutang%' AND LOWER(category) NOT LIKE '%talangan%' AND LOWER(category) NOT LIKE '%kasbon%'");
                        });
                });
            });
        if ($startDateStr && $endDateStr) {
            $expenseQuery->whereBetween(DB::raw('DATE(COALESCE(expense_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $monthlyExpenses = $expenseQuery->sum('amount') ?? 0;

        $monthlyIncomes = 0;
        if (Schema::hasTable('incomes')) {
            $incomeQuery = Income::query();
            if ($startDateStr && $endDateStr) {
                $incomeQuery->whereBetween(DB::raw('DATE(COALESCE(income_date, created_at))'), [$startDateStr, $endDateStr]);
            }
            $monthlyIncomes = $incomeQuery->sum('amount') ?? 0;
        }

        $netProfit = $monthlySales + $monthlyIncomes - $totalPurchaseCost - $monthlyExpenses;

        // 4c. Store Ending Balance (Real-time Kas & Bank Berjalan)
        $allStores = Store::orderBy('name')->get();
        $activeStoreId = session('active_store_id');
        $activeStore = $activeStoreId ? $allStores->firstWhere('id', $activeStoreId) : null;

        if ($activeStore) {
            $storeEndingBalance = $activeStore->ending_balance;
            $storeBalanceScope = $activeStore->name;
            $storeBankSub = $activeStore->bank_name ? "{$activeStore->bank_name} - {$activeStore->bank_account_number}" : 'Kas Toko';
        } else {
            $storeEndingBalance = $allStores->sum('ending_balance');
            $storeBalanceScope = $allStores->count() > 1 ? 'Semua Cabang (' . $allStores->count() . ' Toko)' : ($allStores->first()->name ?? 'Toko');
            $storeBankSub = 'Akumulatif Kas & Bank';
        }

        // 5. Sales & Movements by Creator (Salesperson)
        $salesByCreatorQuery = StockMovement::where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
        });
        if ($startDateStr && $endDateStr) {
            $salesByCreatorQuery->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $salesByCreator = $salesByCreatorQuery
            ->select('user_id',
                DB::raw('SUM(CASE WHEN type = "out" THEN COALESCE(total_price, (quantity * selling_price) - COALESCE(discount, 0), 0) ELSE 0 END) as total_sales'),
                DB::raw('SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as total_out_qty'),
                DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as total_in_qty')
            )
            ->groupBy('user_id')
            ->with('user')
            ->orderByDesc('total_sales')
            ->get();

        // 6. Recent Movements (limit to 5, filtered by date range)
        $recentMovementsQuery = StockMovement::with('product');
        if ($startDateStr && $endDateStr) {
            $recentMovementsQuery->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $recentMovements = $recentMovementsQuery
            ->orderBy(DB::raw('COALESCE(movement_date, created_at)'), 'desc')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // 7. Low Stock Products list (actual products under minimum stock)
        $lowStockProducts = Product::whereColumn('stock', '<', 'min_stock')
            ->orderBy('stock', 'asc')
            ->take(5)
            ->get();

        // 8. Top Selling Products list (within the filtered period)
        $topSellingProductsQuery = StockMovement::where('type', 'out')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });
        if ($startDateStr && $endDateStr) {
            $topSellingProductsQuery->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDateStr, $endDateStr]);
        }
        $topSellingProducts = $topSellingProductsQuery
            ->select('product_id',
                DB::raw('SUM(quantity) as total_sold'),
                DB::raw('SUM(COALESCE(total_price, (quantity * selling_price) - COALESCE(discount, 0), 0)) as total_revenue')
            )
            ->groupBy('product_id')
            ->with('product')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get();

        // 9. Stock Trend Chart — daily Stock In & Out for the last 30 days (or filtered period)
        $trendStart = $startDate ?? Carbon::now()->subDays(29)->startOfDay();
        $trendEnd   = $endDate   ?? Carbon::now()->endOfDay();
        $trendStartStr = $trendStart->toDateString();
        $trendEndStr   = $trendEnd->toDateString();

        $stockTrendRaw = StockMovement::selectRaw(
                'DATE(COALESCE(movement_date, created_at)) as date,
                 SUM(CASE WHEN type = "in"  THEN quantity ELSE 0 END) as stock_in,
                 SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as stock_out'
            )
            ->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$trendStartStr, $trendEndStr])
            ->groupByRaw('DATE(COALESCE(movement_date, created_at))')
            ->orderBy('date')
            ->get();

        // Fill in missing dates so chart is continuous
        $trendDays  = collect();
        $cursor     = $trendStart->copy()->startOfDay();
        $endDay     = $trendEnd->copy()->startOfDay();
        while ($cursor->lte($endDay)) {
            $day  = $cursor->toDateString();
            $row  = $stockTrendRaw->firstWhere('date', $day);
            $trendDays->push([
                'date'     => $cursor->format('d M'),
                'stock_in' => $row ? (int) $row->stock_in  : 0,
                'stock_out'=> $row ? (int) $row->stock_out : 0,
            ]);
            $cursor->addDay();
        }
        $stockTrend = $trendDays;

        // 10. Daily Revenue Chart — within filtered period
        $dailyRevenueRaw = StockMovement::where('type', 'out')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            })
            ->selectRaw(
                'DATE(COALESCE(movement_date, created_at)) as date,
                 SUM(COALESCE(total_price, (quantity * selling_price) - COALESCE(discount, 0), 0)) as revenue'
            )
            ->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$trendStartStr, $trendEndStr])
            ->groupByRaw('DATE(COALESCE(movement_date, created_at))')
            ->orderBy('date')
            ->get();

        $revenueDays = collect();
        $cursor      = $trendStart->copy()->startOfDay();
        while ($cursor->lte($endDay)) {
            $day = $cursor->toDateString();
            $row = $dailyRevenueRaw->firstWhere('date', $day);
            $revenueDays->push([
                'date'    => $cursor->format('d M'),
                'revenue' => $row ? (float) $row->revenue : 0,
            ]);
            $cursor->addDay();
        }
        $dailyRevenue = $revenueDays;

        return view('dashboard', compact(
            'totalItems',
            'uniqueSKUs',
            'lowStockAlerts',
            'monthlyStockIn',
            'monthlySales',
            'monthlyIncomes',
            'totalPurchaseCost',
            'monthlyExpenses',
            'netProfit',
            'storeEndingBalance',
            'storeBalanceScope',
            'storeBankSub',
            'allStores',
            'recentMovements',
            'filter',
            'filterLabel',
            'salesByCreator',
            'lowStockProducts',
            'topSellingProducts',
            'stockTrend',
            'dailyRevenue'
        ));
    }
}
