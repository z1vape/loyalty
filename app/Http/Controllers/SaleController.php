<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreSaleRequest;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('Sales List');

        $query = Sale::with(['user', 'items.product', 'store'])->orderBy('created_at', 'desc');

        // Filter: Search (Ref number or Customer Name)
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }

        // Filter: Date Range (use sale_date as primary transaction date, with created_at fallback)
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->toDateString();
            $end = Carbon::parse($endDate)->toDateString();
            if ($start > $end) {
                [$start, $end] = [$end, $start];
            }
            $query->whereBetween(DB::raw('DATE(COALESCE(sale_date, created_at))'), [
                $start,
                $end
            ]);
        } elseif ($startDate) {
            $query->where(DB::raw('DATE(COALESCE(sale_date, created_at))'), '>=', Carbon::parse($startDate)->toDateString());
        } elseif ($endDate) {
            $query->where(DB::raw('DATE(COALESCE(sale_date, created_at))'), '<=', Carbon::parse($endDate)->toDateString());
        }

        // Filter: Cashier / User
        if ($userId = $request->query('user_id')) {
            if ($userId !== 'all') {
                $query->where('user_id', $userId);
            }
        }

        // Filter: Payment Method
        $paymentMethod = $request->query('payment_method');
        if ($paymentMethod && $paymentMethod !== 'all') {
            if ($paymentMethod === 'split') {
                $query->where(function ($q) {
                    $q->where('payment_method', 'split')
                      ->orWhere('notes', 'LIKE', '%Bayar: SPLIT%');
                });
            } elseif ($paymentMethod === 'cash') {
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('payment_method', 'cash')
                            ->where('notes', 'NOT LIKE', '%Bayar: TRANSFER%')
                            ->where('notes', 'NOT LIKE', '%Bayar: QRIS%')
                            ->where('notes', 'NOT LIKE', '%Bayar: DEBIT%')
                            ->where('notes', 'NOT LIKE', '%Bayar: CREDIT%');
                    })->orWhere('notes', 'LIKE', '%Bayar: CASH%');
                });
            } elseif ($paymentMethod === 'non_cash') {
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereNotNull('payment_method')
                            ->where('payment_method', '!=', 'cash')
                            ->where('payment_method', '!=', 'split');
                    })->orWhere(function ($sub) {
                        $sub->where('notes', 'LIKE', '%Bayar: TRANSFER%')
                          ->orWhere('notes', 'LIKE', '%Bayar: QRIS%')
                          ->orWhere('notes', 'LIKE', '%Bayar: DEBIT%')
                          ->orWhere('notes', 'LIKE', '%Bayar: CREDIT%');
                    });
                });
            } else {
                $query->where(function ($q) use ($paymentMethod) {
                    $q->where('payment_method', strtolower($paymentMethod))
                      ->orWhere('notes', 'LIKE', '%Bayar: ' . strtoupper($paymentMethod) . '%');
                });
            }
        }

        // Compute metrics for the filtered set
        $summaryQuery = clone $query;
        $totalTransactions = $summaryQuery->count();
        $totalRevenue = (float) $summaryQuery->sum('total_amount');
        
        $saleIds = (clone $summaryQuery)->pluck('id');
        $totalItemsSold = 0;
        $totalDiscount = 0;
        if ($saleIds->isNotEmpty()) {
            $totalItemsSold = (int) SaleItem::whereIn('sale_id', $saleIds)->sum('quantity');
            $totalDiscount = (float) SaleItem::whereIn('sale_id', $saleIds)->sum('discount');
        }

        // Compute payment method breakdown for filtered sales set (excluding cancelled sales)
        $pmFilteredQuery = clone $summaryQuery;
        if (\Illuminate\Support\Facades\Schema::hasColumn('sales', 'status')) {
            $pmFilteredQuery->where(function($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });
        }

        $paymentMethodsRaw = (clone $pmFilteredQuery)
            ->select(
                DB::raw("CASE 
                    WHEN notes LIKE '%Bayar: QRIS%' THEN 'qris'
                    WHEN notes LIKE '%Bayar: TRANSFER%' THEN 'transfer'
                    WHEN notes LIKE '%Bayar: DEBIT%' THEN 'debit'
                    WHEN notes LIKE '%Bayar: CREDIT%' THEN 'credit'
                    WHEN payment_method IS NOT NULL AND TRIM(payment_method) != '' THEN LOWER(TRIM(payment_method))
                    ELSE 'cash'
                END as method"),
                DB::raw('COUNT(*) as total_count'),
                DB::raw('SUM(total_amount) as total_amount')
            )
            ->groupBy(DB::raw("CASE 
                WHEN notes LIKE '%Bayar: QRIS%' THEN 'qris'
                WHEN notes LIKE '%Bayar: TRANSFER%' THEN 'transfer'
                WHEN notes LIKE '%Bayar: DEBIT%' THEN 'debit'
                WHEN notes LIKE '%Bayar: CREDIT%' THEN 'credit'
                WHEN payment_method IS NOT NULL AND TRIM(payment_method) != '' THEN LOWER(TRIM(payment_method))
                ELSE 'cash'
            END"))
            ->get();

        $paymentBreakdown = [
            'cash' => [
                'key' => 'cash',
                'label' => 'Tunai (Cash)',
                'icon' => 'payments',
                'color' => 'emerald',
                'count' => 0,
                'amount' => 0.0,
            ],
            'transfer' => [
                'key' => 'transfer',
                'label' => 'Transfer Bank',
                'icon' => 'account_balance',
                'color' => 'blue',
                'count' => 0,
                'amount' => 0.0,
            ],
            'qris' => [
                'key' => 'qris',
                'label' => 'QRIS',
                'icon' => 'qr_code_scanner',
                'color' => 'purple',
                'count' => 0,
                'amount' => 0.0,
            ],
            'debit' => [
                'key' => 'debit',
                'label' => 'Kartu Debit',
                'icon' => 'credit_card',
                'color' => 'cyan',
                'count' => 0,
                'amount' => 0.0,
            ],
            'credit' => [
                'key' => 'credit',
                'label' => 'Kartu Kredit',
                'icon' => 'credit_score',
                'color' => 'amber',
                'count' => 0,
                'amount' => 0.0,
            ],
            'split' => [
                'key' => 'split',
                'label' => 'Split Payment',
                'icon' => 'call_split',
                'color' => 'purple',
                'count' => 0,
                'amount' => 0.0,
            ],
            'other' => [
                'key' => 'other',
                'label' => 'Lainnya',
                'icon' => 'more_horiz',
                'color' => 'gray',
                'count' => 0,
                'amount' => 0.0,
            ],
        ];

        foreach ($paymentMethodsRaw as $pmRow) {
            $mKey = strtolower(trim($pmRow->method));
            if (!isset($paymentBreakdown[$mKey])) {
                $paymentBreakdown[$mKey] = [
                    'key' => $mKey,
                    'label' => ucfirst($mKey),
                    'icon' => 'payments',
                    'color' => 'gray',
                    'count' => 0,
                    'amount' => 0.0,
                ];
            }
            $paymentBreakdown[$mKey]['count'] = (int) $pmRow->total_count;
            $paymentBreakdown[$mKey]['amount'] = (float) $pmRow->total_amount;
        }

        $sales = $query->paginate(15)->withQueryString();

        // Cashiers list for filter dropdown
        $cashiers = User::orderBy('name')->get();

        return view('sales.index', compact(
            'sales',
            'totalTransactions',
            'totalRevenue',
            'totalItemsSold',
            'totalDiscount',
            'paymentBreakdown',
            'cashiers',
            'search',
            'startDate',
            'endDate',
            'userId',
            'paymentMethod'
        ));
    }

    public function create()
    {
        Gate::authorize('Sales Create');

        $products = Product::where('stock', '>', 0)->orderBy('name')->get();
        $categoryPointRules = \App\Models\LoyaltyCategoryRule::where('is_active', true)->pluck('spend_per_point', 'category_name');
        return view('sales.create', compact('products', 'categoryPointRules'));
    }

    public function store(StoreSaleRequest $request)
    {
        Gate::authorize('Sales Create');

        $validated = $request->validate([
            'sale_date' => 'required|date',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Check stock sufficiency first
            foreach ($validated['items'] as $itemData) {
                $product = Product::whereKey($itemData['product_id'])->lockForUpdate()->firstOrFail();
                if ($product->stock < $itemData['quantity']) {
                    throw new \RuntimeException("Insufficient stock for {$product->name}. Only {$product->stock} units left.");
                }
            }

            // Generate unique reference number
            do {
                $refNumber = 'SALE-' . rand(10000, 99999);
            } while (Sale::where('reference_number', $refNumber)->exists());

            $paymentMethodValue = strtolower($request->input('payment_method', 'cash'));
            $paymentMethodUpper = strtoupper($paymentMethodValue);
            $initialNotes = $validated['notes'] ?? null;
            if (!empty($initialNotes)) {
                if (!preg_match('/Bayar:\s*[A-Za-z]+/i', $initialNotes)) {
                    $initialNotes .= " | Bayar: {$paymentMethodUpper}";
                }
            } else {
                $initialNotes = "Bayar: {$paymentMethodUpper}";
            }

            $customer = !empty($validated['customer_id']) ? \App\Models\Customer::find($validated['customer_id']) : null;
            $customerName = $customer ? $customer->name : ($validated['customer_name'] ?? null);
            $pointsRedeemed = (int) ($request->input('points_redeemed', 0));
            $pointsDiscount = (float) ($request->input('points_discount_amount', 0));

            // Load Category Point Rules & Customer Tier Multiplier
            $categoryRules = \App\Models\LoyaltyCategoryRule::where('is_active', true)->get()->keyBy(fn($r) => strtolower(trim($r->category_name)));
            $tierModel = $customer?->getTierModel();
            $tierMultiplier = $tierModel ? (float) $tierModel->point_multiplier : 1.0;
            $totalRawPointsEarned = 0;

            // Create Sale
            $sale = Sale::create([
                'reference_number' => $refNumber,
                'sale_date' => $validated['sale_date'],
                'customer_id' => $customer?->id,
                'customer_name' => $customerName,
                'total_amount' => $validated['total_amount'],
                'points_earned' => 0, // Will be updated after item category points calculated
                'points_redeemed' => $pointsRedeemed,
                'points_discount_amount' => $pointsDiscount,
                'liquid_stamps_earned' => 0, // Will be updated if liquid items exist
                'notes' => $initialNotes,
                'payment_method' => $paymentMethodValue,
                'user_id' => auth()->id(),
            ]);

            $saleDateFormatted = \Illuminate\Support\Carbon::parse($validated['sale_date'])->format('Y-m-d');

            // Ensure a MovementDate container exists for this date and type (Stock Out)
            \App\Models\MovementDate::firstOrCreate(
                [
                    'date' => $saleDateFormatted,
                    'type' => 'out',
                ],
                [
                    'created_by' => auth()->id(),
                    'notes' => 'Auto-created from Sale ' . $refNumber
                ]
            );

            // Create Sale Items and Stock Movements
            $liquidBottlesCount = 0;
            foreach ($validated['items'] as $itemData) {
                $product = Product::whereKey($itemData['product_id'])->lockForUpdate()->firstOrFail();
                
                // Track liquid items for stamp card
                $categoryName = strtolower($product->category ?? '');
                $productName = strtolower($product->name ?? '');
                if (str_contains($categoryName, 'liquid') || str_contains($productName, 'liquid')) {
                    $liquidBottlesCount += (int) $itemData['quantity'];
                }

                // Calculate Category Points for this item
                if ($customer) {
                    $catKey = strtolower(trim($product->category ?? ''));
                    $rule = $categoryRules->get($catKey);
                    $spendPerPoint = ($rule && $rule->spend_per_point > 0) ? $rule->spend_per_point : 10000;
                    if ($rule === null || $rule->is_active) {
                        $rawPointsForItem = floor((float) $itemData['subtotal'] / $spendPerPoint);
                        $totalRawPointsEarned += $rawPointsForItem;
                    }
                }

                // 1. Create Sale Item
                $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'selling_price' => $itemData['selling_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'subtotal' => $itemData['subtotal'],
                ]);

                // 2. Create StockMovement (Stock Out)
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => auth()->id(),
                    'reference_number' => $refNumber,
                    'movement_date' => $saleDateFormatted,
                    'type' => 'out',
                    'quantity' => $itemData['quantity'],
                    'purchase_price' => $product->purchase_price ?? 0,
                    'selling_price' => $itemData['selling_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'total_price' => $itemData['subtotal'],
                    'payment_method' => $paymentMethodValue,
                    'notes' => 'Sale transaction - ' . ($customerName ? 'Customer: ' . $customerName : 'Cash Sale'),
                ]);

                // 3. Deduct product stock ONCE
                $product->stock -= $itemData['quantity'];
                $product->save();
            }

            // Process Customer Loyalty Rewards & Points
            if ($customer) {
                $pointsEarned = (int) round($totalRawPointsEarned * $tierMultiplier);
                $sale->update(['points_earned' => $pointsEarned]);

                if ($pointsRedeemed > 0) {
                    $customer->redeemPoints($pointsRedeemed, $sale->id, "Tukar {$pointsRedeemed} Poin di Transaksi {$refNumber}");
                }
                if ($pointsEarned > 0) {
                    $customer->addPoints($pointsEarned, $sale->id, "Poin Belanja Transaksi {$refNumber} ({$customer->tier} {$tierMultiplier}x)");
                }
                if ($liquidBottlesCount > 0) {
                    $customer->addStamps($liquidBottlesCount, $sale->id, "Stempel {$liquidBottlesCount}x Liquid di Transaksi {$refNumber}");
                    $sale->update(['liquid_stamps_earned' => $liquidBottlesCount]);
                }
                $customer->updateTierAfterPurchase((float) $validated['total_amount']);
            }

            // Post double-entry accounting journal
            app(\App\Services\AccountingService::class)->recordSaleJournal($sale);

            DB::commit();

            return redirect()->route('sales.show', $sale->id)->with('success', "Sale {$refNumber} has been recorded successfully.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'An error occurred while processing the sale: ' . $e->getMessage()])->withInput();
        }
    }

    public function show(Request $request, Sale $sale)
    {
        Gate::authorize('Sales Show');

        $sale->load(['items.product', 'user', 'store']);

        if ($request->expectsJson() || $request->ajax()) {
            $totalGross = $sale->items->sum(fn($it) => $it->quantity * $it->selling_price);
            $totalDiscount = $sale->items->sum('discount');

            return response()->json([
                'status' => 'success',
                'sale' => [
                    'id' => $sale->id,
                    'reference_number' => $sale->reference_number,
                    'customer_name' => $sale->customer_name ?: 'Pelanggan Umum',
                    'sale_date' => $sale->sale_date ? $sale->sale_date->format('d M Y') : ($sale->created_at ? $sale->created_at->format('d M Y') : '-'),
                    'total_gross' => (float) $totalGross,
                    'total_gross_formatted' => number_format($totalGross, 0, ',', '.'),
                    'total_discount' => (float) $totalDiscount,
                    'total_discount_formatted' => number_format($totalDiscount, 0, ',', '.'),
                    'total_amount' => (float) $sale->total_amount,
                    'total_amount_formatted' => number_format($sale->total_amount, 0, ',', '.'),
                    'payment_method' => $sale->payment_method,
                    'payment_method_label' => $sale->payment_method_label,
                    'items' => $sale->items->map(function ($item) {
                        $gross = (float) $item->quantity * (float) $item->selling_price;
                        return [
                            'id' => $item->id,
                            'product_id' => $item->product_id,
                            'product_name' => $item->product?->name ?? 'Produk Dihapus',
                            'sku' => $item->product?->sku ?? '-',
                            'quantity' => (int) $item->quantity,
                            'selling_price' => (float) $item->selling_price,
                            'selling_price_formatted' => number_format($item->selling_price, 0, ',', '.'),
                            'gross_total' => $gross,
                            'gross_total_formatted' => number_format($gross, 0, ',', '.'),
                            'discount' => (float) $item->discount,
                            'discount_formatted' => number_format($item->discount, 0, ',', '.'),
                            'subtotal' => (float) $item->subtotal,
                            'subtotal_formatted' => number_format($item->subtotal, 0, ',', '.'),
                        ];
                    }),
                ]
            ]);
        }

        $cashiers = User::orderBy('name')->get();
        return view('sales.show', compact('sale', 'cashiers'));
    }

    /**
     * Update cashier for an existing sale transaction.
     */
    public function updateCashier(Request $request, Sale $sale)
    {
        if (!Gate::allows('Sales List') && !Gate::allows('Sales Show') && !Gate::allows('Sales Edit') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah kasir transaksi ini.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ], [
            'user_id.required' => 'Pilih kasir yang akan ditugaskan.',
            'user_id.exists' => 'Kasir yang dipilih tidak ditemukan dalam sistem.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        try {
            DB::beginTransaction();

            $newCashier = User::findOrFail($validated['user_id']);
            $newCashierName = $newCashier->name;

            // 1. Update user_id on sale
            $sale->user_id = $newCashier->id;

            // 2. Update notes if POS notes exist
            if (!empty($sale->notes)) {
                if (preg_match('/\[POS\s*-\s*[^\]]+\]/i', $sale->notes)) {
                    $sale->notes = preg_replace('/\[POS\s*-\s*[^\]]+\]/i', "[POS - {$newCashierName}]", $sale->notes);
                }
            }

            $sale->save();

            // 3. Update related StockMovement records for accurate staff performance reporting
            StockMovement::withoutGlobalScopes()
                ->where('reference_number', $sale->reference_number)
                ->update(['user_id' => $newCashier->id]);

            DB::commit();

            $message = "Kasir untuk transaksi #{$sale->reference_number} berhasil diubah menjadi {$newCashierName}.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'cashier' => [
                        'id' => $newCashier->id,
                        'name' => $newCashier->name,
                        'initial' => strtoupper(substr($newCashier->name, 0, 1)),
                        'email' => $newCashier->email,
                    ],
                    'notes' => $sale->notes,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengubah kasir: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Gagal mengubah kasir: ' . $e->getMessage()]);
        }
    }

    /**
     * Update payment method for an existing sale transaction.
     */
    public function updatePaymentMethod(Request $request, Sale $sale)
    {
        if (!Gate::allows('Sales List') && !Gate::allows('Sales Show') && !Gate::allows('Sales Edit') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah metode pembayaran transaksi ini.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'payment_method' => 'required|string|in:cash,qris,transfer,debit,credit,split,other',
            'payment_splits' => 'nullable|array',
            'payment_splits.*.method' => 'required_with:payment_splits|string|in:cash,qris,transfer,debit,credit',
            'payment_splits.*.amount' => 'required_with:payment_splits|numeric|min:0',
        ], [
            'payment_method.required' => 'Pilih metode pembayaran.',
            'payment_method.in' => 'Metode pembayaran tidak valid.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $newMethod = strtolower($request->input('payment_method'));
        $newMethodUpper = strtoupper($newMethod);

        try {
            DB::beginTransaction();

            // 1. Update payment_method on sale
            $sale->payment_method = $newMethod;
            $splitDetailsStr = '';

            if ($newMethod === 'split' && $request->has('payment_splits') && is_array($request->input('payment_splits'))) {
                $sale->payment_splits = $request->input('payment_splits');

                $methodNames = [
                    'cash' => 'Tunai',
                    'qris' => 'QRIS',
                    'transfer' => 'Transfer',
                    'debit' => 'Debit',
                    'credit' => 'Kredit'
                ];
                $splitParts = [];
                foreach ($sale->payment_splits as $sp) {
                    $mKey = strtolower($sp['method'] ?? 'cash');
                    $mLabel = $methodNames[$mKey] ?? strtoupper($mKey);
                    $amtFormatted = number_format($sp['amount'] ?? 0, 0, ',', '.');
                    $splitParts[] = "{$mLabel}: Rp{$amtFormatted}";
                }
                $splitDetailsStr = implode(' + ', $splitParts);

                if (!empty($sale->notes)) {
                    if (preg_match('/Bayar:\s*[^|\(\)]+/i', $sale->notes)) {
                        $sale->notes = preg_replace('/Bayar:\s*[^|\(\)]+/i', "Bayar: SPLIT [{$splitDetailsStr}]", $sale->notes);
                    } else {
                        $sale->notes = $sale->notes . " | Bayar: SPLIT [{$splitDetailsStr}]";
                    }
                } else {
                    $sale->notes = "Bayar: SPLIT [{$splitDetailsStr}]";
                }
            } else {
                if ($newMethod !== 'split') {
                    $sale->payment_splits = null;
                }

                if (!empty($sale->notes)) {
                    if (preg_match('/Bayar:\s*[A-Za-z0-9_\-\/]+/i', $sale->notes)) {
                        $sale->notes = preg_replace('/Bayar:\s*[A-Za-z0-9_\-\/]+/i', "Bayar: {$newMethodUpper}", $sale->notes);
                    } else {
                        $sale->notes = $sale->notes . " | Bayar: {$newMethodUpper}";
                    }
                } else {
                    $sale->notes = "Bayar: {$newMethodUpper}";
                }
            }

            $sale->save();

            // 3. Update related StockMovement records so Stock Out & Stock Cards reflect the new payment method and details
            $movementsToUpdate = StockMovement::withoutGlobalScopes()
                ->where('reference_number', $sale->reference_number)
                ->get();

            foreach ($movementsToUpdate as $sm) {
                $sm->payment_method = $newMethod;
                if ($newMethod === 'split' && !empty($splitDetailsStr)) {
                    if (!empty($sm->notes)) {
                        if (preg_match('/Bayar:\s*[^|\(\)]+/i', $sm->notes)) {
                            $sm->notes = preg_replace('/Bayar:\s*[^|\(\)]+/i', "Bayar: SPLIT [{$splitDetailsStr}]", $sm->notes);
                        } else {
                            $sm->notes = $sm->notes . " | Bayar: SPLIT [{$splitDetailsStr}]";
                        }
                    } else {
                        $sm->notes = "Bayar: SPLIT [{$splitDetailsStr}]";
                    }
                } else {
                    if (!empty($sm->notes)) {
                        if (preg_match('/Bayar:\s*[A-Za-z0-9_\-\/]+/i', $sm->notes)) {
                            $sm->notes = preg_replace('/Bayar:\s*[A-Za-z0-9_\-\/]+/i', "Bayar: {$newMethodUpper}", $sm->notes);
                        }
                    }
                }
                $sm->save();
            }

            // 4. Synchronize accounting journal entry
            app(\App\Services\AccountingService::class)->recordSaleJournal($sale);

            DB::commit();

            $methodLabel = $sale->payment_method_label;
            $badgeClass = $sale->payment_badge_class;
            $message = "Metode pembayaran untuk transaksi #{$sale->reference_number} berhasil diubah menjadi {$methodLabel}.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'payment_method' => $newMethod,
                    'payment_method_label' => $methodLabel,
                    'badge_class' => $badgeClass,
                    'notes' => $sale->notes,
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengubah metode pembayaran: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Gagal mengubah metode pembayaran: ' . $e->getMessage()]);
        }
    }

    /**
     * Update discount for sale items in an existing sale transaction.
     */
    public function updateDiscount(Request $request, Sale $sale)
    {
        if (!Gate::allows('Sales List') && !Gate::allows('Sales Show') && !Gate::allows('Sales Edit') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengubah diskon transaksi ini.');
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.discount' => 'required|numeric|min:0',
        ], [
            'items.required' => 'Daftar item produk wajib ada.',
            'items.*.discount.min' => 'Diskon tidak boleh bernilai negatif.',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            $sale->load('items');
            $saleItemsById = $sale->items->keyBy('id');
            $totalNewDiscount = 0;
            $totalNewAmount = 0;

            foreach ($request->input('items') as $itemInput) {
                $itemId = $itemInput['id'];
                if (!$saleItemsById->has($itemId)) {
                    continue;
                }

                $saleItem = $saleItemsById->get($itemId);
                $grossItem = (float) $saleItem->quantity * (float) $saleItem->selling_price;
                $newDiscount = min((float) $itemInput['discount'], $grossItem);
                $newSubtotal = max(0, $grossItem - $newDiscount);

                $saleItem->discount = $newDiscount;
                $saleItem->subtotal = $newSubtotal;
                $saleItem->save();

                // Synchronize corresponding StockMovement
                StockMovement::withoutGlobalScopes()
                    ->where('reference_number', $sale->reference_number)
                    ->where('product_id', $saleItem->product_id)
                    ->update([
                        'discount' => $newDiscount,
                        'total_price' => $newSubtotal,
                    ]);

                $totalNewDiscount += $newDiscount;
                $totalNewAmount += $newSubtotal;
            }

            // Recalculate sale total amount from items
            $sale->total_amount = $sale->items()->sum('subtotal');
            $sale->save();

            // Synchronize accounting journal entry
            app(\App\Services\AccountingService::class)->recordSaleJournal($sale);

            DB::commit();

            $sale->load(['items.product', 'user', 'store']);

            $message = "Diskon untuk transaksi #{$sale->reference_number} berhasil diperbarui.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'success',
                    'message' => $message,
                    'total_amount' => (float) $sale->total_amount,
                    'total_amount_formatted' => number_format($sale->total_amount, 0, ',', '.'),
                    'total_discount' => (float) $totalNewDiscount,
                    'total_discount_formatted' => number_format($totalNewDiscount, 0, ',', '.'),
                    'items' => $sale->items->map(function ($it) {
                        return [
                            'id' => $it->id,
                            'product_id' => $it->product_id,
                            'product_name' => $it->product?->name ?? 'Produk',
                            'quantity' => (int) $it->quantity,
                            'selling_price' => (float) $it->selling_price,
                            'discount' => (float) $it->discount,
                            'subtotal' => (float) $it->subtotal,
                            'discount_formatted' => number_format($it->discount, 0, ',', '.'),
                            'subtotal_formatted' => number_format($it->subtotal, 0, ',', '.'),
                        ];
                    }),
                ]);
            }

            return back()->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengubah diskon: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors(['error' => 'Gagal mengubah diskon: ' . $e->getMessage()]);
        }
    }

    /**
     * Standard update method forwarding to updateDiscount, updatePaymentMethod or updateCashier.
     */
    public function update(Request $request, Sale $sale)
    {
        if ($request->has('items') || $request->has('discount')) {
            return $this->updateDiscount($request, $sale);
        }
        if ($request->has('payment_method')) {
            return $this->updatePaymentMethod($request, $sale);
        }
        return $this->updateCashier($request, $sale);
    }

    public function destroy(Sale $sale)
    {
        if (!Gate::allows('Sales Destroy') && !Gate::allows('Movement Destroy') && !auth()->user()->hasRole('Super Admin')) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $sale->load('items.product');
            $refNumber = $sale->reference_number;

            // Revert product stocks
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $item->product->stock += $item->quantity;
                    $item->product->save();
                }
            }

            // Delete corresponding StockMovement records
            StockMovement::where('reference_number', $refNumber)->delete();

            // Purge accounting journal
            app(\App\Services\AccountingService::class)->purgeJournal('sale', $sale->id);

            // Delete items & sale
            $sale->items()->delete();
            $sale->delete();

            DB::commit();

            return redirect()->route('sales.index')->with('success', "Transaksi penjualan #{$refNumber} berhasil dihapus dan stok barang dikembalikan.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus transaksi penjualan: ' . $e->getMessage()]);
        }
    }

    public function bulkDestroy(Request $request)
    {
        if (!Gate::allows('Sales Destroy') && !Gate::allows('Movement Destroy') && !auth()->user()->hasRole('Super Admin')) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            if ($request->boolean('delete_all')) {
                $sales = Sale::with('items.product')->get();
            } else {
                $validated = $request->validate([
                    'ids' => 'required|array|min:1',
                    'ids.*' => 'integer|exists:sales,id',
                ], [
                    'ids.required' => 'Pilih minimal satu transaksi penjualan untuk dihapus.',
                ]);
                $sales = Sale::with('items.product')->whereIn('id', $validated['ids'])->get();
            }

            $count = $sales->count();
            if ($count === 0) {
                return back()->with('error', 'Tidak ada transaksi penjualan untuk dihapus.');
            }

            foreach ($sales as $sale) {
                $refNumber = $sale->reference_number;

                // Revert stocks
                foreach ($sale->items as $item) {
                    if ($item->product) {
                        $item->product->stock += $item->quantity;
                        $item->product->save();
                    }
                }

                // Delete movements
                StockMovement::where('reference_number', $refNumber)->delete();

                // Purge accounting journal
                app(\App\Services\AccountingService::class)->purgeJournal('sale', $sale->id);

                // Delete items & sale
                $sale->items()->delete();
                $sale->delete();
            }

            DB::commit();

            return redirect()->route('sales.index')->with('success', "Berhasil menghapus {$count} transaksi penjualan dan mengembalikan stok barang.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus transaksi: ' . $e->getMessage()]);
        }
    }
}
