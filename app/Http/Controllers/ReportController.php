<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Setting;
use App\Models\Store;
use App\Models\PurchaseOrder;
use App\Models\MovementDate;
use Carbon\Carbon;
use App\Services\LowStockReportService;
use App\Exports\SalesBySkuExport;
use App\Exports\ProductSalesDetailExport;
use App\Exports\TopSuppliersExport;
use App\Exports\LowStockReportExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    /**
     * Reports Hub / Katalog Laporan -> Redirect to default report
     */
    public function index()
    {
        $currentUser = auth()->user();

        $hasAccess = $currentUser->hasRole('Super Admin')
            || $currentUser->can('Report List')
            || $currentUser->can('Report SKU')
            || $currentUser->can('Report Category')
            || $currentUser->can('Report Staff')
            || $currentUser->can('Inventory List')
            || $currentUser->can('Accounting Report View');

        if (!$hasAccess) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        return redirect()->route('reports.by-sku');
    }

    public function lowStock(Request $request, LowStockReportService $service)
    {
        if (Gate::denies('Report List') && Gate::denies('Report SKU') && Gate::denies('Inventory List') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $data = $this->getLowStockData($request, $service);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data'                   => $data['items']->values(),
                'count'                  => $data['totalItems'],
                'total_shortage'         => $data['totalShortage'],
                'estimated_restock_cost' => round($data['totalRestockCost'], 2),
            ]);
        }

        return view('reports.low_stock', $data);
    }

    public function exportLowStock(Request $request, LowStockReportService $service)
    {
        if (Gate::denies('Report List') && Gate::denies('Report SKU') && Gate::denies('Inventory List') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $data = $this->getLowStockData($request, $service);
        $fileName = 'laporan-stok-kritis-' . date('Y-m-d') . '.xlsx';

        return Excel::download(
            new LowStockReportExport($data, $data['storeName']),
            $fileName
        );
    }

    public function getLowStockData(Request $request, LowStockReportService $service): array
    {
        $category = $request->input('category');
        $search   = $request->input('search');
        $includeOutOfStock = $request->has('include_out_of_stock')
            ? filter_var($request->input('include_out_of_stock'), FILTER_VALIDATE_BOOLEAN)
            : true;

        $items = $service->products($category, $includeOutOfStock);

        if ($request->filled('search')) {
            $term = strtolower(trim($search));
            $items = $items->filter(function ($item) use ($term) {
                return str_contains(strtolower($item['name'] ?? ''), $term)
                    || str_contains(strtolower($item['sku'] ?? ''), $term);
            });
        }

        $categories = Setting::where('type', 'category')->pluck('value')
            ->merge(Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category'))
            ->unique()
            ->sort()
            ->values();

        $totalItems = $items->count();
        $outOfStockCount = $items->where('stock', '<=', 0)->count();
        $lowStockCount = $items->where('stock', '>', 0)->count();
        $totalShortage = $items->sum('shortage');
        $totalRestockCost = $items->sum('estimated_restock_cost');

        $activeStoreId = session('active_store_id');
        $storeName = 'Semua Toko';
        if ($activeStoreId) {
            $currentStore = Store::find($activeStoreId);
            if ($currentStore) {
                $storeName = $currentStore->name;
            }
        }

        return compact(
            'items',
            'categories',
            'category',
            'search',
            'includeOutOfStock',
            'totalItems',
            'outOfStockCount',
            'lowStockCount',
            'totalShortage',
            'totalRestockCost',
            'storeName'
        );
    }

    /**
     * Laporan Penjualan berdasarkan SKU (Produk)
     */
    public function bySku(Request $request)
    {
        if (Gate::denies('Report SKU') && Gate::denies('Report List')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $data = $this->getSalesBySkuData($request);

        $categories = Setting::where('type', 'category')->pluck('value')
            ->merge(Product::whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category'))
            ->unique()
            ->sort()
            ->values();

        return view('reports.by_sku', array_merge($data, compact('categories')));
    }

    /**
     * Export Laporan Penjualan per SKU ke Excel
     */
    public function exportBySku(Request $request)
    {
        if (Gate::denies('Report SKU') && Gate::denies('Report List')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $data = $this->getSalesBySkuData($request);

        $activeStoreId = session('active_store_id');
        $storeName = 'Semua Toko';
        if ($activeStoreId) {
            $store = Store::find($activeStoreId);
            if ($store) {
                $storeName = $store->name;
            }
        }

        $fileName = 'penjualan-sku-' . $data['startDate'] . '-sd-' . $data['endDate'] . '.xlsx';

        return Excel::download(
            new SalesBySkuExport($data, $storeName),
            $fileName
        );
    }

    /**
     * Export Detail Penjualan Produk (Breakdown per Unit Qty) ke Excel
     */
    public function exportDetail(Request $request)
    {
        if (Gate::denies('Report SKU') && Gate::denies('Report List')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date',   Carbon::now()->toDateString());
        $category  = $request->input('category');
        $search    = $request->input('search');

        $query = StockMovement::query()
            ->with(['product', 'user'])
            ->where('type', 'out')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });

        if ($request->filled('category')) {
            $cat = $category;
            $query->whereHas('product', function ($q) use ($cat) {
                if ($cat === 'Tanpa Kategori') {
                    $q->whereNull('category')->orWhere('category', '');
                } else {
                    $q->where('category', $cat);
                }
            });
        }

        if ($request->filled('search')) {
            $term = trim($search);
            $query->whereHas('product', function ($q) use ($term) {
                $q->where(function ($sq) use ($term) {
                    $sq->where('sku', 'like', "%{$term}%")
                       ->orWhere('name', 'like', "%{$term}%")
                       ->orWhere('variant', 'like', "%{$term}%");
                });
            });
        }

        $movements = $query->orderBy('movement_date', 'asc')->orderBy('id', 'asc')->get();

        $activeStoreId = session('active_store_id');
        $storeName = 'Semua Toko';
        if ($activeStoreId) {
            $store = Store::find($activeStoreId);
            if ($store) {
                $storeName = $store->name;
            }
        }

        $fileName = 'penjualan-detail-produk-' . $startDate . '-sd-' . $endDate . '.xlsx';

        return Excel::download(
            new ProductSalesDetailExport($movements, [
                'startDate' => $startDate,
                'endDate'   => $endDate,
                'category'  => $category,
                'search'    => $search,
            ], $storeName),
            $fileName
        );
    }

    /**
     * Helper to prepare filtered Sales by SKU data for both View and Excel Export
     */
    public function getSalesBySkuData(Request $request): array
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date',   Carbon::now()->toDateString());
        $category  = $request->input('category');
        $search    = $request->input('search');

        $query = StockMovement::query()
            ->with('product')
            ->where('type', 'out')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });

        if ($request->filled('category')) {
            $cat = $category;
            $query->whereHas('product', function ($q) use ($cat) {
                if ($cat === 'Tanpa Kategori') {
                    $q->whereNull('category')->orWhere('category', '');
                } else {
                    $q->where('category', $cat);
                }
            });
        }

        if ($request->filled('search')) {
            $term = trim($search);
            $query->whereHas('product', function ($q) use ($term) {
                $q->where(function ($sq) use ($term) {
                    $sq->where('sku', 'like', "%{$term}%")
                       ->orWhere('name', 'like', "%{$term}%")
                       ->orWhere('variant', 'like', "%{$term}%");
                });
            });
        }

        $items = $query->get()
            ->groupBy('product_id')
            ->map(function ($group) {
                $product = $group->first()->product;
                $totalPurchasePrice = $group->sum(function ($m) {
                    $unitPurchase = $m->purchase_price ?? $m->product?->purchase_price ?? 0;
                    return $m->quantity * $unitPurchase;
                });
                $totalDiscount = $group->sum(fn ($m) => $m->discount ?? 0);
                $totalAmount   = $group->sum(function ($m) {
                    return $m->total_price ?? ($m->quantity * ($m->selling_price ?? $m->product?->selling_price ?? 0));
                });
                $profit = $totalAmount - $totalPurchasePrice;

                return [
                    'sku'                 => $product?->sku ?? '-',
                    'name'                => $product?->name ?? 'Produk Dihapus',
                    'variant'             => $product?->variant ?? null,
                    'category'            => !empty($product?->category) ? $product->category : 'Tanpa Kategori',
                    'qty'                 => (int) $group->sum('quantity'),
                    'total_purchase_price'=> (float) $totalPurchasePrice,
                    'total_discount'      => (float) $totalDiscount,
                    'total_amount'        => (float) $totalAmount,
                    'profit'              => (float) $profit,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        $grandTotal        = (float) $items->sum('total_amount');
        $totalQty          = (int) $items->sum('qty');
        $totalProducts     = $items->count();
        $totalPurchaseCost = (float) $items->sum('total_purchase_price');
        $totalDiscount     = (float) $items->sum('total_discount');
        $totalProfit       = (float) $items->sum('profit');

        // Add precalculated margin and contribution percentage
        $items = $items->map(function ($item) use ($grandTotal) {
            $item['margin'] = $item['total_amount'] > 0
                ? round(($item['profit'] / $item['total_amount']) * 100, 1)
                : 0.0;
            $item['contribution'] = $grandTotal > 0
                ? round(($item['total_amount'] / $grandTotal) * 100, 1)
                : 0.0;
            return $item;
        });

        return compact(
            'items', 'startDate', 'endDate', 'category', 'search',
            'grandTotal', 'totalQty', 'totalProducts',
            'totalPurchaseCost', 'totalDiscount', 'totalProfit'
        );
    }

    /**
     * Laporan Penjualan berdasarkan Kategori
     */
    public function byCategory(Request $request)
    {
        if (Gate::denies('Report Category') && Gate::denies('Report List')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date',   Carbon::now()->toDateString());

        $items = StockMovement::query()
            ->with('product')
            ->where('type', 'out')
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->get()
            ->groupBy(fn ($item) => !empty($item->product?->category) ? $item->product->category : 'Tanpa Kategori')
            ->map(function ($group, $category) {
                $totalPurchasePrice = $group->sum(function ($m) {
                    $unitPurchase = $m->purchase_price ?? $m->product?->purchase_price ?? 0;
                    return $m->quantity * $unitPurchase;
                });
                $totalDiscount = $group->sum(fn ($m) => $m->discount ?? 0);
                $totalAmount   = $group->sum(function ($m) {
                    return $m->total_price ?? ($m->quantity * ($m->selling_price ?? $m->product?->selling_price ?? 0));
                });
                $profit = $totalAmount - $totalPurchasePrice;

                return [
                    'category'            => $category,
                    'product_count'       => $group->pluck('product_id')->unique()->count(),
                    'qty'                 => $group->sum('quantity'),
                    'total_purchase_price'=> $totalPurchasePrice,
                    'total_discount'      => $totalDiscount,
                    'total_amount'        => $totalAmount,
                    'profit'              => $profit,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        $grandTotal        = $items->sum('total_amount');
        $totalQty          = $items->sum('qty');
        $totalCategories   = $items->count();
        $totalPurchaseCost = $items->sum('total_purchase_price');
        $totalDiscount     = $items->sum('total_discount');
        $totalProfit       = $items->sum('profit');

        return view('reports.by_category', compact(
            'items', 'startDate', 'endDate',
            'grandTotal', 'totalQty', 'totalCategories',
            'totalPurchaseCost', 'totalDiscount', 'totalProfit'
        ));
    }

    /**
     * Laporan Penjualan & Movements per Staff
     */
    public function byStaff(Request $request)
    {
        if (Gate::denies('Report Staff') && Gate::denies('Report List')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate   = $request->input('end_date',   Carbon::now()->toDateString());

        // Aggregate stock movements grouped by user_id
        $staffStats = StockMovement::query()
            ->whereBetween('movement_date', [$startDate, $endDate])
            ->select(
                'user_id',
                DB::raw('COUNT(DISTINCT reference_number) as total_transactions'),
                DB::raw('SUM(CASE WHEN type = "out" THEN quantity ELSE 0 END) as total_out_qty'),
                DB::raw('SUM(CASE WHEN type = "in" THEN quantity ELSE 0 END) as total_in_qty'),
                DB::raw('SUM(CASE WHEN type = "out" THEN COALESCE(total_price, quantity * selling_price, 0) ELSE 0 END) as total_sales_amount')
            )
            ->groupBy('user_id')
            ->get();

        $activeStoreId = session('active_store_id');

        // Fetch users belonging to active store or with movements in range
        $usersQuery = User::with('roles');
        if ($activeStoreId) {
            $usersQuery->where(function($q) use ($activeStoreId, $staffStats) {
                $q->whereHas('stores', fn($sq) => $sq->where('stores.id', $activeStoreId))
                  ->orWhereIn('id', $staffStats->pluck('user_id')->filter());
            });
        }
        $allUsers = $usersQuery->get();

        $items = $allUsers->map(function ($user) use ($staffStats) {
            $stat = $staffStats->firstWhere('user_id', $user->id);
            $totalTransactions = $stat ? (int)$stat->total_transactions : 0;
            $totalOutQty       = $stat ? (int)$stat->total_out_qty : 0;
            $totalInQty        = $stat ? (int)$stat->total_in_qty : 0;
            $totalSales        = $stat ? (float)$stat->total_sales_amount : 0.0;
            $avgTx             = $totalTransactions > 0 ? ($totalSales / $totalTransactions) : 0.0;

            return [
                'user_id'            => $user->id,
                'name'               => $user->name,
                'email'              => $user->email,
                'employee_number'    => $user->employee_number ?? '-',
                'position'           => $user->position ?? ($user->roles->pluck('name')->first() ?? 'Staff'),
                'photo'              => $user->photo,
                'total_transactions' => $totalTransactions,
                'total_out_qty'      => $totalOutQty,
                'total_in_qty'       => $totalInQty,
                'total_sales'        => $totalSales,
                'avg_transaction'    => $avgTx,
            ];
        })->sortByDesc('total_sales')->values();

        $grandTotalSales        = $items->sum('total_sales');
        $grandTotalTransactions = $items->sum('total_transactions');
        $grandTotalOutQty       = $items->sum('total_out_qty');
        $grandTotalInQty        = $items->sum('total_in_qty');
        $topPerformer           = $items->firstWhere('total_sales', '>', 0) ?? $items->first();

        return view('reports.by_staff', compact(
            'items', 'startDate', 'endDate',
            'grandTotalSales', 'grandTotalTransactions',
            'grandTotalOutQty', 'grandTotalInQty', 'topPerformer'
        ));
    }

    /**
     * Laporan Rekap 10 Supplier Terbesar (Biaya Belanja)
     */
    public function topSuppliers(Request $request)
    {
        if (Gate::denies('Report SKU') && Gate::denies('Report List') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $data = $this->getTopSuppliersData($request);

        return view('reports.top_suppliers', $data);
    }

    /**
     * Export Rekap 10 Supplier Terbesar ke Excel
     */
    public function exportTopSuppliers(Request $request)
    {
        if (Gate::denies('Report SKU') && Gate::denies('Report List') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'THIS ACTION IS UNAUTHORIZED.');
        }

        $data = $this->getTopSuppliersData($request);

        $activeStoreId = session('active_store_id');
        $storeName = 'Semua Toko';
        if ($activeStoreId) {
            $store = Store::find($activeStoreId);
            if ($store) {
                $storeName = $store->name;
            }
        }

        $cleanLabel = trim(preg_replace('/-+/', '-', preg_replace('/[^a-zA-Z0-9_\-]/', '-', strtolower($data['periodLabel']))), '-');
        $fileName = 'rekap-top-10-supplier-' . $cleanLabel . '.xlsx';

        return Excel::download(
            new TopSuppliersExport($data, $storeName),
            $fileName
        );
    }

    /**
     * Helper to prepare Suppliers data (Supports Monthly Matrix & Multi-Year Matrix for ALL Registered Suppliers)
     */
    public function getTopSuppliersData(Request $request): array
    {
        $mode = $request->input('mode', $request->input('period_type', 'month'));
        if (!in_array($mode, ['month', 'year'])) {
            $mode = 'month';
        }

        $currentYear = (int) Carbon::now()->year;
        $availableYears = range(2023, max(2027, $currentYear + 2));
        rsort($availableYears);

        $supplierExpr = "COALESCE(NULLIF(TRIM(stock_movements.supplier_name), ''), NULLIF(TRIM(movement_dates.supplier_name), ''), 'Supplier Umum / Tanpa Nama')";

        $activeStoreId = session('active_store_id');
        $storeName = 'Semua Toko';
        if ($activeStoreId) {
            $currentStore = Store::find($activeStoreId);
            if ($currentStore) {
                $storeName = $currentStore->name;
            }
        }

        if ($mode === 'year') {
            // Mode Per Tahun: Bandingkan antar tahun (default 2025 s/d 2027 atau custom range)
            $startYear = (int) $request->input('start_year', 2025);
            $endYear   = (int) $request->input('end_year', 2027);

            if ($startYear > $endYear) {
                $temp = $startYear;
                $startYear = $endYear;
                $endYear = $temp;
            }

            // Batasi rentang maksimal 10 tahun
            if (($endYear - $startYear) > 10) {
                $endYear = $startYear + 10;
            }

            $yearsList = range($startYear, $endYear);
            $startDate = Carbon::create($startYear, 1, 1)->startOfDay()->toDateString();
            $endDate   = Carbon::create($endYear, 12, 31)->endOfDay()->toDateString();
            $periodLabel = "Tahun {$startYear} - {$endYear}";

            $baseQuery = StockMovement::withoutGlobalScopes()
                ->leftJoin('movement_dates', function ($join) use ($activeStoreId) {
                    $join->on('movement_dates.date', '=', 'stock_movements.movement_date')
                         ->where('movement_dates.type', '=', 'in');
                    if ($activeStoreId) {
                        $join->where('movement_dates.store_id', '=', $activeStoreId);
                    }
                })
                ->where('stock_movements.type', 'in')
                ->where(function ($q) {
                    $q->whereNull('stock_movements.status')
                      ->orWhere('stock_movements.status', '!=', 'cancelled');
                })
                ->whereBetween('stock_movements.movement_date', [$startDate, $endDate]);

            if ($activeStoreId) {
                $baseQuery->where('stock_movements.store_id', $activeStoreId);
            }

            // Ambil seluruh pergerakan belanja stok toko aktif di rentang tahun
            $movements = (clone $baseQuery)
                ->select(
                    DB::raw("{$supplierExpr} as supplier_name"),
                    'stock_movements.movement_date',
                    'stock_movements.reference_number',
                    'stock_movements.quantity',
                    DB::raw("COALESCE(stock_movements.total_price, stock_movements.quantity * stock_movements.purchase_price, 0) as amount")
                )
                ->get();

            // Ambil seluruh supplier yang memiliki transaksi mutasi masuk di toko & rentang tahun ini
            $allSupplierNames = $movements->pluck('supplier_name')
                ->map(fn($s) => trim((string)$s))
                ->filter(fn($s) => !empty($s) && $s !== '-' && strtolower($s) !== 'supplier umum / tanpa nama')
                ->unique()
                ->values();

            $yearlyMatrix = [];
            $supplierTotals = [];
            $supplierQty = [];
            $supplierInvoices = [];

            foreach ($allSupplierNames as $sName) {
                $yearlyMatrix[$sName] = array_fill_keys($yearsList, 0.0);
                $supplierTotals[$sName] = 0.0;
                $supplierQty[$sName] = 0;
                $supplierInvoices[$sName] = collect();
            }

            foreach ($movements as $m) {
                $sName = $m->supplier_name;
                if (isset($yearlyMatrix[$sName])) {
                    $yr = (int) Carbon::parse($m->movement_date)->format('Y');
                    if (isset($yearlyMatrix[$sName][$yr])) {
                        $amt = (float) $m->amount;
                        $yearlyMatrix[$sName][$yr] += $amt;
                        $supplierTotals[$sName] += $amt;
                        $supplierQty[$sName] += (int) $m->quantity;
                        if (!empty($m->reference_number)) {
                            $supplierInvoices[$sName]->push($m->reference_number);
                        }
                    }
                }
            }

            // Urutkan supplier: nominal belanja tertinggi di urutan teratas, lalu nama alfabet
            $sortedSuppliers = $allSupplierNames->sort(function ($a, $b) use ($supplierTotals) {
                $spentA = $supplierTotals[$a] ?? 0.0;
                $spentB = $supplierTotals[$b] ?? 0.0;
                if ($spentA == $spentB) {
                    return strcasecmp($a, $b);
                }
                return ($spentB > $spentA) ? 1 : -1;
            })->values();

            $yearlyTotals = array_fill_keys($yearsList, 0.0);
            $grandTotalMultiYear = 0.0;

            $topSuppliers = $sortedSuppliers->map(function ($sName) use ($yearlyMatrix, $yearsList, $supplierTotals, $supplierQty, $supplierInvoices, &$yearlyTotals, &$grandTotalMultiYear) {
                $yearsData = $yearlyMatrix[$sName] ?? array_fill_keys($yearsList, 0.0);
                $totalSup = $supplierTotals[$sName] ?? 0.0;

                foreach ($yearsData as $yr => $val) {
                    $yearlyTotals[$yr] += $val;
                }
                $grandTotalMultiYear += $totalSup;

                return [
                    'supplier_name'  => $sName,
                    'years'          => $yearsData,
                    'total_spent'    => $totalSup,
                    'total_qty'      => (int) ($supplierQty[$sName] ?? 0),
                    'total_invoices' => (int) ($supplierInvoices[$sName] ? $supplierInvoices[$sName]->unique()->count() : 0),
                ];
            })
            ->filter(fn($sup) => $sup['total_spent'] > 0)
            ->values()
            ->map(function ($sup, $index) {
                $sup['rank'] = $index + 1;
                return $sup;
            });

            return [
                'mode'                => 'year',
                'startYear'           => $startYear,
                'endYear'             => $endYear,
                'yearsList'           => $yearsList,
                'topSuppliers'        => $topSuppliers,
                'yearlyTotals'        => $yearlyTotals,
                'grandTotalMultiYear' => $grandTotalMultiYear,
                'periodLabel'         => $periodLabel,
                'availableYears'      => $availableYears,
                'years'               => $availableYears,
                'year'                => $currentYear,
                'monthlyTotals'       => array_fill(1, 12, 0.0),
                'grandTotalYear'      => 0.0,
                'monthNames'          => [],
                'storeName'           => $storeName,
                'activeStoreId'       => $activeStoreId,
            ];
        }

        // Mode Per Bulan (Default): 12 Bulan (January - December) dalam 1 Tahun
        $year = (int) $request->input('year', $currentYear);
        if ($year < 2000 || $year > 2100) {
            $year = $currentYear;
        }

        $startDate = Carbon::create($year, 1, 1)->startOfDay()->toDateString();
        $endDate   = Carbon::create($year, 12, 31)->endOfDay()->toDateString();
        $periodLabel = "Tahun {$year}";

        $monthNames = [
            1  => 'January',
            2  => 'Febuary',
            3  => 'March',
            4  => 'April',
            5  => 'May',
            6  => 'June',
            7  => 'July',
            8  => 'August',
            9  => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December',
        ];

        $baseQuery = StockMovement::withoutGlobalScopes()
            ->leftJoin('movement_dates', function ($join) use ($activeStoreId) {
                $join->on('movement_dates.date', '=', 'stock_movements.movement_date')
                     ->where('movement_dates.type', '=', 'in');
                if ($activeStoreId) {
                    $join->where('movement_dates.store_id', '=', $activeStoreId);
                }
            })
            ->where('stock_movements.type', 'in')
            ->where(function ($q) {
                $q->whereNull('stock_movements.status')
                  ->orWhere('stock_movements.status', '!=', 'cancelled');
            })
            ->whereBetween('stock_movements.movement_date', [$startDate, $endDate]);

        if ($activeStoreId) {
            $baseQuery->where('stock_movements.store_id', $activeStoreId);
        }

        // Ambil seluruh pergerakan belanja stok toko aktif di tahun ini
        $movements = (clone $baseQuery)
            ->select(
                DB::raw("{$supplierExpr} as supplier_name"),
                'stock_movements.movement_date',
                'stock_movements.reference_number',
                'stock_movements.quantity',
                DB::raw("COALESCE(stock_movements.total_price, stock_movements.quantity * stock_movements.purchase_price, 0) as amount")
            )
            ->get();

        // Ambil seluruh supplier yang memiliki transaksi belanja aktual di toko & tahun ini
        $allSupplierNames = $movements->pluck('supplier_name')
            ->map(fn($s) => trim((string)$s))
            ->filter(fn($s) => !empty($s) && $s !== '-' && strtolower($s) !== 'supplier umum / tanpa nama')
            ->unique()
            ->values();

        $monthlyMatrix = [];
        $supplierTotals = [];
        $supplierQty = [];
        $supplierInvoices = [];

        foreach ($allSupplierNames as $sName) {
            $monthlyMatrix[$sName] = array_fill(1, 12, 0.0);
            $supplierTotals[$sName] = 0.0;
            $supplierQty[$sName] = 0;
            $supplierInvoices[$sName] = collect();
        }

        foreach ($movements as $m) {
            $sName = $m->supplier_name;
            if (isset($monthlyMatrix[$sName])) {
                $mNum = (int) Carbon::parse($m->movement_date)->format('n');
                if ($mNum >= 1 && $mNum <= 12) {
                    $amt = (float) $m->amount;
                    $monthlyMatrix[$sName][$mNum] += $amt;
                    $supplierTotals[$sName] += $amt;
                    $supplierQty[$sName] += (int) $m->quantity;
                    if (!empty($m->reference_number)) {
                        $supplierInvoices[$sName]->push($m->reference_number);
                    }
                }
            }
        }

        // Urutkan supplier: nominal belanja tertinggi di urutan teratas, lalu nama alfabet
        $sortedSuppliers = $allSupplierNames->sort(function ($a, $b) use ($supplierTotals) {
            $spentA = $supplierTotals[$a] ?? 0.0;
            $spentB = $supplierTotals[$b] ?? 0.0;
            if ($spentA == $spentB) {
                return strcasecmp($a, $b);
            }
            return ($spentB > $spentA) ? 1 : -1;
        })->values();

        $monthlyTotals = array_fill(1, 12, 0.0);
        $grandTotalYear = 0.0;

        $topSuppliers = $sortedSuppliers->map(function ($sName) use ($monthlyMatrix, $supplierTotals, $supplierQty, $supplierInvoices, &$monthlyTotals, &$grandTotalYear) {
            $months = $monthlyMatrix[$sName] ?? array_fill(1, 12, 0.0);
            $totalYear = $supplierTotals[$sName] ?? 0.0;

            foreach ($months as $mNum => $val) {
                $monthlyTotals[$mNum] += $val;
            }
            $grandTotalYear += $totalYear;

            return [
                'supplier_name'  => $sName,
                'months'         => $months,
                'total_year'     => $totalYear,
                'total_qty'      => (int) ($supplierQty[$sName] ?? 0),
                'total_invoices' => (int) ($supplierInvoices[$sName] ? $supplierInvoices[$sName]->unique()->count() : 0),
            ];
        })
        ->filter(fn($sup) => $sup['total_year'] > 0)
        ->values()
        ->map(function ($sup, $index) {
            $sup['rank'] = $index + 1;
            return $sup;
        });

        return [
            'mode'                => 'month',
            'year'                => $year,
            'topSuppliers'        => $topSuppliers,
            'monthlyTotals'       => $monthlyTotals,
            'grandTotalYear'      => $grandTotalYear,
            'periodLabel'         => $periodLabel,
            'monthNames'          => $monthNames,
            'years'               => $availableYears,
            'availableYears'      => $availableYears,
            'startYear'           => 2025,
            'endYear'             => 2027,
            'yearsList'           => [2025, 2026, 2027],
            'yearlyTotals'        => [2025 => 0.0, 2026 => 0.0, 2027 => 0.0],
            'grandTotalMultiYear' => 0.0,
            'storeName'           => $storeName,
            'activeStoreId'       => $activeStoreId,
        ];
    }
}
