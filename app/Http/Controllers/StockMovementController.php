<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\MovementDate;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreStockMovementRequest;

class StockMovementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('Movement List');

        // Load movement dates with their movements, filtered if needed
        $movementDatesQuery = MovementDate::query();

        if ($request->has('type') && in_array($request->type, ['in', 'out'])) {
            $movementDatesQuery->where('type', $request->type);
        }
        if ($request->has('product_id') && $request->product_id != '') {
            $movementDatesQuery->whereHas('movements', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }
        if ($request->has('date') && $request->date != '') {
            $movementDatesQuery->where('date', $request->date);
        }

        $movementDatesQuery->with(['creator', 'movements' => function ($q) use ($request) {
            $q->with(['product', 'user']);
            if ($request->has('product_id') && $request->product_id != '') {
                $q->where('product_id', $request->product_id);
            }
            $q->orderBy('created_at', 'desc');
        }])->orderBy('date', 'desc');

        $movementDates = $movementDatesQuery->paginate(10)->withQueryString();
        $products = Product::orderBy('name')->get();

        $selectedType = $request->get('type');
        $summaryTotalAmount = 0;
        $summaryTotalQuantity = 0;
        $summaryTotalDiscount = 0;
        $summaryTotalLogs = 0;
        $paymentBreakdown = [];

        $allMovQuery = StockMovement::query()
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });
        if ($selectedType && in_array($selectedType, ['in', 'out'])) {
            $allMovQuery->where('type', $selectedType);
        }
        if ($request->filled('date')) {
            $allMovQuery->where('movement_date', $request->date);
        }
        if ($request->filled('product_id')) {
            $allMovQuery->where('product_id', $request->product_id);
        }

        $summaryTotalLogs = (int) (clone $allMovQuery)->count();
        $summaryTotalQuantity = (int) (clone $allMovQuery)->sum('quantity');
        $summaryTotalDiscount = (float) (clone $allMovQuery)->sum('discount');
        $summaryTotalAmount = (float) (clone $allMovQuery)->sum(
            DB::raw("COALESCE(total_price, CASE WHEN type = 'in' THEN quantity * COALESCE(purchase_price, 0) ELSE (quantity * COALESCE(selling_price, 0)) - COALESCE(discount, 0) END, 0)")
        );

        if ($selectedType === 'out') {
                $pmStats = (clone $allMovQuery)
                    ->select(
                        DB::raw("COALESCE(NULLIF(TRIM(payment_method), ''), 'cash') as method"),
                        DB::raw('COUNT(*) as total_count'),
                        DB::raw("SUM(COALESCE(total_price, (quantity * COALESCE(selling_price, 0)) - COALESCE(discount, 0), 0)) as total_amount")
                    )
                    ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(payment_method), ''), 'cash')"))
                    ->get();

                $paymentBreakdown = [
                    'cash' => ['label' => 'Tunai (Cash)', 'icon' => 'payments', 'color' => 'emerald', 'count' => 0, 'amount' => 0.0],
                    'transfer' => ['label' => 'Transfer Bank', 'icon' => 'account_balance', 'color' => 'blue', 'count' => 0, 'amount' => 0.0],
                    'qris' => ['label' => 'QRIS', 'icon' => 'qr_code_scanner', 'color' => 'purple', 'count' => 0, 'amount' => 0.0],
                    'debit' => ['label' => 'Kartu Debit', 'icon' => 'credit_card', 'color' => 'cyan', 'count' => 0, 'amount' => 0.0],
                    'credit' => ['label' => 'Kartu Kredit', 'icon' => 'credit_score', 'color' => 'amber', 'count' => 0, 'amount' => 0.0],
                    'split' => ['label' => 'Split Payment', 'icon' => 'call_split', 'color' => 'purple', 'count' => 0, 'amount' => 0.0],
                    'other' => ['label' => 'Lainnya', 'icon' => 'more_horiz', 'color' => 'gray', 'count' => 0, 'amount' => 0.0],
                ];

                foreach ($pmStats as $row) {
                    $mKey = strtolower(trim($row->method));
                    if (!isset($paymentBreakdown[$mKey])) {
                        $paymentBreakdown[$mKey] = ['label' => ucfirst($mKey), 'icon' => 'payments', 'color' => 'gray', 'count' => 0, 'amount' => 0.0];
                    }
                    $paymentBreakdown[$mKey]['count'] = (int) $row->total_count;
                    $paymentBreakdown[$mKey]['amount'] = (float) $row->total_amount;
                }
            } elseif ($selectedType === 'in') {
                $pmStats = (clone $allMovQuery)
                    ->select(
                        DB::raw("COALESCE(NULLIF(TRIM(payment_method), ''), 'cash') as method"),
                        DB::raw('COUNT(*) as total_count'),
                        DB::raw("SUM(COALESCE(total_price, quantity * COALESCE(purchase_price, 0), 0)) as total_amount")
                    )
                    ->groupBy(DB::raw("COALESCE(NULLIF(TRIM(payment_method), ''), 'cash')"))
                    ->get();

                $paymentBreakdown = [
                    'cash' => ['label' => 'Tunai / Kas Toko', 'icon' => 'payments', 'color' => 'emerald', 'count' => 0, 'amount' => 0.0],
                    'transfer' => ['label' => 'Transfer Bank', 'icon' => 'account_balance', 'color' => 'blue', 'count' => 0, 'amount' => 0.0],
                    'tempo' => ['label' => 'Tempo / Hutang Dagang', 'icon' => 'pending_actions', 'color' => 'amber', 'count' => 0, 'amount' => 0.0],
                ];

                foreach ($pmStats as $row) {
                    $mKey = strtolower(trim($row->method));
                    if (!isset($paymentBreakdown[$mKey])) {
                        $paymentBreakdown[$mKey] = ['label' => ucfirst($mKey), 'icon' => 'payments', 'color' => 'gray', 'count' => 0, 'amount' => 0.0];
                    }
                    $paymentBreakdown[$mKey]['count'] = (int) $row->total_count;
                    $paymentBreakdown[$mKey]['amount'] = (float) $row->total_amount;
                }
            }

        $suppliers = Setting::where('type', 'supplier')->orderBy('value')->pluck('value')->unique()->values();

        return view('movements.index', compact(
            'movementDates',
            'products',
            'suppliers',
            'summaryTotalAmount',
            'summaryTotalQuantity',
            'summaryTotalDiscount',
            'summaryTotalLogs',
            'paymentBreakdown'
        ));
    }

    public function create(Request $request)
    {
        $selectedType = $request->get('type', 'in');
        if ($selectedType === 'in') {
            Gate::authorize('Stock In Create');
        } else {
            Gate::authorize('Stock Out Create');
        }

        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        $suppliers = Setting::where('type', 'supplier')->orderBy('value')->pluck('value')->unique()->values();
        $selectedProductId = $request->get('product_id');

        return view('movements.create', compact('products', 'users', 'suppliers', 'selectedProductId', 'selectedType'));
    }

    public function store(StoreStockMovementRequest $request)
    {
        $type = $request->input('type', 'in');
        if ($type === 'in') {
            Gate::authorize('Stock In Store');
        } else {
            Gate::authorize('Stock Out Store');
        }

        $validated = $request->validate([
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.purchase_price' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'product_id' => 'nullable|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
            'movement_date' => 'required|date',
            'user_id' => 'nullable|exists:users,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'total_price' => 'nullable|numeric',
            'payment_method' => 'nullable|string|max:50',
            'customer_name' => 'nullable|string|max:255',
            'supplier_name' => 'nullable|string|max:150',
            'due_date' => 'nullable|date',
        ]);

        $userId = $validated['user_id'] ?? auth()->id();
        $rawItems = $request->input('items');
        $rawProductIds = $request->input('product_ids');
        $itemList = [];

        if (is_array($rawItems) && count($rawItems) > 0) {
            foreach ($rawItems as $rawItem) {
                if (!empty($rawItem['product_id']) && !empty($rawItem['quantity']) && (int)$rawItem['quantity'] > 0) {
                    $itemList[] = [
                        'product_id' => (int) $rawItem['product_id'],
                        'quantity' => (int) $rawItem['quantity'],
                        'purchase_price' => isset($rawItem['purchase_price']) && is_numeric($rawItem['purchase_price']) ? (float)$rawItem['purchase_price'] : null,
                        'notes' => $rawItem['notes'] ?? ($validated['notes'] ?? null),
                    ];
                }
            }
        } elseif (is_array($rawProductIds) && count($rawProductIds) > 0) {
            $productIds = array_values(array_unique(array_filter($rawProductIds)));
            foreach ($productIds as $pId) {
                $itemList[] = [
                    'product_id' => (int) $pId,
                    'quantity' => (int) ($validated['quantity'] ?? 1),
                    'purchase_price' => isset($validated['purchase_price']) && is_numeric($validated['purchase_price']) ? (float)$validated['purchase_price'] : null,
                    'notes' => $validated['notes'] ?? null,
                ];
            }
        } elseif ($request->filled('product_id')) {
            $itemList[] = [
                'product_id' => (int) $request->input('product_id'),
                'quantity' => (int) ($validated['quantity'] ?? 1),
                'purchase_price' => isset($validated['purchase_price']) && is_numeric($validated['purchase_price']) ? (float)$validated['purchase_price'] : null,
                'notes' => $validated['notes'] ?? null,
            ];
        }

        if (empty($itemList)) {
            return back()
                ->withErrors(['product_id' => 'Silakan pilih minimal satu produk.'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $movementDateFormatted = \Illuminate\Support\Carbon::parse($validated['movement_date'])->format('Y-m-d');

            // Ensure a MovementDate container exists for this date and type
            $movementDateRecord = \App\Models\MovementDate::firstOrCreate(
                [
                    'date' => $movementDateFormatted,
                    'type' => $validated['type'],
                ],
                [
                    'created_by' => $userId,
                    'notes' => 'Auto-created from manual entry'
                ]
            );

            $lastRefNumber = null;
            $createdCount = 0;

            // Prepare matching Sale transaction for Stock Out (to synchronize with Sales History and Shift Recap)
            $sale = null;
            if ($validated['type'] === 'out') {
                do {
                    $refNumber = 'SALE-' . rand(10000, 99999);
                } while (Sale::where('reference_number', $refNumber)->exists() || StockMovement::where('reference_number', $refNumber)->exists());

                $lastRefNumber = $refNumber;
                $paymentMethodValue = strtolower($validated['payment_method'] ?? 'cash');
                $paymentMethodUpper = strtoupper($paymentMethodValue);
                $userModel = User::find($userId) ?? auth()->user();
                $userName = $userModel ? $userModel->name : 'Staff';
                $customNote = !empty($validated['notes']) ? " | " . $validated['notes'] : "";
                $saleNotes = "[Stock Out - {$userName}] Bayar: {$paymentMethodUpper}{$customNote}";

                $firstProduct = Product::find($itemList[0]['product_id'] ?? null);
                $storeId = session('active_store_id') 
                    ?? $firstProduct?->store_id 
                    ?? $userModel?->stores()?->first()?->id 
                    ?? 1;

                $saleDateTime = \Illuminate\Support\Carbon::parse($movementDateFormatted . ' ' . \Illuminate\Support\Carbon::now()->format('H:i:s'));
                $customerName = !empty($validated['customer_name']) ? $validated['customer_name'] : 'Pelanggan Umum';

                $sale = Sale::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'reference_number' => $refNumber,
                    'sale_date' => $movementDateFormatted,
                    'customer_name' => $customerName,
                    'total_amount' => 0,
                    'notes' => $saleNotes,
                    'payment_method' => $paymentMethodValue,
                    'status' => 'completed',
                    'user_id' => $userId,
                    'created_at' => $saleDateTime,
                    'updated_at' => $saleDateTime,
                ]);

                DB::table('sales')->where('id', $sale->id)->update([
                    'created_at' => $saleDateTime,
                    'updated_at' => $saleDateTime,
                ]);
            }

            $totalSaleAmount = 0;

            foreach ($itemList as $itemData) {
                $product = Product::whereKey($itemData['product_id'])->lockForUpdate()->firstOrFail();
                $quantity = (int) $itemData['quantity'];

                // Business logic validation: Prevent negative stock
                if ($validated['type'] === 'out' && $product->stock < $quantity) {
                    DB::rollBack();
                    return back()
                        ->withErrors(['quantity' => "Insufficient stock. Current stock for {$product->name} is only {$product->stock} units."])
                        ->withInput();
                }

                if ($validated['type'] === 'in') {
                    // Auto-generate reference number for Stock In: TXN-XXXXX
                    do {
                        $refNumber = 'TXN-' . rand(10000, 99999);
                    } while (StockMovement::where('reference_number', $refNumber)->exists());
                    $lastRefNumber = $refNumber;
                } else {
                    $refNumber = $sale->reference_number;
                }

                // Determine unit purchase price
                if ($validated['type'] === 'in') {
                    $movementPurchasePrice = isset($itemData['purchase_price']) && is_numeric($itemData['purchase_price'])
                        ? (float) $itemData['purchase_price']
                        : (float) $product->purchase_price;
                } else {
                    $movementPurchasePrice = isset($itemData['purchase_price']) && is_numeric($itemData['purchase_price'])
                        ? (float) $itemData['purchase_price']
                        : (float) ($product->purchase_price ?? 0);
                }

                $sellingPrice = isset($validated['selling_price']) && is_numeric($validated['selling_price'])
                    ? (float) $validated['selling_price']
                    : (float) ($product->selling_price ?? 0);

                $discount = $validated['type'] === 'in' ? 0 : ($validated['discount'] ?? 0);

                if ($validated['type'] === 'out' && isset($validated['total_price']) && is_numeric($validated['total_price']) && count($itemList) === 1) {
                    $lineTotal = (float) $validated['total_price'];
                } else {
                    $lineTotal = ($validated['type'] === 'in')
                        ? ($quantity * $movementPurchasePrice)
                        : max(0, ($quantity * $sellingPrice) - $discount);
                }

                $status = ($validated['type'] === 'in') ? 'draft' : 'approved';
                $resolvedStoreId = session('active_store_id') ?? $product->store_id ?? ($sale ? $sale->store_id : 1);

                $paymentMethod = $validated['payment_method'] ?? ($validated['type'] === 'in' ? ($movementDateRecord->payment_method ?: 'cash') : 'cash');
                $isTempo = ($validated['type'] === 'in' && in_array(strtolower($paymentMethod), ['tempo', 'hutang', 'credit']));
                $paymentStatus = ($validated['type'] === 'in')
                    ? ($isTempo ? 'pending' : 'paid')
                    : 'paid';
                $paidAmount = ($validated['type'] === 'in')
                    ? ($isTempo ? 0 : $lineTotal)
                    : $lineTotal;
                $dueDate = ($isTempo && !empty($validated['due_date']))
                    ? $validated['due_date']
                    : (($isTempo && $movementDateRecord->due_date) ? $movementDateRecord->due_date->format('Y-m-d') : null);
                $supplierName = ($validated['type'] === 'in')
                    ? (!empty($validated['supplier_name']) ? $validated['supplier_name'] : $movementDateRecord->supplier_name)
                    : null;

                if ($validated['type'] === 'in' && !empty($supplierName)) {
                    $trimmedSupplier = trim($supplierName);
                    if ($trimmedSupplier !== '') {
                        Setting::firstOrCreate([
                            'type' => 'supplier',
                            'value' => $trimmedSupplier,
                        ]);
                    }
                }

                // Create movement
                StockMovement::create([
                    'store_id' => $resolvedStoreId,
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'reference_number' => $refNumber,
                    'movement_date' => $movementDateFormatted,
                    'type' => $validated['type'],
                    'quantity' => $quantity,
                    'purchase_price' => $movementPurchasePrice,
                    'selling_price' => $sellingPrice,
                    'discount' => $discount,
                    'total_price' => $lineTotal,
                    'payment_method' => $paymentMethod,
                    'supplier_name' => $supplierName,
                    'payment_status' => $paymentStatus,
                    'due_date' => $dueDate,
                    'paid_amount' => $paidAmount,
                    'status' => $status,
                    'notes' => $itemData['notes'] ?? ($validated['notes'] ?? null),
                ]);

                // Create matching sale item for Stock Out
                if ($validated['type'] === 'out' && $sale) {
                    $sale->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'selling_price' => $sellingPrice,
                        'discount' => $discount,
                        'subtotal' => $lineTotal,
                    ]);
                    $totalSaleAmount += $lineTotal;
                }

                // Adjust product stock & update Moving Average Cost ONLY IF APPROVED (Stock Out immediately, Stock In on Approve)
                if ($status === 'approved') {
                    if ($validated['type'] === 'in') {
                        $product->applyStockInMovingAverage((float) $quantity, $movementPurchasePrice);
                    } else {
                        $product->stock -= $quantity;
                        $product->save();
                    }
                }

                $createdCount++;
            }

            if ($validated['type'] === 'out' && $sale) {
                $sale->total_amount = $totalSaleAmount;
                $sale->timestamps = false;
                $sale->save();
                DB::table('sales')->where('id', $sale->id)->update([
                    'created_at' => $saleDateTime,
                    'updated_at' => $saleDateTime,
                ]);

                // Record double-entry sale journal
                app(\App\Services\AccountingService::class)->recordSaleJournal($sale);
            }

            DB::commit();

            if ($validated['type'] === 'in') {
                $successMessage = ($createdCount > 1)
                    ? "Berhasil menyimpan {$createdCount} produk sebagai DRAFT. Silakan periksa kembali sebelum melakukan Approve."
                    : "Movement {$lastRefNumber} berhasil disimpan sebagai DRAFT. Silakan periksa kembali sebelum melakukan Approve.";
            } else {
                $successMessage = ($createdCount > 1)
                    ? "Berhasil mencatat mutasi untuk {$createdCount} produk (" . ($createdCount * $quantity) . " total unit)."
                    : "Movement {$lastRefNumber} berhasil dicatat.";
            }

            return redirect()
                ->route('movement-dates.show', $movementDateRecord->id)
                ->with('success', $successMessage);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'An error occurred while saving the stock movement: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Update a draft Stock In movement (quantity, purchase_price, selling_price).
     */
    public function update(Request $request, $id)
    {
        if (!Gate::allows('Stock In Store') && !Gate::allows('Stock In Create') && !Gate::allows('Movement List') && !auth()->user()->hasRole('Super Admin')) {
            abort(403);
        }

        $movement = StockMovement::with('product')->findOrFail($id);

        if ($movement->type !== 'in' || $movement->status !== 'draft') {
            return back()->withErrors(['error' => 'Hanya item Stock In berstatus DRAFT yang dapat diedit.']);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
        ], [
            'quantity.required' => 'Jumlah quantity wajib diisi.',
            'quantity.integer' => 'Jumlah quantity harus berupa angka bulat.',
            'quantity.min' => 'Jumlah quantity minimal 1 unit.',
            'purchase_price.required' => 'Harga beli wajib diisi.',
            'purchase_price.numeric' => 'Harga beli harus berupa angka.',
            'purchase_price.min' => 'Harga beli tidak boleh negatif.',
            'selling_price.numeric' => 'Harga jual harus berupa angka.',
            'selling_price.min' => 'Harga jual tidak boleh negatif.',
        ]);

        try {
            DB::beginTransaction();

            $newQty = (int) $validated['quantity'];
            $newPurchasePrice = (float) $validated['purchase_price'];
            $newSellingPrice = isset($validated['selling_price']) && is_numeric($validated['selling_price'])
                ? (float) $validated['selling_price']
                : ($movement->selling_price ?? $movement->product?->selling_price ?? 0);

            $newTotalPrice = $newQty * $newPurchasePrice;

            $movement->quantity = $newQty;
            $movement->purchase_price = $newPurchasePrice;
            $movement->selling_price = $newSellingPrice;
            $movement->total_price = $newTotalPrice;

            // Recalculate paid_amount if cash/transfer (non-tempo)
            $isTempo = in_array(strtolower(trim($movement->payment_method ?? '')), ['tempo', 'hutang', 'credit']);
            if (!$isTempo) {
                $movement->paid_amount = $newTotalPrice;
                $movement->payment_status = 'paid';
            }

            $movement->save();

            DB::commit();

            $prodName = $movement->product?->name ?? 'Produk';
            return back()->with('success', "Item mutasi draft #{$movement->reference_number} ({$prodName}) berhasil diperbarui! Qty: {$newQty}, Harga Beli: Rp " . number_format($newPurchasePrice, 0, ',', '.') . ", Harga Jual: Rp " . number_format($newSellingPrice, 0, ',', '.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal memperbarui item mutasi: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve a single draft stock movement.
     */
    public function approve($id)
    {
        Gate::authorize('Stock In Store');

        $movement = StockMovement::findOrFail($id);

        if ($movement->status !== 'draft') {
            return back()->with('info', "Movement {$movement->reference_number} sudah disetujui sebelumnya.");
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($movement->product_id);

            if ($movement->type === 'in') {
                $product->applyStockInMovingAverage((float) $movement->quantity, $movement->purchase_price);
                if ($movement->selling_price !== null && (float) $movement->selling_price > 0) {
                    $product->selling_price = (float) $movement->selling_price;
                    $product->save();
                }
            } else {
                if ($product->stock < $movement->quantity) {
                    DB::rollBack();
                    return back()->withErrors(['error' => "Stok produk {$product->name} tidak mencukupi untuk di-approve (stok: {$product->stock}, qty: {$movement->quantity})."]);
                }
                $product->stock -= $movement->quantity;
                $product->save();
            }

            $movement->status = 'approved';
            $movement->save();

            // Record double-entry stock in journal
            if ($movement->type === 'in') {
                app(\App\Services\AccountingService::class)->recordStockInJournal($movement);
            }

            DB::commit();

            return back()->with('success', "Movement {$movement->reference_number} ({$product->name}) berhasil di-approve! Stok fisik dan HPP telah diperbarui.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal meng-approve movement: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk approve draft stock movements.
     */
    public function bulkApprove(Request $request)
    {
        Gate::authorize('Stock In Store');

        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:stock_movements,id',
            'movement_date_id' => 'nullable|exists:movement_dates,id',
        ]);

        try {
            DB::beginTransaction();

            if ($request->boolean('approve_all') && $request->filled('movement_date_id')) {
                $movementDate = MovementDate::findOrFail($request->movement_date_id);
                $movements = StockMovement::where('movement_date', \Carbon\Carbon::parse($movementDate->date)->format('Y-m-d'))
                    ->where('type', $movementDate->type ?? 'in')
                    ->where('status', 'draft')
                    ->get();
            } else {
                $ids = $validated['ids'] ?? [];
                if (empty($ids)) {
                    return back()->withErrors(['error' => 'Pilih minimal satu item draft untuk di-approve.']);
                }
                $movements = StockMovement::whereIn('id', $ids)->where('status', 'draft')->get();
            }

            if ($movements->isEmpty()) {
                return back()->with('info', 'Tidak ada item berstatus draft yang perlu di-approve.');
            }

            $approvedCount = 0;
            foreach ($movements as $movement) {
                $product = Product::find($movement->product_id);
                if ($product) {
                    if ($movement->type === 'in') {
                        $product->applyStockInMovingAverage((float) $movement->quantity, $movement->purchase_price);
                        if ($movement->selling_price !== null && (float) $movement->selling_price > 0) {
                            $product->selling_price = (float) $movement->selling_price;
                            $product->save();
                        }
                    } else {
                        if ($product->stock < $movement->quantity) {
                            DB::rollBack();
                            return back()->withErrors(['error' => "Stok produk {$product->name} tidak mencukupi untuk di-approve."]);
                        }
                        $product->stock -= $movement->quantity;
                        $product->save();
                    }
                }

                $movement->status = 'approved';
                $movement->save();

                // Record double-entry stock in journal
                if ($movement->type === 'in') {
                    app(\App\Services\AccountingService::class)->recordStockInJournal($movement);
                }

                $approvedCount++;
            }

            DB::commit();

            return back()->with('success', "Berhasil meng-approve {$approvedCount} item mutasi draft. Stok fisik dan HPP telah disesuaikan!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal meng-approve mutasi: ' . $e->getMessage()]);
        }
    }

    /**
     * Revert an approved Stock In movement back to draft.
     */
    public function unapprove($id)
    {
        if (!Gate::allows('Stock In Store') && !Gate::allows('Stock In Create') && !Gate::allows('Movement List')) {
            abort(403);
        }

        $movement = StockMovement::findOrFail($id);

        if ($movement->type !== 'in') {
            return back()->withErrors(['error' => 'Hanya mutasi Stock In yang dapat dikembalikan ke status draft.']);
        }

        if ($movement->status !== 'approved') {
            return back()->with('info', "Movement #{$movement->reference_number} saat ini tidak berstatus approved (status: {$movement->status}).");
        }

        if ($movement->supplierPayments()->exists()) {
            return back()->withErrors(['error' => "Gagal: Movement #{$movement->reference_number} memiliki riwayat pembayaran supplier/hutang. Batalkan atau hapus pembayaran terlebih dahulu."]);
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($movement->product_id);

            // Ensure stock won't drop below 0
            if ($product->stock < $movement->quantity) {
                DB::rollBack();
                return back()->withErrors(['error' => "Gagal: Stok produk {$product->name} saat ini ({$product->stock} unit) tidak mencukupi untuk dikurangi {$movement->quantity} unit (mungkin stok sudah terjual)."]);
            }

            // Safely revert stock and Moving Average Cost (HPP)
            $product->revertStockInMovingAverage((float) $movement->quantity, $movement->purchase_price);

            $movement->status = 'draft';
            $movement->save();

            // Purge stock movement journal from general ledger
            app(\App\Services\AccountingService::class)->purgeJournal('stock_movement', $movement->id);

            DB::commit();

            return back()->with('success', "Movement #{$movement->reference_number} ({$product->name}) berhasil dikembalikan ke status DRAFT. Stok fisik dan HPP telah disesuaikan kembali, serta jurnal transaksi telah dihapus.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengembalikan status movement ke draft: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk revert approved Stock In movements back to draft.
     */
    public function bulkUnapprove(Request $request)
    {
        if (!Gate::allows('Stock In Store') && !Gate::allows('Stock In Create') && !Gate::allows('Movement List')) {
            abort(403);
        }

        $validated = $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:stock_movements,id',
            'movement_date_id' => 'nullable|exists:movement_dates,id',
        ]);

        try {
            DB::beginTransaction();

            if ($request->boolean('unapprove_all') && $request->filled('movement_date_id')) {
                $movementDate = MovementDate::findOrFail($request->movement_date_id);
                $movements = StockMovement::where('movement_date', \Carbon\Carbon::parse($movementDate->date)->format('Y-m-d'))
                    ->where('type', 'in')
                    ->where('status', 'approved')
                    ->get();
            } else {
                $ids = $validated['ids'] ?? [];
                if (empty($ids)) {
                    return back()->withErrors(['error' => 'Pilih minimal satu item approved untuk dikembalikan ke draft.']);
                }
                $movements = StockMovement::whereIn('id', $ids)
                    ->where('type', 'in')
                    ->where('status', 'approved')
                    ->get();
            }

            if ($movements->isEmpty()) {
                return back()->with('info', 'Tidak ada item berstatus approved yang perlu dikembalikan ke draft.');
            }

            $unapprovedCount = 0;
            $accountingService = app(\App\Services\AccountingService::class);

            foreach ($movements as $movement) {
                if ($movement->supplierPayments()->exists()) {
                    DB::rollBack();
                    return back()->withErrors(['error' => "Gagal: Movement #{$movement->reference_number} memiliki riwayat pembayaran supplier. Hapus pembayaran terlebih dahulu."]);
                }

                $product = Product::find($movement->product_id);
                if ($product) {
                    if ($product->stock < $movement->quantity) {
                        DB::rollBack();
                        return back()->withErrors(['error' => "Gagal: Stok produk {$product->name} saat ini ({$product->stock} unit) tidak mencukupi jika dikurangi {$movement->quantity} unit."]);
                    }
                    $product->revertStockInMovingAverage((float) $movement->quantity, $movement->purchase_price);
                }

                $movement->status = 'draft';
                $movement->save();

                // Purge stock movement journal
                $accountingService->purgeJournal('stock_movement', $movement->id);

                $unapprovedCount++;
            }

            DB::commit();

            return back()->with('success', "Berhasil mengembalikan {$unapprovedCount} item mutasi ke status DRAFT. Stok fisik dan HPP telah disesuaikan kembali, serta jurnal transaksi telah dibersihkan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal mengembalikan status mutasi: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $movement = StockMovement::findOrFail($id);

        if ($movement->type === 'in') {
            if (!Gate::allows('Stock In Destroy') && !Gate::allows('Movement Destroy')) {
                abort(403);
            }
        } elseif ($movement->type === 'out') {
            if (!Gate::allows('Stock Out Destroy') && !Gate::allows('Movement Destroy')) {
                abort(403);
            }
        } else {
            Gate::authorize('Movement Destroy');
        }

        try {
            DB::beginTransaction();

            $product = Product::findOrFail($movement->product_id);
            $refNumber = $movement->reference_number;

            // Revert product stock only if movement was approved (not draft)
            if ($movement->status !== 'draft') {
                if ($movement->type === 'in') {
                    // Stock In originally increased stock (+qty). Deleting it must decrease stock (-qty).
                    if ($product->stock < $movement->quantity) {
                        return back()->withErrors([
                            'error' => "Gagal menghapus: Stok produk {$product->name} saat ini ({$product->stock} unit) tidak mencukupi jika dikurangi {$movement->quantity} unit."
                        ]);
                    }
                    $product->stock -= $movement->quantity;
                } else {
                    // Stock Out originally decreased stock (-qty). Deleting it must add stock back (+qty).
                    $product->stock += $movement->quantity;
                }

                $product->save();
                $successMsg = "Movement log #{$refNumber} berhasil dihapus dan stok produk {$product->name} telah disesuaikan kembali.";
            } else {
                $successMsg = "Draft movement log #{$refNumber} ({$product->name}) berhasil dihapus.";
            }

            if ($movement->type === 'out') {
                $remainingMovements = StockMovement::where('reference_number', $refNumber)->where('id', '!=', $movement->id)->count();
                if ($remainingMovements === 0) {
                    $sale = Sale::withoutGlobalScopes()->where('reference_number', $refNumber)->first();
                    if ($sale) {
                        app(\App\Services\AccountingService::class)->purgeJournal('sale', $sale->id);
                        app(\App\Services\AccountingService::class)->purgeJournalByReference($refNumber);
                        $sale->items()->delete();
                        $sale->delete();
                    } else {
                        app(\App\Services\AccountingService::class)->purgeJournalByReference($refNumber);
                    }
                } else {
                    $sale = Sale::withoutGlobalScopes()->where('reference_number', $refNumber)->first();
                    if ($sale) {
                        $sale->items()->where('product_id', $movement->product_id)->delete();
                        $sale->total_amount = $sale->items()->sum('subtotal');
                        $sale->save();
                        app(\App\Services\AccountingService::class)->purgeJournal('sale', $sale->id);
                        app(\App\Services\AccountingService::class)->purgeJournalByReference($refNumber);
                        app(\App\Services\AccountingService::class)->recordSaleJournal($sale);
                    }
                }
            }

            // Purge stock movement journal if exists
            app(\App\Services\AccountingService::class)->purgeJournal('stock_movement', $movement->id);

            $movement->delete();

            DB::commit();

            return back()->with('success', $successMsg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus movement: ' . $e->getMessage()]);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $type = $request->input('type');

        if ($type === 'in') {
            if (!Gate::allows('Stock In Destroy') && !Gate::allows('Movement Destroy')) {
                abort(403);
            }
        } elseif ($type === 'out') {
            if (!Gate::allows('Stock Out Destroy') && !Gate::allows('Movement Destroy')) {
                abort(403);
            }
        } else {
            if (!Gate::allows('Movement Destroy') && !Gate::allows('Stock In Destroy') && !Gate::allows('Stock Out Destroy')) {
                abort(403);
            }
        }

        try {
            DB::beginTransaction();

            if ($request->boolean('delete_all') && $request->filled('movement_date_id')) {
                $movementDate = MovementDate::findOrFail($request->movement_date_id);
                $movements = StockMovement::where('movement_date', \Carbon\Carbon::parse($movementDate->date)->format('Y-m-d'))
                    ->where('type', $movementDate->type)
                    ->get();
            } else {
                $validated = $request->validate([
                    'ids' => 'required|array|min:1',
                    'ids.*' => 'integer|exists:stock_movements,id',
                ], [
                    'ids.required' => 'Pilih minimal satu movement log untuk dihapus.',
                ]);
                $movements = StockMovement::whereIn('id', $validated['ids'])->get();
            }

            $count = $movements->count();
            if ($count === 0) {
                return back()->with('error', 'Tidak ada movement log untuk dihapus.');
            }

            foreach ($movements as $movement) {
                $product = Product::find($movement->product_id);
                if ($product && $movement->status !== 'draft') {
                    if ($movement->type === 'in') {
                        $product->stock = max(0, $product->stock - $movement->quantity);
                    } else {
                        $product->stock += $movement->quantity;
                    }
                    $product->save();
                }
                if ($movement->type === 'out') {
                    $sale = Sale::withoutGlobalScopes()->where('reference_number', $movement->reference_number)->first();
                    if ($sale) {
                        app(\App\Services\AccountingService::class)->purgeJournal('sale', $sale->id);
                        $sale->items()->delete();
                        $sale->delete();
                    }
                    app(\App\Services\AccountingService::class)->purgeJournalByReference($movement->reference_number);
                }
                app(\App\Services\AccountingService::class)->purgeJournal('stock_movement', $movement->id);
                $movement->delete();
            }

            // Clean up corresponding sales for any deleted out movements
            $outRefNumbers = $movements->where('type', 'out')->pluck('reference_number')->unique();
            if ($outRefNumbers->isNotEmpty()) {
                foreach ($outRefNumbers as $refNum) {
                    if (!StockMovement::where('reference_number', $refNum)->exists()) {
                        $sale = Sale::withoutGlobalScopes()->where('reference_number', $refNum)->first();
                        if ($sale) {
                            $sale->items()->delete();
                            $sale->delete();
                        }
                    }
                }
            }

            DB::commit();

            return back()->with('success', "Berhasil menghapus {$count} movement log dan menyesuaikan stok.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus movement: ' . $e->getMessage()]);
        }
    }
}
