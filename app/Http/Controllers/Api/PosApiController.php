<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\MovementDate;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shift;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class PosApiController extends Controller
{
    /**
     * Get list of active stores for POS store selection.
     */
    public function stores(): JsonResponse
    {
        $stores = Store::orderBy('name')->get(['id', 'name', 'address', 'phone']);

        return response()->json([
            'status' => 'success',
            'data' => $stores
        ]);
    }

    /**
     * Get list of cashiers/users.
     */
    public function cashiers(Request $request): JsonResponse
    {
        $storeId = $request->query('store_id');
        $query = User::select('id', 'name', 'email', 'position', 'photo', 'employee_number')
            ->with(['stores', 'roles:id,name']);

        if ($storeId) {
            $query->whereHas('stores', function ($q) use ($storeId) {
                $q->where('stores.id', $storeId);
            })->orWhereHas('roles', function ($q) {
                $q->where('name', 'Super Admin');
            });
        }

        $users = $query->orderBy('name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Authenticate cashier/staff for POS & Absensi based on store, user, and password.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_id'  => 'required|exists:users,id',
            'password' => 'required|string',
        ], [
            'store_id.required' => 'Cabang toko wajib dipilih.',
            'store_id.exists'   => 'Cabang toko tidak valid.',
            'user_id.required'  => 'Karyawan/Kasir wajib dipilih.',
            'user_id.exists'    => 'Karyawan tidak ditemukan.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $store = Store::find($validated['store_id']);
        $user = User::with('roles')->find($validated['user_id']);

        // Check if user is assigned to this store or is Super Admin
        $isAssigned = $user->stores()->where('stores.id', $store->id)->exists();
        $isSuperAdmin = $user->hasRole('Super Admin');

        if (!$isAssigned && !$isSuperAdmin) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Karyawan ini tidak terdaftar pada cabang ' . $store->name . '.',
            ], 403);
        }

        // Verify password
        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Password yang Anda masukkan salah.',
            ], 401);
        }

        $assignedStores = $isSuperAdmin
            ? Store::orderBy('name')->get(['id', 'name', 'address', 'phone'])
            : $user->stores()->select('stores.id', 'stores.name', 'stores.address', 'stores.phone')->orderBy('name')->get();

        return response()->json([
            'status'  => 'success',
            'message' => 'Login berhasil. Selamat bertugas!',
            'data'    => [
                'user' => [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'position'        => $user->position,
                    'photo'           => $user->photo,
                    'roles'           => $user->roles->pluck('name'),
                    'employee_number' => $user->employee_number,
                    'assigned_stores' => $assignedStores,
                ],
                'store' => [
                    'id'      => $store->id,
                    'name'    => $store->name,
                    'address' => $store->address,
                    'phone'   => $store->phone,
                ]
            ]
        ]);
    }

    /**
     * Get products for POS catalog with category filter and search.
     */
    public function products(Request $request): JsonResponse
    {
        $storeId = $request->query('store_id');
        $category = $request->query('category');
        $search = $request->query('search');

        $query = Product::withoutGlobalScopes();

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('variant', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('name', 'asc')->get();

        // Distinct categories for filter pills
        $categoriesQuery = Product::withoutGlobalScopes()
            ->whereNotNull('category')
            ->where('category', '!=', '');

        if ($storeId) {
            $categoriesQuery->where('store_id', $storeId);
        }

        $categories = $categoriesQuery->distinct()->pluck('category')->values();

        return response()->json([
            'status' => 'success',
            'count' => $products->count(),
            'categories' => $categories,
            'data' => $products
        ]);
    }

    /**
     * Process checkout transaction.
     */
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_id' => 'nullable|exists:users,id',
            'cashier_name' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:150',
            'payment_method' => 'required|string|in:cash,qris,transfer,debit,credit,split',
            'amount_paid' => 'required|numeric|min:0',
            'change_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'payment_splits' => 'nullable|array',
            'payment_splits.*.method' => 'required_with:payment_splits|string|in:cash,qris,transfer,debit,credit',
            'payment_splits.*.amount' => 'required_with:payment_splits|numeric|min:0',
            'payment_splits.*.amount_paid' => 'nullable|numeric|min:0',
            'payment_splits.*.change_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.discount' => 'nullable|numeric|min:0',
            'items.*.subtotal' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
        ]);

        // Validate split payment integrity
        if ($validated['payment_method'] === 'split') {
            if (empty($validated['payment_splits']) || count($validated['payment_splits']) < 2) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pembayaran split memerlukan minimal 2 rincian metode pembayaran.'
                ], 422);
            }

            $splitSum = 0;
            foreach ($validated['payment_splits'] as $sp) {
                $splitSum += (float) ($sp['amount'] ?? 0);
            }

            if (abs($splitSum - (float) $validated['total_amount']) > 1) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Total nominal rincian split (Rp ' . number_format($splitSum, 0, ',', '.') . ') tidak sama dengan total belanja (Rp ' . number_format($validated['total_amount'], 0, ',', '.') . ').'
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $storeId = $validated['store_id'];
            $store = Store::findOrFail($storeId);

            // 1. Check stock availability for all items
            foreach ($validated['items'] as $itemData) {
                $product = Product::withoutGlobalScopes()->where('id', $itemData['product_id'])->lockForUpdate()->firstOrFail();
                if ($product->stock < $itemData['quantity']) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => "Stok produk '{$product->name}' tidak mencukupi! Tersisa: {$product->stock} unit, diminta: {$itemData['quantity']} unit."
                    ], 422);
                }
            }

            // 2. Generate Reference Number
            $datePrefix = Carbon::now()->format('ymd');
            do {
                $refNumber = 'POS-' . $datePrefix . '-' . rand(1000, 9999);
            } while (Sale::withoutGlobalScopes()->where('reference_number', $refNumber)->exists());

            // Build detailed note for tracking
            $cashierName = $validated['cashier_name'] ?? 'Kasir';
            $customNote = !empty($validated['notes']) ? " | Catatan: " . $validated['notes'] : '';

            if ($validated['payment_method'] === 'split' && !empty($validated['payment_splits'])) {
                $methodNames = [
                    'cash' => 'Tunai',
                    'qris' => 'QRIS',
                    'transfer' => 'Transfer',
                    'debit' => 'Debit',
                    'credit' => 'Kredit'
                ];
                $splitParts = [];
                $totalSplitPaid = 0;
                $totalSplitChange = 0;
                foreach ($validated['payment_splits'] as $sp) {
                    $mKey = strtolower($sp['method'] ?? 'cash');
                    $mLabel = $methodNames[$mKey] ?? strtoupper($mKey);
                    $amtFormatted = number_format($sp['amount'], 0, ',', '.');
                    $spPaid = (float) ($sp['amount_paid'] ?? $sp['amount']);
                    $spChange = (float) ($sp['change_amount'] ?? 0);
                    $totalSplitPaid += $spPaid;
                    $totalSplitChange += $spChange;

                    if ($mKey === 'cash' && ($spPaid > $sp['amount'] || $spChange > 0)) {
                        $splitParts[] = "{$mLabel}: Rp{$amtFormatted} (Uang: Rp" . number_format($spPaid, 0, ',', '.') . ", Kembali: Rp" . number_format($spChange, 0, ',', '.') . ")";
                    } else {
                        $splitParts[] = "{$mLabel}: Rp{$amtFormatted}";
                    }
                }
                $paymentMethodLabel = 'SPLIT';
                $paidFormatted = number_format($validated['amount_paid'] ?? $totalSplitPaid, 0, ',', '.');
                $changeFormatted = number_format($validated['change_amount'] ?? $totalSplitChange, 0, ',', '.');
                $splitDetailsStr = implode(' + ', $splitParts);
                $saleNote = "[POS - {$cashierName}] Bayar: SPLIT [{$splitDetailsStr}] (Uang: Rp{$paidFormatted}, Kembali: Rp{$changeFormatted}){$customNote}";
            } else {
                $paymentMethodLabel = strtoupper($validated['payment_method']);
                $paidFormatted = number_format($validated['amount_paid'], 0, ',', '.');
                $changeFormatted = number_format($validated['change_amount'] ?? 0, 0, ',', '.');
                $saleNote = "[POS - {$cashierName}] Bayar: {$paymentMethodLabel} (Uang: Rp{$paidFormatted}, Kembali: Rp{$changeFormatted}){$customNote}";
            }

            // 3. Create Sale
            $sale = Sale::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'reference_number' => $refNumber,
                'sale_date' => Carbon::now()->toDateString(),
                'customer_name' => !empty($validated['customer_name']) ? $validated['customer_name'] : 'Pelanggan Umum',
                'total_amount' => $validated['total_amount'],
                'notes' => $saleNote,
                'payment_method' => strtolower($validated['payment_method'] ?? 'cash'),
                'payment_splits' => $validated['payment_splits'] ?? null,
                'user_id' => $validated['user_id'] ?? 1,
            ]);

            $today = Carbon::now()->toDateString();

            // 4. Ensure MovementDate container exists
            MovementDate::withoutGlobalScopes()->firstOrCreate(
                [
                    'date' => $today,
                    'store_id' => $storeId,
                    'type' => 'out',
                ],
                [
                    'created_by' => $validated['user_id'] ?? 1,
                    'notes' => 'Otomatis dari Transaksi POS ' . $refNumber,
                ]
            );

            // 5. Create Sale Items, Deduct Stock, and Record StockMovement
            $receiptItems = [];
            foreach ($validated['items'] as $itemData) {
                $product = Product::withoutGlobalScopes()->findOrFail($itemData['product_id']);

                // Create Sale Item
                $saleItem = $sale->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'selling_price' => $itemData['selling_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'subtotal' => $itemData['subtotal'],
                ]);

                // Create Stock Movement (Out)
                StockMovement::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'product_id' => $product->id,
                    'user_id' => $validated['user_id'] ?? 1,
                    'reference_number' => $refNumber,
                    'movement_date' => $today,
                    'type' => 'out',
                    'quantity' => $itemData['quantity'],
                    'purchase_price' => $product->purchase_price ?? 0,
                    'selling_price' => $itemData['selling_price'],
                    'discount' => $itemData['discount'] ?? 0,
                    'total_price' => $itemData['subtotal'],
                    'payment_method' => $validated['payment_method'] ?? 'cash',
                    'notes' => "POS Penjualan - {$refNumber} ({$sale->customer_name})",
                ]);

                // Deduct Product Stock
                $product->decrement('stock', $itemData['quantity']);

                $receiptItems[] = [
                    'product_name' => $product->name,
                    'variant' => $product->variant,
                    'sku' => $product->sku,
                    'quantity' => $itemData['quantity'],
                    'selling_price' => (float) $itemData['selling_price'],
                    'discount' => (float) ($itemData['discount'] ?? 0),
                    'subtotal' => (float) $itemData['subtotal'],
                ];
            }

            // Post double-entry accounting journal
            app(\App\Services\AccountingService::class)->recordSaleJournal($sale);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi POS berhasil diselesaikan!',
                'data' => [
                    'sale_id' => $sale->id,
                    'reference_number' => $refNumber,
                    'date_time' => Carbon::now()->format('d/m/Y H:i:s'),
                    'store_name' => $store->name,
                    'store_address' => $store->address,
                    'store_phone' => $store->phone,
                    'cashier_name' => $cashierName,
                    'customer_name' => $sale->customer_name,
                    'payment_method' => $paymentMethodLabel,
                    'payment_splits' => $sale->payment_splits,
                    'total_amount' => (float) $validated['total_amount'],
                    'amount_paid' => (float) $validated['amount_paid'],
                    'change_amount' => (float) ($validated['change_amount'] ?? 0),
                    'items' => $receiptItems,
                    'notes' => $validated['notes'] ?? '',
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses transaksi: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply recap filters to a Sale query based on request parameters.
     */
    private function applyRecapFilters($query, Request $request): void
    {
        // Store filter
        if ($storeId = $request->query('store_id')) {
            $query->where('store_id', $storeId);
        }

        // Cashier (user_id) filter
        $userId = $request->query('user_id');
        if ($userId && $userId !== 'all') {
            $query->where('user_id', $userId);
        }

        // Period & Date filters
        $customDate = $request->query('date');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($request->has('period')) {
            $period = $request->query('period');
            if ($period === 'today') {
                $query->whereDate('created_at', Carbon::today());
            } elseif ($period === 'yesterday') {
                $query->whereDate('created_at', Carbon::yesterday());
            } elseif ($period === 'last_7_days') {
                $query->whereBetween('created_at', [
                    Carbon::today()->subDays(6)->startOfDay(),
                    Carbon::now()->endOfDay()
                ]);
            } elseif ($period === 'this_month') {
                $query->whereBetween('created_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ]);
            } elseif ($period === 'custom') {
                if ($dateFrom && $dateTo) {
                    $query->whereBetween('created_at', [
                        Carbon::parse($dateFrom)->startOfDay(),
                        Carbon::parse($dateTo)->endOfDay()
                    ]);
                } elseif ($customDate) {
                    $query->whereDate('created_at', Carbon::parse($customDate)->toDateString());
                }
            }
            // If period === 'all', do not filter by date
        } elseif ($customDate) {
            $query->whereDate('created_at', Carbon::parse($customDate)->toDateString());
        }

        // Payment method filter (checked against payment_method and notes)
        $paymentMethod = $request->query('payment_method');
        if ($paymentMethod && $paymentMethod !== 'all') {
            if ($paymentMethod === 'cash') {
                $query->where(function ($q) {
                    $q->where('payment_method', 'cash')
                      ->orWhere('notes', 'LIKE', '%Bayar: CASH%');
                });
            } elseif ($paymentMethod === 'split') {
                $query->where(function ($q) {
                    $q->where('payment_method', 'split')
                      ->orWhere('notes', 'LIKE', '%Bayar: SPLIT%');
                });
            } elseif ($paymentMethod === 'non_cash') {
                $query->where(function ($q) {
                    $q->whereNotIn('payment_method', ['cash', 'split'])
                      ->orWhere(function ($sub) {
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

        // Status filter: 'all' | 'completed' | 'cancelled'
        $statusFilter = $request->query('status');
        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'cancelled') {
                $query->where('status', 'cancelled');
            } elseif ($statusFilter === 'completed') {
                $query->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
                });
            }
        }

        // Search reference_number or customer_name
        $search = $request->query('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('customer_name', 'LIKE', "%{$search}%");
            });
        }
    }

    /**
     * Get recent sales for the store shift recap with optional filtering.
     */
    public function recentSales(Request $request): JsonResponse
    {
        $limit = min((int) ($request->query('limit', 50)), 150);

        $query = Sale::withoutGlobalScopes()
            ->with(['items.product', 'user'])
            ->orderBy('created_at', 'desc');

        $this->applyRecapFilters($query, $request);

        $sales = $query->take($limit)->get();

        return response()->json([
            'status' => 'success',
            'count' => $sales->count(),
            'data' => $sales
        ]);
    }

    /**
     * Get summary for cashier shift recap with period and cashier filtering.
     */
    public function todaySummary(Request $request): JsonResponse
    {
        $query = Sale::withoutGlobalScopes();
        
        // If neither period nor date is provided, default to today
        if (!$request->has('period') && !$request->has('date') && !$request->has('date_from')) {
            $request->merge(['period' => 'today']);
        }

        $this->applyRecapFilters($query, $request);

        // Only active (non-cancelled) sales count towards revenue and cash drawer
        $activeQuery = (clone $query)->where(function ($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
        });

        $totalSales = (clone $activeQuery)->count();
        $totalRevenue = (float) (clone $activeQuery)->sum('total_amount');
        
        // Calculate cash vs non-cash revenue accurately, including cash portion from split payments
        $activeSales = (clone $activeQuery)->get(['id', 'total_amount', 'payment_method', 'payment_splits', 'notes']);
        $cashRevenue = 0;
        foreach ($activeSales as $saleItem) {
            $pm = strtolower($saleItem->payment_method ?? 'cash');
            if ($pm === 'cash') {
                $cashRevenue += (float) $saleItem->total_amount;
            } elseif ($pm === 'split') {
                if (!empty($saleItem->payment_splits) && is_array($saleItem->payment_splits)) {
                    foreach ($saleItem->payment_splits as $sp) {
                        if (strtolower($sp['method'] ?? '') === 'cash') {
                            $cashRevenue += (float) ($sp['amount'] ?? 0);
                        }
                    }
                } elseif (preg_match('/Tunai:\s*Rp([\d\.]+)/i', $saleItem->notes ?? '', $m)) {
                    $cashRevenue += (float) str_replace('.', '', $m[1]);
                }
            }
        }
        $nonCashRevenue = max(0, $totalRevenue - $cashRevenue);

        // Average order value
        $avgTransaction = $totalSales > 0 ? round($totalRevenue / $totalSales, 0) : 0;

        // Total items sold (active only)
        $saleIds = (clone $activeQuery)->pluck('id');
        $totalItemsSold = 0;
        if ($saleIds->isNotEmpty()) {
            $totalItemsSold = (int) SaleItem::whereIn('sale_id', $saleIds)->sum('quantity');
        }

        // Cancelled transactions info for transparency
        $cancelledCount = (clone $query)->where('status', 'cancelled')->count();
        $cancelledRevenue = (float) (clone $query)->where('status', 'cancelled')->sum('total_amount');

        // Human-readable period label
        $period = $request->query('period', 'today');
        $periodLabel = 'Hari Ini (' . Carbon::now()->format('d M Y') . ')';
        if ($period === 'yesterday') {
            $periodLabel = 'Kemarin (' . Carbon::yesterday()->format('d M Y') . ')';
        } elseif ($period === 'last_7_days') {
            $periodLabel = '7 Hari Terakhir (' . Carbon::today()->subDays(6)->format('d M') . ' - ' . Carbon::now()->format('d M Y') . ')';
        } elseif ($period === 'this_month') {
            $periodLabel = 'Bulan Ini (' . Carbon::now()->format('F Y') . ')';
        } elseif ($period === 'all') {
            $periodLabel = 'Semua Riwayat Transaksi';
        } elseif ($period === 'custom') {
            $cDate = $request->query('date');
            $dFrom = $request->query('date_from');
            $dTo = $request->query('date_to');
            if ($dFrom && $dTo) {
                $periodLabel = Carbon::parse($dFrom)->format('d M Y') . ' s/d ' . Carbon::parse($dTo)->format('d M Y');
            } elseif ($cDate) {
                $periodLabel = Carbon::parse($cDate)->format('d M Y');
            }
        }

        // Calculate store balance if store_id is present
        $storeBalanceData = null;
        $storeIdParam = $request->query('store_id');
        if ($storeIdParam) {
            $storeObj = Store::find($storeIdParam);
            if ($storeObj) {
                $startDate = $storeObj->initial_balance_date ? Carbon::parse($storeObj->initial_balance_date)->toDateString() : ($storeObj->created_at ? Carbon::parse($storeObj->created_at)->toDateString() : '2000-01-01');

                $allSalesQuery = Sale::withoutGlobalScopes()
                    ->where('store_id', $storeIdParam)
                    ->whereDate('sale_date', '>=', $startDate)
                    ->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
                    });

                $allSalesForBalance = (clone $allSalesQuery)->get(['id', 'total_amount', 'payment_method', 'payment_splits', 'notes']);
                $cumTotalSales = (float) $allSalesForBalance->sum('total_amount');
                $cumCashSales = 0;
                foreach ($allSalesForBalance as $sObj) {
                    $pm = strtolower($sObj->payment_method ?? 'cash');
                    if ($pm === 'cash') {
                        $cumCashSales += (float) $sObj->total_amount;
                    } elseif ($pm === 'split') {
                        if (!empty($sObj->payment_splits) && is_array($sObj->payment_splits)) {
                            foreach ($sObj->payment_splits as $sp) {
                                if (strtolower($sp['method'] ?? '') === 'cash') {
                                    $cumCashSales += (float) ($sp['amount'] ?? 0);
                                }
                            }
                        } elseif (preg_match('/Tunai:\s*Rp([\d\.]+)/i', $sObj->notes ?? '', $m)) {
                            $cumCashSales += (float) str_replace('.', '', $m[1]);
                        }
                    }
                }
                $cumNonCashSales = max(0, $cumTotalSales - $cumCashSales);

                $cumExpenses = (float) $storeObj->expenses()->whereDate('expense_date', '>=', $startDate)->sum('amount');
                $initBal = (float) ($storeObj->initial_balance ?? 0);
                $endBal = (float) $storeObj->ending_balance;

                $storeBalanceData = [
                    'store_id' => $storeObj->id,
                    'store_name' => $storeObj->name,
                    'bank_name' => $storeObj->bank_name,
                    'bank_account_number' => $storeObj->bank_account_number,
                    'bank_account_name' => $storeObj->bank_account_name,
                    'initial_balance' => $initBal,
                    'initial_balance_date' => $startDate,
                    'cumulative_sales' => $cumTotalSales,
                    'cumulative_cash_sales' => $cumCashSales,
                    'cumulative_non_cash_sales' => $cumNonCashSales,
                    'cumulative_expenses' => $cumExpenses,
                    'ending_balance' => $endBal,
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'date' => Carbon::now()->format('d M Y'),
                'period_label' => $periodLabel,
                'total_transactions' => $totalSales,
                'total_revenue' => $totalRevenue,
                'cash_revenue' => $cashRevenue,
                'non_cash_revenue' => $nonCashRevenue,
                'avg_transaction' => $avgTransaction,
                'total_items_sold' => $totalItemsSold,
                'cancelled_transactions' => $cancelledCount,
                'cancelled_revenue' => $cancelledRevenue,
                'store_balance' => $storeBalanceData,
            ]
        ]);
    }

    /**
     * Get real-time store balance including initial balance, cumulative sales, expenses, and ending balance.
     */
    public function storeBalance(Request $request): JsonResponse
    {
        $storeId = $request->query('store_id');
        if (!$storeId) {
            return response()->json([
                'status' => 'error',
                'message' => 'store_id parameter is required'
            ], 400);
        }

        $storeObj = Store::find($storeId);
        if (!$storeObj) {
            return response()->json([
                'status' => 'error',
                'message' => 'Toko tidak ditemukan'
            ], 404);
        }

        $startDate = $storeObj->initial_balance_date ? Carbon::parse($storeObj->initial_balance_date)->toDateString() : ($storeObj->created_at ? Carbon::parse($storeObj->created_at)->toDateString() : '2000-01-01');

        $allSalesQuery = Sale::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereDate('sale_date', '>=', $startDate)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });

        $allSalesForBalance = (clone $allSalesQuery)->get(['id', 'total_amount', 'payment_method', 'payment_splits', 'notes']);
        $cumTotalSales = (float) $allSalesForBalance->sum('total_amount');
        $cumCashSales = 0;
        foreach ($allSalesForBalance as $sObj) {
            $pm = strtolower($sObj->payment_method ?? 'cash');
            if ($pm === 'cash') {
                $cumCashSales += (float) $sObj->total_amount;
            } elseif ($pm === 'split') {
                if (!empty($sObj->payment_splits) && is_array($sObj->payment_splits)) {
                    foreach ($sObj->payment_splits as $sp) {
                        if (strtolower($sp['method'] ?? '') === 'cash') {
                            $cumCashSales += (float) ($sp['amount'] ?? 0);
                        }
                    }
                } elseif (preg_match('/Tunai:\s*Rp([\d\.]+)/i', $sObj->notes ?? '', $m)) {
                    $cumCashSales += (float) str_replace('.', '', $m[1]);
                }
            }
        }
        $cumNonCashSales = max(0, $cumTotalSales - $cumCashSales);

        $cumExpenses = (float) $storeObj->expenses()->whereDate('expense_date', '>=', $startDate)->sum('amount');
        $initBal = (float) ($storeObj->initial_balance ?? 0);
        $endBal = (float) $storeObj->ending_balance;

        return response()->json([
            'status' => 'success',
            'data' => [
                'store_id' => $storeObj->id,
                'store_name' => $storeObj->name,
                'bank_name' => $storeObj->bank_name,
                'bank_account_number' => $storeObj->bank_account_number,
                'bank_account_name' => $storeObj->bank_account_name,
                'initial_balance' => $initBal,
                'initial_balance_date' => $startDate,
                'cumulative_sales' => $cumTotalSales,
                'cumulative_cash_sales' => $cumCashSales,
                'cumulative_non_cash_sales' => $cumNonCashSales,
                'cumulative_expenses' => $cumExpenses,
                'ending_balance' => $endBal,
            ]
        ]);
    }

    /**
     * Get attendance for today for a specific user and store, along with shifts and recent records.
     */
    public function attendanceToday(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $storeId = $request->query('store_id');
        $today = Carbon::today();
        $todayDate = $today->toDateString();

        $attendance = null;
        if ($userId) {
            $attendance = Attendance::where('user_id', $userId)
                ->where('date', $todayDate)
                ->with(['shift', 'user'])
                ->first();

            // Sesi kerja aktif lintas hari (misal shift malam yang clock out setelah 00:00)
            if (!$attendance || !$attendance->clock_in) {
                $openYesterday = Attendance::where('user_id', $userId)
                    ->whereDate('date', $today->copy()->subDay())
                    ->whereNotNull('clock_in')
                    ->whereNull('clock_out')
                    ->with(['shift', 'user'])
                    ->latest('clock_in')
                    ->first();

                if ($openYesterday) {
                    $attendance = $openYesterday;
                }
            }
        }

        $shifts = Shift::where('is_active', true)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->orderBy('start_time')
            ->get();

        if ($shifts->isEmpty() && $storeId) {
            $shifts = Shift::where('is_active', true)
                ->whereNull('store_id')
                ->orderBy('start_time')
                ->get();
        }

        if ($shifts->isEmpty()) {
            $shifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        }

        // Get all attendances today for this store / general

        $todayRecords = Attendance::where('date', $todayDate)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->with(['user', 'shift'])
            ->orderByDesc('clock_in')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'attendance' => $attendance,
                'shifts' => $shifts,
                'today_records' => $todayRecords,
                'date' => $today->toDateString(),
                'formatted_date' => $today->isoFormat('dddd, D MMMM Y'),
            ]
        ]);
    }

    /**
     * Get shifts from Shift Management for a specific store.
     */
    public function attendanceShifts(Request $request): JsonResponse
    {
        $storeId = $request->query('store_id');

        $shifts = Shift::where('is_active', true)
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->orderBy('start_time')
            ->get();

        if ($shifts->isEmpty() && $storeId) {
            $shifts = Shift::where('is_active', true)
                ->whereNull('store_id')
                ->orderBy('start_time')
                ->get();
        }

        if ($shifts->isEmpty()) {
            $shifts = Shift::where('is_active', true)->orderBy('start_time')->get();
        }

        return response()->json([
            'status' => 'success',
            'data' => $shifts
        ]);
    }

    /**
     * Attendance Clock In

     */
    public function attendanceClockIn(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'selfie' => 'required|string',
            'store_id' => 'nullable|exists:stores,id',
            'shift_id' => 'nullable|exists:shifts,id',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'address' => 'nullable|string|max:500',
            'face_verified' => 'nullable|boolean',
            'face_confidence' => 'nullable|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $userId = $request->input('user_id');
        $storeId = $request->input('store_id');
        $now = Carbon::now();
        $today = $now->toDateString();

        $existing = Attendance::where('user_id', $userId)->where('date', $today)->first();
        if ($existing && $existing->clock_in) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan ini sudah melakukan absen masuk hari ini.'
            ], 422);
        }

        // Save selfie photo
        $photoPath = $this->saveSelfie($request->input('selfie'), (int)$userId, 'in');

        // Determine late status
        $status = 'present';
        $lateMinutes = 0;
        $shift = $request->shift_id ? Shift::find($request->shift_id) : null;
        if ($shift && $shift->isLate($now)) {
            $status = 'late';
            $lateMinutes = $shift->lateMinutes($now);
        }

        $attendance = Attendance::updateOrCreate(
            ['user_id' => $userId, 'date' => $today],
            [
                'store_id' => $storeId,
                'shift_id' => $request->shift_id,
                'clock_in' => $now,
                'clock_in_photo' => $photoPath,
                'clock_in_lat' => $request->lat,
                'clock_in_lng' => $request->lng,
                'clock_in_address' => $request->address,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'face_verified' => $request->boolean('face_verified', true),
                'face_confidence' => $request->input('face_confidence', 95.0),
                'note' => $request->note,
            ]
        );

        $attendance->load(['shift', 'user']);

        return response()->json([
            'status' => 'success',
            'message' => $status === 'late'
                ? "Absen masuk berhasil. Anda terlambat {$lateMinutes} menit."
                : 'Absen masuk berhasil. Selamat bekerja!',
            'data' => $attendance,
        ]);
    }

    /**
     * Attendance Clock Out
     */
    public function attendanceClockOut(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'selfie' => 'required|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
            'address' => 'nullable|string|max:500',
            'face_verified' => 'nullable|boolean',
            'face_confidence' => 'nullable|numeric',
            'note' => 'nullable|string|max:500',
        ]);

        $userId = $request->input('user_id');
        $now = Carbon::now();
        $today = $now->toDateString();

        $attendance = Attendance::where('user_id', $userId)->where('date', $today)->first();

        // Jika hari ini belum ada atau belum clock in / sudah clock out,
        // periksa sesi kerja aktif lintas malam dari kemarin yang belum clock out
        if (!$attendance || !$attendance->clock_in || $attendance->clock_out) {
            $openYesterday = Attendance::where('user_id', $userId)
                ->whereDate('date', $now->copy()->subDay()->toDateString())
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->latest('clock_in')
                ->first();

            if ($openYesterday) {
                $attendance = $openYesterday;
            }
        }

        if (!$attendance || !$attendance->clock_in) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan ini belum melakukan absen masuk hari ini.'
            ], 422);
        }

        if ($attendance->clock_out) {
            return response()->json([
                'status' => 'error',
                'message' => 'Karyawan ini sudah melakukan absen pulang untuk sesi kerja ini.'
            ], 422);
        }

        $photoPath = $this->saveSelfie($request->input('selfie'), (int)$userId, 'out');
        $workMinutes = $now->diffInMinutes($attendance->clock_in);

        $attDateStr = Carbon::parse($attendance->date)->toDateString();
        $overtimeMinutes = 0;
        if ($attendance->shift) {
            $shiftStart = Carbon::parse($attDateStr . ' ' . $attendance->shift->start_time);
            $shiftEnd = Carbon::parse($attDateStr . ' ' . $attendance->shift->end_time);
            if ($attendance->shift->is_overnight || $shiftEnd->lte($shiftStart)) {
                $shiftEnd->addDay();
            }
            if ($now->gt($shiftEnd)) {
                $diffMins = $now->diffInMinutes($shiftEnd);
                // Lembur reguler hanya diakui jika clock out melebihi jam selesai shift minimal 1 jam (60 menit)
                $overtimeMinutes = $diffMins >= 60 ? $diffMins : 0;
            }
        }

        $attendance->update([
            'clock_out' => $now,
            'clock_out_photo' => $photoPath,
            'clock_out_lat' => $request->lat,
            'clock_out_lng' => $request->lng,
            'clock_out_address' => $request->address,
            'work_minutes' => $workMinutes,
            'overtime_minutes' => $overtimeMinutes,
            'face_verified' => $request->boolean('face_verified', true),
            'face_confidence' => $request->input('face_confidence', 95.0),
            'note' => $request->note ?? $attendance->note,
        ]);

        $attendance->load(['shift', 'user']);

        return response()->json([
            'status' => 'success',
            'message' => 'Absen pulang berhasil. Terima kasih atas dedikasi kerja Anda!',
            'data' => $attendance,
        ]);
    }

    /**
     * Save base64 selfie image.
     */
    private function saveSelfie(string $base64, int $userId, string $type): string
    {
        $image = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $image = base64_decode($image);

        $dir = "attendances/{$userId}";
        $filename = "{$type}_" . now()->format('Ymd_His') . ".jpg";
        $path = "{$dir}/{$filename}";

        Storage::disk('public')->put($path, $image);

        // Also copy to pos_absensi storage if it exists, so both Laravel apps can display photos
        $posAbsensiDir = base_path('../pos_absensi/storage/app/public/' . $dir);
        if (is_dir(base_path('../pos_absensi/storage/app/public'))) {
            if (!is_dir($posAbsensiDir)) {
                @mkdir($posAbsensiDir, 0755, true);
            }
            @file_put_contents($posAbsensiDir . '/' . $filename, $image);
        }

        return $path;
    }

    /**
     * Update payment method for a sale transaction from POS.
     */
    public function updatePaymentMethod(Request $request, $id): JsonResponse
    {
        // Ensure sales table has payment_splits column if not already migrated on production
        if (\Illuminate\Support\Facades\Schema::hasTable('sales') && !\Illuminate\Support\Facades\Schema::hasColumn('sales', 'payment_splits')) {
            \Illuminate\Support\Facades\Schema::table('sales', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->json('payment_splits')->nullable()->after('payment_method');
            });
        }

        $validated = $request->validate([
            'payment_method' => 'required|string|in:cash,qris,transfer,debit,credit,split,other',
            'payment_splits' => 'nullable|array',
            'payment_splits.*.method' => 'required_with:payment_splits|string|in:cash,qris,transfer,debit,credit',
            'payment_splits.*.amount' => 'required_with:payment_splits|numeric|min:0',
            'payment_splits.*.amount_paid' => 'nullable|numeric|min:0',
            'payment_splits.*.change_amount' => 'nullable|numeric|min:0',
        ]);

        $sale = Sale::withoutGlobalScopes()->findOrFail($id);
        $newMethod = strtolower($validated['payment_method']);
        $newMethodUpper = strtoupper($newMethod);

        if ($newMethod === 'split') {
            if (empty($validated['payment_splits']) || count($validated['payment_splits']) < 2) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Split payment membutuhkan minimal 2 rincian metode pembayaran.',
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            $sale->payment_method = $newMethod;
            $splitDetailsStr = '';

            if ($newMethod === 'split' && !empty($validated['payment_splits'])) {
                $sale->payment_splits = $validated['payment_splits'];

                $methodNames = [
                    'cash' => 'Tunai',
                    'qris' => 'QRIS',
                    'transfer' => 'Transfer',
                    'debit' => 'Debit',
                    'credit' => 'Kredit'
                ];
                $splitParts = [];
                foreach ($validated['payment_splits'] as $sp) {
                    $mKey = strtolower($sp['method'] ?? 'cash');
                    $mLabel = $methodNames[$mKey] ?? strtoupper($mKey);
                    $amtFormatted = number_format($sp['amount'], 0, ',', '.');
                    $spPaid = (float) ($sp['amount_paid'] ?? $sp['amount']);
                    $spChange = (float) ($sp['change_amount'] ?? 0);
                    if ($mKey === 'cash' && ($spPaid > $sp['amount'] || $spChange > 0)) {
                        $splitParts[] = "{$mLabel}: Rp{$amtFormatted} (Uang: Rp" . number_format($spPaid, 0, ',', '.') . ", Kembali: Rp" . number_format($spChange, 0, ',', '.') . ")";
                    } else {
                        $splitParts[] = "{$mLabel}: Rp{$amtFormatted}";
                    }
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

            // Synchronize all related StockMovement records so Stock Out & Stock Cards reflect the new payment method and details
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

            // Synchronize accounting journal entry
            app(\App\Services\AccountingService::class)->recordSaleJournal($sale);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Metode pembayaran berhasil diubah ke {$sale->payment_method_label}.",
                'data' => [
                    'id' => $sale->id,
                    'reference_number' => $sale->reference_number,
                    'payment_method' => $newMethod,
                    'payment_method_label' => $sale->payment_method_label,
                    'payment_splits' => $sale->payment_splits,
                    'badge_class' => $sale->payment_badge_class,
                    'notes' => $sale->notes,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah metode pembayaran: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update discount for sale items from POS.
     */
    public function updateDiscount(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.discount' => 'nullable|numeric|min:0',
        ], [
            'items.required' => 'Daftar item produk wajib ada.',
            'items.*.discount.min' => 'Diskon tidak boleh bernilai negatif.',
        ]);

        $sale = Sale::withoutGlobalScopes()->findOrFail($id);

        try {
            DB::beginTransaction();

            $sale->load('items');
            $saleItemsById = $sale->items->keyBy('id');
            $totalNewDiscount = 0;

            foreach ($validated['items'] as $itemInput) {
                $itemId = $itemInput['id'];
                if (!$saleItemsById->has($itemId)) {
                    continue;
                }

                $saleItem = $saleItemsById->get($itemId);
                $grossItem = (float) $saleItem->quantity * (float) $saleItem->selling_price;
                $discountVal = isset($itemInput['discount']) && $itemInput['discount'] !== '' ? (float) $itemInput['discount'] : 0;
                $newDiscount = min($discountVal, $grossItem);
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
            }

            // Recalculate sale total amount from items
            $sale->total_amount = $sale->items()->sum('subtotal');
            $sale->save();

            // Refresh double-entry accounting journal if available
            try {
                app(\App\Services\AccountingService::class)->recordSaleJournal($sale);
            } catch (\Throwable $th) {
                // Ignore accounting sync if not applicable
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Diskon transaksi #{$sale->reference_number} berhasil diperbarui.",
                'data' => [
                    'id' => $sale->id,
                    'reference_number' => $sale->reference_number,
                    'total_amount' => (float) $sale->total_amount,
                    'total_discount' => (float) $totalNewDiscount,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah diskon: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel / Void a sale transaction from POS.
     * Restores product stock and marks status as 'cancelled' on Sale and StockMovement.
     */
    public function cancelSale(Request $request, $id): JsonResponse
    {
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:500',
            'user_id' => 'nullable|integer|exists:users,id',
            'cashier_name' => 'nullable|string|max:100',
        ], [
            'cancellation_reason.required' => 'Alasan pembatalan transaksi wajib diisi.',
        ]);

        $sale = Sale::withoutGlobalScopes()->with(['items.product', 'user'])->findOrFail($id);

        if (($sale->status ?? 'completed') === 'cancelled') {
            return response()->json([
                'status' => 'error',
                'message' => "Transaksi #{$sale->reference_number} sudah dibatalkan sebelumnya.",
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reason = trim($validated['cancellation_reason']);
            $cashierName = $validated['cashier_name'] ?? ($sale->user?->name ?? 'Kasir');
            $cancelledBy = $validated['user_id'] ?? $sale->user_id;
            $now = Carbon::now();

            // 1. Revert product stocks (increment back to inventory)
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            // 2. Mark Sale as cancelled
            $sale->status = 'cancelled';
            $sale->cancellation_reason = $reason;
            $sale->cancelled_at = $now;
            $sale->cancelled_by = $cancelledBy;
            $sale->notes = "[DIBATALKAN oleh {$cashierName}: {$reason}] " . ($sale->notes ?? '');
            $sale->save();

            // 3. Mark corresponding StockMovement records as cancelled so they remain visible in Stock Out
            $auditPrefix = "[DIBATALKAN oleh " . addslashes($cashierName) . ": " . addslashes($reason) . "] ";
            StockMovement::withoutGlobalScopes()
                ->where('reference_number', $sale->reference_number)
                ->update([
                    'status' => 'cancelled',
                    'notes' => DB::raw("CONCAT('{$auditPrefix}', COALESCE(notes, ''))"),
                ]);

            // Purge double-entry accounting journal so voided sale is not counted in revenue/reports
            app(\App\Services\AccountingService::class)->purgeJournal('sale', $sale->id);
            app(\App\Services\AccountingService::class)->purgeJournal('sale_cancellation', $sale->id);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Transaksi #{$sale->reference_number} berhasil dibatalkan. Stok seluruh item telah dikembalikan ke inventaris.",
                'data' => [
                    'id' => $sale->id,
                    'reference_number' => $sale->reference_number,
                    'status' => 'cancelled',
                    'cancellation_reason' => $reason,
                    'cancelled_at' => $now->format('d/m/Y H:i:s'),
                    'cashier_name' => $cashierName,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membatalkan transaksi: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance history for the logged-in user.
     */
    public function attendanceHistory(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $storeId = $request->query('store_id');
        $month = $request->query('month', Carbon::now()->format('Y-m'));

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'user_id is required'
            ], 400);
        }

        $records = Attendance::where('user_id', $userId)
            ->where('date', 'like', "{$month}%")
            ->when($storeId, fn($q) => $q->where('store_id', $storeId))
            ->with(['shift', 'store'])
            ->orderByDesc('date')
            ->get();

        $totalPresent = $records->whereNotNull('clock_in')->count();
        $totalLate = $records->where('status', 'late')->count();
        $totalOnTime = $records->where('status', 'on_time')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'records' => $records,
                'summary' => [
                    'total_present' => $totalPresent,
                    'total_late' => $totalLate,
                    'total_on_time' => $totalOnTime,
                    'month' => $month,
                ]
            ]
        ]);
    }

    /**
     * Get list of purchase requests.
     */
    public function purchaseRequests(Request $request): JsonResponse
    {
        $storeId = $request->query('store_id');
        $userId = $request->query('user_id');

        $query = \App\Models\PurchaseRequest::withoutGlobalScopes()
            ->with(['items.product', 'user:id,name', 'approvedByUser:id,name', 'store:id,name'])
            ->orderByDesc('created_at');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $requests = $query->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests
        ]);
    }

    /**
     * Create a new purchase request from staff.
     */
    public function storePurchaseRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'user_id' => 'required|exists:users,id',
            'priority' => 'nullable|string|in:normal,urgent',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity_requested' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Generate unique PR Number: PR-YYYYMMDD-XXXX
            $datePrefix = date('Ymd');
            $latestPr = \App\Models\PurchaseRequest::withoutGlobalScopes()
                ->where('pr_number', 'like', "PR-{$datePrefix}-%")
                ->orderByDesc('id')
                ->first();

            $seq = 1;
            if ($latestPr && preg_match('/PR-\d+-(\d+)/', $latestPr->pr_number, $matches)) {
                $seq = intval($matches[1]) + 1;
            }
            $prNumber = sprintf('PR-%s-%04d', $datePrefix, $seq);

            $pr = \App\Models\PurchaseRequest::create([
                'pr_number' => $prNumber,
                'store_id' => $validated['store_id'],
                'user_id' => $validated['user_id'],
                'request_date' => Carbon::now()->toDateString(),
                'status' => 'pending',
                'priority' => $validated['priority'] ?? 'normal',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $itemData) {
                \App\Models\PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => $itemData['product_id'],
                    'quantity_requested' => $itemData['quantity_requested'],
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            DB::commit();

            $pr->load(['items.product', 'user', 'store']);

            return response()->json([
                'status' => 'success',
                'message' => "Order barang #{$prNumber} berhasil diajukan.",
                'data' => $pr
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membuat purchase request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get personal sales report for a staff/cashier.
     */
    public function staffSalesReport(Request $request): JsonResponse
    {
        $userId = $request->query('user_id');
        $storeId = $request->query('store_id');
        $period = $request->query('period', 'today'); // 'today', 'this_month', 'last_month', 'custom'
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if (!$userId) {
            return response()->json([
                'status' => 'error',
                'message' => 'user_id is required'
            ], 400);
        }

        $now = Carbon::now();
        if ($period === 'today') {
            $startDate = $now->toDateString();
            $endDate = $now->toDateString();
        } elseif ($period === 'this_month') {
            $startDate = $now->copy()->startOfMonth()->toDateString();
            $endDate = $now->toDateString();
        } elseif ($period === 'last_month') {
            $startDate = $now->copy()->subMonth()->startOfMonth()->toDateString();
            $endDate = $now->copy()->subMonth()->endOfMonth()->toDateString();
        } else {
            $startDate = $dateFrom ?: $now->toDateString();
            $endDate = $dateTo ?: $now->toDateString();
        }

        $salesQuery = Sale::withoutGlobalScopes()
            ->where('user_id', $userId)
            ->whereBetween('sale_date', [$startDate, $endDate])
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
            });

        if ($storeId) {
            $salesQuery->where('store_id', $storeId);
        }

        $sales = (clone $salesQuery)->with(['items.product'])->orderByDesc('created_at')->get();

        $totalSalesAmount = (float) $sales->sum('total_amount');
        $totalTransactions = $sales->count();
        $totalItemsSold = 0;
        $productStats = [];

        foreach ($sales as $sale) {
            foreach ($sale->items as $item) {
                $totalItemsSold += (int) $item->quantity;
                $pId = $item->product_id;
                $pName = $item->product?->name ?? 'Produk Dihapus';
                $pSku = $item->product?->sku ?? '-';

                if (!isset($productStats[$pId])) {
                    $productStats[$pId] = [
                        'product_id' => $pId,
                        'name' => $pName,
                        'sku' => $pSku,
                        'quantity_sold' => 0,
                        'total_amount' => 0,
                    ];
                }
                $productStats[$pId]['quantity_sold'] += (int) $item->quantity;
                $productStats[$pId]['total_amount'] += (float) ($item->total_price ?? ($item->quantity * $item->unit_price));
            }
        }

        $topProducts = collect(array_values($productStats))->sortByDesc('quantity_sold')->take(10)->values();
        $avgTransaction = $totalTransactions > 0 ? round($totalSalesAmount / $totalTransactions, 2) : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'summary' => [
                    'total_sales' => $totalSalesAmount,
                    'total_transactions' => $totalTransactions,
                    'total_items_sold' => $totalItemsSold,
                    'average_transaction' => $avgTransaction,
                ],
                'top_products' => $topProducts,
                'recent_sales' => $sales->take(20)->map(function ($s) {
                    return [
                        'id' => $s->id,
                        'reference_number' => $s->reference_number,
                        'sale_date' => $s->sale_date,
                        'total_amount' => (float) $s->total_amount,
                        'payment_method' => $s->payment_method,
                        'item_count' => $s->items->count(),
                        'created_at' => $s->created_at ? $s->created_at->format('H:i') : '-',
                    ];
                }),
            ]
        ]);
    }
}


