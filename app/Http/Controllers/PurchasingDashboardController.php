<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SupplierPayment;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchasingDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('Stock In Create') && !$user->can('Movement List') && !$user->can('Accounting Payable Manage') && !$user->can('Dashboard List')) {
            abort(403, 'Anda tidak memiliki akses ke Dashboard Purchasing.');
        }

        // Active Store Filter
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

        // Base Query for Stock In (Purchases)
        $purchasesQuery = StockMovement::withoutGlobalScopes()
            ->where('type', 'in')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'approved');
            });

        if ($activeStoreId) {
            $purchasesQuery->where(function ($q) use ($activeStoreId) {
                $q->where('store_id', $activeStoreId);
                if ($activeStoreId == 1) {
                    $q->orWhereNull('store_id');
                }
            });
        }

        if ($startDateStr && $endDateStr) {
            $purchasesQuery->whereBetween(DB::raw('DATE(COALESCE(movement_date, created_at))'), [$startDateStr, $endDateStr]);
        }

        // 1. Total Purchasing Value in Period
        $totalPurchases = (float) (clone $purchasesQuery)->sum(DB::raw('COALESCE(total_price, quantity * purchase_price, 0)'));
        $totalPurchasedQty = (int) (clone $purchasesQuery)->sum('quantity');
        $totalInvoicesCount = (clone $purchasesQuery)->count();

        // 2. Outstanding Payables (Hutang Dagang) - Cumulative real-time
        $payablesBaseQuery = StockMovement::withoutGlobalScopes()
            ->where('type', 'in')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'approved');
            })
            ->where(function ($q) {
                $q->whereIn('payment_method', ['tempo', 'hutang', 'credit'])
                    ->orWhereIn('payment_status', ['pending', 'partial']);
            })
            ->whereIn('payment_status', ['pending', 'partial']);

        if ($activeStoreId) {
            $payablesBaseQuery->where(function ($q) use ($activeStoreId) {
                $q->where('store_id', $activeStoreId);
                if ($activeStoreId == 1) {
                    $q->orWhereNull('store_id');
                }
            });
        }

        // Filter those with remaining debt > 0
        $allBills = (clone $payablesBaseQuery)->with(['supplierPayments', 'product'])->get();
        $outstandingBills = $allBills->filter(fn($m) => $m->remaining_debt > 0);
        $totalRemainingDebt = (float) $outstandingBills->sum(fn($m) => $m->remaining_debt);

        $today = Carbon::today();
        $sevenDaysLater = Carbon::today()->addDays(7);

        // Overdue & Due Soon
        $overdueBills = $outstandingBills->filter(fn($m) => $m->due_date && Carbon::parse($m->due_date)->lt($today));
        $overdueDebt = (float) $overdueBills->sum(fn($m) => $m->remaining_debt);
        $overdueCount = $overdueBills->count();

        $dueSoonBills = $outstandingBills->filter(fn($m) => $m->due_date && Carbon::parse($m->due_date)->gte($today) && Carbon::parse($m->due_date)->lte($sevenDaysLater));
        $dueSoonDebt = (float) $dueSoonBills->sum(fn($m) => $m->remaining_debt);
        $dueSoonCount = $dueSoonBills->count();

        // 3. Payment Status Breakdown (for current period)
        $paidCount = 0;
        $partialCount = 0;
        $unpaidCount = 0;

        $periodBills = (clone $purchasesQuery)->with('supplierPayments')->get();
        foreach ($periodBills as $bill) {
            $method = strtolower(trim($bill->payment_method ?? ''));
            $isTempo = in_array($method, ['tempo', 'hutang', 'credit']);
            $status = $bill->payment_status;

            if ($status === 'paid' || (!$isTempo && $status !== 'partial')) {
                $paidCount++;
            } elseif ($status === 'partial') {
                $partialCount++;
            } else {
                $unpaidCount++;
            }
        }

        // 4. Top Suppliers by Purchase Volume (Period)
        $topSuppliers = (clone $purchasesQuery)
            ->select(
                DB::raw("COALESCE(NULLIF(supplier_name, ''), 'Supplier Umum') as supplier"),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(COALESCE(total_price, quantity * purchase_price, 0)) as total_spent')
            )
            ->groupBy('supplier')
            ->orderByDesc('total_spent')
            ->limit(5)
            ->get();

        // 5. Urgent Payables List (sorted by due_date asc)
        $urgentPayables = $outstandingBills
            ->sortBy(function ($bill) {
                return $bill->due_date ? Carbon::parse($bill->due_date)->timestamp : 9999999999;
            })
            ->take(6);

        // 6. Recent Restock Movements
        $recentMovements = (clone $purchasesQuery)
            ->with(['product', 'user'])
            ->latest('created_at')
            ->limit(6)
            ->get();

        return view('purchasing.dashboard', compact(
            'activeStore',
            'filter',
            'filterLabel',
            'startDate',
            'endDate',
            'totalPurchases',
            'totalPurchasedQty',
            'totalInvoicesCount',
            'totalRemainingDebt',
            'overdueDebt',
            'overdueCount',
            'dueSoonDebt',
            'dueSoonCount',
            'paidCount',
            'partialCount',
            'unpaidCount',
            'topSuppliers',
            'urgentPayables',
            'recentMovements'
        ));
    }
}
