<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockAdjustmentRequest;
use App\Models\MovementDate;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockOpnamePayrollAllocation;
use App\Models\Store;
use App\Models\User;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use LogicException;

class StockAdjustmentController extends Controller
{
    /**
     * Display a listing of stock opname date folders and adjustments.
     */
    public function index(Request $request)
    {
        Gate::authorize('Movement List');

        $request->validate([
            'product_id' => ['nullable', 'integer', 'min:1'],
            'user_id'    => ['nullable', 'integer', 'min:1'],
            'reason'     => ['nullable', \Illuminate\Validation\Rule::in(array_keys(StockAdjustment::REASONS))],
            'date_from'  => ['nullable', 'date_format:Y-m-d'],
            'date_to'    => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);

        // Base query with filters
        $baseQuery = StockAdjustment::with(['product', 'user', 'reversedBy'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')->toString()))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')));

        // 1. Raw adjustments paginated (maintains backward compatibility & passes test suite)
        $adjustments = (clone $baseQuery)->latest()->paginate(20)->withQueryString();

        // 2. Query distinct dates for "Folder Tanggal Stock Opname"
        // Combine explicitly created opname session dates & existing adjustment dates
        $opnameSessionDates = MovementDate::where('type', 'opname')
            ->when($request->filled('date_from'), fn ($q) => $q->where('date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->where('date', '<=', $request->date_to))
            ->pluck('date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

        $adjQuery = StockAdjustment::query()
            ->selectRaw('DATE(created_at) as adj_date')
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')->toString()))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('date_to')))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->pluck('adj_date')
            ->map(fn($d) => Carbon::parse($d)->format('Y-m-d'));

        $allDates = $opnameSessionDates->concat($adjQuery)->unique()->sortDesc()->values();

        // Paginate the collection of dates
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentItems = $allDates->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $dateFolders = new LengthAwarePaginator(
            $currentItems,
            $allDates->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 3. Aggregate metrics for the dates visible on this page
        $datesOnPage = $currentItems;
        $folderDetails = [];

        if ($datesOnPage->isNotEmpty()) {
            $sessionsByDate = MovementDate::with('creator')
                ->where('type', 'opname')
                ->whereIn('date', $datesOnPage)
                ->get()
                ->keyBy(fn ($s) => Carbon::parse($s->date)->format('Y-m-d'));

            $itemsByDate = StockAdjustment::with(['product', 'user'])
                ->whereIn(DB::raw('DATE(created_at)'), $datesOnPage)
                ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->integer('product_id')))
                ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->integer('user_id')))
                ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->string('reason')->toString()))
                ->latest()
                ->get()
                ->groupBy(fn ($item) => Carbon::parse($item->created_at)->toDateString());

            $existingAllocationsByDate = StockOpnamePayrollAllocation::whereIn(DB::raw('DATE(opname_date)'), $datesOnPage)
                ->get()
                ->groupBy(fn($a) => Carbon::parse($a->opname_date)->toDateString());

            foreach ($datesOnPage as $d) {
                $items = $itemsByDate->get($d, collect());
                $session = $sessionsByDate->get($d);

                $users = $items->pluck('user.name')->filter();
                if ($session?->creator?->name && !$users->contains($session->creator->name)) {
                    $users->prepend($session->creator->name);
                }

                $activeMinusItems = $items->filter(fn($i) => 
                    !$i->isReversed() && $i->reason !== 'reversal' && $i->quantity_change < 0
                );
                $totalMinusQty = abs((int) $activeMinusItems->sum('quantity_change'));
                $totalMinusNominal = (float) $activeMinusItems->sum(fn($i) => abs($i->quantity_change) * (float)($i->product?->selling_price ?? 0));

                $folderDetails[$d] = [
                    'date'                 => $d,
                    'session'              => $session,
                    'notes'                => $session?->notes,
                    'total_items'          => $items->count(),
                    'total_added'          => $items->where('quantity_change', '>', 0)->sum('quantity_change'),
                    'total_reduced'        => $items->where('quantity_change', '<', 0)->sum('quantity_change'),
                    'net_change'           => $items->sum('quantity_change'),
                    'total_minus_qty'      => $totalMinusQty,
                    'total_minus_nominal'  => $totalMinusNominal,
                    'existing_allocations' => $existingAllocationsByDate->get($d, collect())->mapWithKeys(fn($a) => [$a->user_id => (float)$a->amount])->all(),
                    'users'                => $users->unique()->values(),
                    'has_reversals'        => $items->contains(fn($i) => $i->isReversed() || $i->reason === 'reversal'),
                    'sample_products'      => $items->take(3)->map(fn($i) => $i->product?->name ?? '-')->filter()->values(),
                ];
            }
        }

        // 4. Overall metrics for header badges
        $summaryTotalFolders = $allDates->count();
        $summaryTotalItems   = StockAdjustment::query()->count();
        $summaryNetUnits     = StockAdjustment::query()->sum('quantity_change');

        return view('inventory.adjustments.index', [
            'adjustments'         => $adjustments,
            'dateFolders'         => $dateFolders,
            'folderDetails'       => $folderDetails,
            'summaryTotalFolders' => $summaryTotalFolders,
            'summaryTotalItems'   => $summaryTotalItems,
            'summaryNetUnits'     => $summaryNetUnits,
            'products'            => Product::orderBy('name')->get(),
            'reasons'             => StockAdjustment::REASONS,
            'stores'              => Store::orderBy('name')->get(),
            'employees'           => User::with(['stores'])->orderBy('name')->get()->map(fn($e) => [
                'id'          => $e->id,
                'name'        => $e->name,
                'position'    => $e->position ?: 'Staff',
                'store_ids'   => $e->stores->pluck('id')->all(),
                'store_names' => $e->stores->pluck('name')->implode(', '),
            ]),
        ]);
    }

    /**
     * Store a newly created opname session date.
     */
    public function storeDate(Request $request)
    {
        Gate::authorize('Stock In Store');

        $validated = $request->validate([
            'date'  => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $formattedDate = Carbon::parse($validated['date'])->format('Y-m-d');

        $movementDate = MovementDate::firstOrCreate(
            [
                'date'     => $formattedDate,
                'type'     => 'opname',
                'store_id' => session('active_store_id'),
            ],
            [
                'created_by' => auth()->id(),
                'notes'      => $validated['notes'] ?? null,
            ]
        );

        if (!$movementDate->wasRecentlyCreated && $request->filled('notes')) {
            $movementDate->update(['notes' => $validated['notes']]);
        }

        $dateLabel = Carbon::parse($formattedDate)->translatedFormat('d F Y');

        return redirect()
            ->route('stock-adjustments.date', ['date' => $formattedDate])
            ->with('success', "Tanggal sesi opname {$dateLabel} berhasil dibuat. Silakan tambahkan barang hasil audit fisik.");
    }

    /**
     * Assign or update stock opname minus/shortage allocation to employee payroll.
     */
    public function assignPayroll(Request $request)
    {
        Gate::authorize('Stock In Store');

        $validated = $request->validate([
            'date'         => ['required', 'date_format:Y-m-d'],
            'store_id'     => ['nullable', 'exists:stores,id'],
            'period_month' => ['required', 'integer', 'between:1,12'],
            'period_year'  => ['required', 'integer', 'min:2020', 'max:2050'],
            'deductions'   => ['nullable', 'array'],
            'deductions.*' => ['nullable', 'numeric', 'min:0'],
            'note'         => ['nullable', 'string', 'max:255'],
        ]);

        $soDate = $validated['date'];
        $carbonDate = Carbon::parse($soDate);
        $dateLabel = $carbonDate->translatedFormat('d M Y');
        $month = (int) $validated['period_month'];
        $year = (int) $validated['period_year'];
        $storeId = $validated['store_id'] ?? null;
        $noteInput = !empty($validated['note']) ? trim($validated['note']) : "Beban Selisih SO {$dateLabel}";

        $assignedCount = 0;
        $totalAssignedAmount = 0;

        DB::transaction(function () use ($validated, $soDate, $month, $year, $storeId, $dateLabel, $noteInput, &$assignedCount, &$totalAssignedAmount) {
            $formattedSoDate = Carbon::parse($soDate)->toDateString();
            $existingAllocations = StockOpnamePayrollAllocation::whereDate('opname_date', $formattedSoDate)
                ->get()
                ->keyBy('user_id');

            $submittedDeductions = $validated['deductions'] ?? [];

            // 1. Remove previous allocations for employees no longer submitted or with 0 deduction
            foreach ($existingAllocations as $userId => $alloc) {
                if (!isset($submittedDeductions[$userId]) || (float) $submittedDeductions[$userId] <= 0) {
                    $prevAmount = (float) $alloc->amount;
                    $payroll = $alloc->payroll_id ? Payroll::find($alloc->payroll_id) : Payroll::where('user_id', $userId)->where('period_month', $month)->where('period_year', $year)->first();
                    if ($payroll) {
                        $newOtherDeductions = max(0, (float) $payroll->other_deductions - $prevAmount);
                        $pattern = '/(\[Pot\. SO ' . preg_quote($dateLabel, '/') . ':[^\]]+\]\s*(-\s*[^|]+)?(\s*\|\s*)?)/';
                        $cleanedNote = trim(trim(preg_replace($pattern, '', (string) $payroll->note)), '|');

                        $gross = (float) $payroll->basic_salary 
                            + (float) $payroll->total_daily_allowance 
                            + (float) $payroll->regular_overtime_pay 
                            + (float) $payroll->holiday_overtime_pay 
                            + (float) $payroll->bonus;
                        $totalDeductions = (float) $payroll->late_deduction + $newOtherDeductions;
                        $netSalary = max(0, round($gross - $totalDeductions, 2));

                        $payroll->update([
                            'other_deductions' => $newOtherDeductions,
                            'note'             => substr($cleanedNote, 0, 500),
                            'net_salary'       => $netSalary,
                        ]);
                    }
                    $alloc->delete();
                }
            }

            // 2. Insert or update submitted employee deductions (calculating delta so no double accumulation!)
            foreach ($submittedDeductions as $userId => $amount) {
                $deduction = (float) $amount;
                if ($deduction <= 0) {
                    continue;
                }

                $user = User::with(['stores', 'payrollSetting'])->find($userId);
                if (!$user) {
                    continue;
                }

                $prevAlloc = $existingAllocations->get($userId);
                $prevAmount = $prevAlloc ? (float) $prevAlloc->amount : 0;
                $delta = $deduction - $prevAmount;

                $existingPayroll = Payroll::where('user_id', $user->id)
                    ->where('period_month', $month)
                    ->where('period_year', $year)
                    ->first();

                $noteTag = "[Pot. SO {$dateLabel}: Rp " . number_format($deduction, 0, ',', '.') . "]";
                $noteSuffix = ($noteInput !== "Beban Selisih SO {$dateLabel}" ? " - {$noteInput}" : '');

                if ($existingPayroll) {
                    $newOtherDeductions = max(0, (float) $existingPayroll->other_deductions + $delta);

                    $currentNote = (string) $existingPayroll->note;
                    $pattern = '/\[Pot\. SO ' . preg_quote($dateLabel, '/') . ':[^\]]+\](\s*-\s*[^|]+)?/';
                    if (preg_match($pattern, $currentNote)) {
                        $combinedNote = preg_replace($pattern, $noteTag . $noteSuffix, $currentNote);
                    } else {
                        $combinedNote = $currentNote 
                            ? $currentNote . ' | ' . $noteTag . $noteSuffix
                            : $noteTag . $noteSuffix;
                    }

                    $gross = (float) $existingPayroll->basic_salary 
                        + (float) $existingPayroll->total_daily_allowance 
                        + (float) $existingPayroll->regular_overtime_pay 
                        + (float) $existingPayroll->holiday_overtime_pay 
                        + (float) $existingPayroll->bonus;
                    $totalDeductions = (float) $existingPayroll->late_deduction + $newOtherDeductions;
                    $netSalary = max(0, round($gross - $totalDeductions, 2));

                    $existingPayroll->update([
                        'other_deductions' => $newOtherDeductions,
                        'note'             => substr($combinedNote, 0, 500),
                        'net_salary'       => $netSalary,
                    ]);

                    $payrollId = $existingPayroll->id;
                } else {
                    $setting = $user->payrollSetting;
                    $basicSalary = (float) ($setting?->basic_salary ?? 0);
                    $dailyRate = (float) ($setting?->daily_allowance ?? 0);
                    $payrollCode = 'PAY-' . $year . str_pad((string)$month, 2, '0', STR_PAD_LEFT) . '-' . str_pad((string)$user->id, 3, '0', STR_PAD_LEFT);
                    $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                    $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                    $assignedStoreId = $storeId ?? $user->stores->first()?->id ?? null;

                    $newPayroll = Payroll::create([
                        'payroll_code'             => $payrollCode,
                        'user_id'                  => $user->id,
                        'store_id'                 => $assignedStoreId,
                        'period_month'             => $month,
                        'period_year'              => $year,
                        'start_date'               => $startDate->toDateString(),
                        'end_date'                 => $endDate->toDateString(),
                        'payment_type'             => $setting?->payment_type ?? 'monthly',
                        'basic_salary'             => $basicSalary,
                        'total_days_worked'        => 0,
                        'daily_allowance_rate'     => $dailyRate,
                        'total_daily_allowance'    => 0,
                        'regular_overtime_minutes' => 0,
                        'regular_overtime_rate'    => (float) ($setting?->overtime_rate_per_hour ?? 0),
                        'regular_overtime_pay'     => 0,
                        'holiday_overtime_minutes' => 0,
                        'holiday_overtime_rate'    => (float) ($setting?->holiday_overtime_rate_per_hour ?? 0),
                        'holiday_overtime_pay'     => 0,
                        'late_minutes'             => 0,
                        'late_deduction'           => 0,
                        'bonus'                    => 0,
                        'other_deductions'         => $deduction,
                        'net_salary'               => max(0, round($basicSalary - $deduction, 2)),
                        'status'                   => 'draft',
                        'note'                     => $noteTag . $noteSuffix,
                        'created_by'               => Auth::id(),
                    ]);

                    $payrollId = $newPayroll->id;
                }

                if ($prevAlloc) {
                    $prevAlloc->update([
                        'payroll_id' => $payrollId,
                        'amount'     => $deduction,
                    ]);
                } else {
                    StockOpnamePayrollAllocation::create([
                        'opname_date' => $formattedSoDate,
                        'user_id'     => $userId,
                        'payroll_id'  => $payrollId,
                        'amount'      => $deduction,
                    ]);
                }

                $assignedCount++;
                $totalAssignedAmount += $deduction;
            }
        });

        if ($assignedCount === 0) {
            return redirect()->back()->with('error', 'Tidak ada potongan yang dibebankan (pilih minimal 1 karyawan dengan nominal > 0).');
        }

        $formattedTotal = 'Rp ' . number_format($totalAssignedAmount, 0, ',', '.');
        $monthName = Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y');
        return redirect()->back()->with('success', "Berhasil memperbarui alokasi selisih SO sebesar {$formattedTotal} untuk {$assignedCount} karyawan (Periode: {$monthName}).");
    }

    /**
     * Show items inside a specific stock opname date folder.
     */
    public function showByDate(Request $request, string $date)
    {
        Gate::authorize('Movement List');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            abort(404, 'Format tanggal opname tidak valid.');
        }

        $session = MovementDate::with('creator')
            ->where('type', 'opname')
            ->where('date', $date)
            ->first();

        $search = $request->query('search');
        $reason = $request->query('reason');
        $productId = $request->query('product_id');

        $adjustmentsQuery = StockAdjustment::with(['product', 'user', 'reversedBy', 'original', 'reversal'])
            ->whereDate('created_at', $date)
            ->when(!empty($productId), fn ($q) => $q->where('product_id', (int) $productId))
            ->when(!empty($reason), fn ($q) => $q->where('reason', $reason))
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->whereHas('product', function ($pq) use ($search) {
                        $pq->where('name', 'like', "%{$search}%")
                           ->orWhere('sku', 'like', "%{$search}%");
                    })->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->latest();

        $adjustments = $adjustmentsQuery->paginate(50)->withQueryString();

        // Summary for this date (respecting any active filters)
        $allDateAdjustments = (clone $adjustmentsQuery)->get();
        $activeMinusAdjustments = $allDateAdjustments->filter(fn($a) => !$a->isReversed() && $a->reason !== 'reversal' && $a->quantity_change < 0);

        $summary = [
            'total_items'        => $allDateAdjustments->count(),
            'total_added'        => $allDateAdjustments->where('quantity_change', '>', 0)->sum('quantity_change'),
            'total_reduced'      => $allDateAdjustments->where('quantity_change', '<', 0)->sum('quantity_change'),
            'net_change'         => $allDateAdjustments->sum('quantity_change'),
            'total_minus_units'  => abs((int) $activeMinusAdjustments->sum('quantity_change')),
            'total_harga_jual'   => (float) $activeMinusAdjustments->sum(fn($a) => abs($a->quantity_change) * (float) ($a->product?->selling_price ?? 0)),
            'active_items_count' => $allDateAdjustments->filter(fn($a) => !$a->isReversed() && $a->reason !== 'reversal')->count(),
            'has_reversals'      => $allDateAdjustments->contains(fn($i) => $i->isReversed() || $i->reason === 'reversal'),
        ];

        return view('inventory.adjustments.date_show', [
            'date'        => $date,
            'session'     => $session,
            'adjustments' => $adjustments,
            'summary'     => $summary,
            'products'    => Product::orderBy('name')->get(),
            'reasons'     => StockAdjustment::REASONS,
            'search'      => $search,
            'reason'      => $reason,
            'productId'   => $productId,
        ]);
    }

    /**
     * Show form to create a new adjustment.
     */
    public function create(Request $request)
    {
        Gate::authorize('Stock In Store');

        $selectedDate = $request->query('date', date('Y-m-d'));
        $products = Product::orderBy('name')->get();
        $categories = $products->pluck('category')->unique()->filter()->values();

        return view('inventory.adjustments.create', [
            'products'     => $products,
            'categories'   => $categories,
            'reasons'      => array_diff_key(StockAdjustment::REASONS, ['reversal' => true]),
            'selectedDate' => $selectedDate,
        ]);
    }

    /**
     * Store a newly created stock adjustment (supports batch items or single item).
     */
    public function store(StoreStockAdjustmentRequest $request, InventoryService $service)
    {
        try {
            $adjustmentDate = $request->input('adjustment_date') ?: null;

            // Also ensure a MovementDate session container exists for this date
            if ($adjustmentDate) {
                MovementDate::firstOrCreate(
                    [
                        'date'     => $adjustmentDate,
                        'type'     => 'opname',
                        'store_id' => session('active_store_id'),
                    ],
                    [
                        'created_by' => $request->user()->id,
                    ]
                );
            }

            // 1. Batch Mode
            if ($request->has('items') && is_array($request->input('items'))) {
                $items = $request->input('items');
                $count = 0;

                DB::transaction(function () use ($items, $service, $request, $adjustmentDate, &$count) {
                    foreach ($items as $item) {
                        $service->adjust(
                            Product::findOrFail($item['product_id']),
                            (int) $item['quantity_change'],
                            $item['reason'],
                            $item['notes'] ?? null,
                            $request->user()->id,
                            $adjustmentDate
                        );
                        $count++;
                    }
                });

                if ($request->boolean('redirect_to_date') && !empty($adjustmentDate)) {
                    return redirect()->route('stock-adjustments.date', ['date' => $adjustmentDate])
                        ->with('success', "Berhasil menyimpan {$count} produk penyesuaian stok.");
                }

                return redirect()->route('stock-adjustments.index')
                    ->with('success', "Berhasil menyimpan {$count} produk penyesuaian stok.");
            }

            // 2. Single Item Mode (Backward Compatibility)
            $data = $request->validated();
            $service->adjust(
                Product::findOrFail($data['product_id']),
                (int) $data['quantity_change'],
                $data['reason'],
                $data['notes'] ?? null,
                $request->user()->id,
                $adjustmentDate
            );
        } catch (LogicException $e) {
            return back()->withErrors(['quantity_change' => $e->getMessage()])->withInput();
        }

        // If explicitly requested from a date folder, redirect back to that folder
        if ($request->boolean('redirect_to_date') && !empty($adjustmentDate)) {
            return redirect()->route('stock-adjustments.date', ['date' => $adjustmentDate])
                ->with('success', 'Penyesuaian stok tersimpan.');
        }

        return redirect()->route('stock-adjustments.index')
            ->with('success', 'Penyesuaian stok tersimpan.');
    }

    /**
     * Display the specified stock adjustment record.
     */
    public function show(StockAdjustment $stockAdjustment)
    {
        Gate::authorize('Movement List');

        return view('inventory.adjustments.show', [
            'adjustment' => $stockAdjustment->load(['product', 'user', 'reversedBy', 'original', 'reversal'])
        ]);
    }

    /**
     * Reverse an existing stock adjustment.
     */
    public function reverse(StockAdjustment $stockAdjustment, Request $request, InventoryService $service)
    {
        Gate::authorize('Stock In Store');

        $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        try {
            $service->reverse($stockAdjustment, $request->user()->id, $request->input('notes'));
        } catch (LogicException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Penyesuaian dibatalkan dengan record reversal.');
    }
}
