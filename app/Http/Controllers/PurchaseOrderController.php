<?php

namespace App\Http\Controllers;

use App\Models\MovementDate;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Setting;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\ApprovalWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of Purchase Orders.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('Stock In Create') && !$user->can('Movement List') && !$user->can('Accounting Payable Manage') && !$user->can('Dashboard List') && !$user->can('Z1 App Purchase Request')) {
            abort(403, 'Anda tidak memiliki akses ke Modul Purchasing.');
        }

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->roles->contains(fn($r) => strtolower($r->name) === 'super admin') || $user->id === 1;
        $userStoreIds = $user->stores->pluck('id')->toArray();
        $activeStoreId = session('active_store_id') ?? ($userStoreIds[0] ?? null);

        $stores = $isSuperAdmin ? Store::orderBy('name')->get() : $user->stores()->orderBy('name')->get();

        // Filters
        $storeFilter = $request->input('store_id');
        // Default ke cabang toko yang sedang aktif jika filter belum dipilih
        $storeId = $storeFilter !== null ? $storeFilter : $activeStoreId;
        $status = $request->input('status', 'all');
        $supplier = $request->input('supplier');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        $baseQuery = PurchaseOrder::withoutGlobalScopes()
            ->with(['items.product', 'store', 'creator', 'receiver', 'purchaseRequest']);

        if ($storeId && $storeId !== 'all') {
            $baseQuery->where('store_id', $storeId);
        } elseif (!$isSuperAdmin && !empty($userStoreIds)) {
            $baseQuery->whereIn('store_id', $userStoreIds);
        }

        // Metrics Query
        $metricsQuery = clone $baseQuery;
        if (!empty($startDate) && !empty($endDate)) {
            $metricsQuery->whereBetween('po_date', [$startDate, $endDate]);
        }

        $allPos = $metricsQuery->get();
        $totalPoCount = $allPos->count();
        $totalPoAmount = $allPos->sum('total_amount');
        $totalPoUnits = $allPos->sum(function ($po) {
            return $po->items->sum('quantity_ordered');
        });

        $draftCount = $allPos->where('status', 'draft')->count();
        $sentCount = $allPos->where('status', 'sent')->count();
        $partialCount = $allPos->where('status', 'partial')->count();
        $receivedCount = $allPos->where('status', 'received')->count();

        // Filter applications
        if (!empty($status) && $status !== 'all') {
            $baseQuery->where('status', $status);
        }

        if (!empty($supplier)) {
            $baseQuery->where('supplier_name', 'like', "%{$supplier}%");
        }

        if (!empty($startDate) && !empty($endDate)) {
            $baseQuery->whereBetween('po_date', [$startDate, $endDate]);
        }

        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('supplier_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('purchaseRequest', function ($prQ) use ($search) {
                      $prQ->where('pr_number', 'like', "%{$search}%");
                  });
            });
        }

        $purchaseOrders = $baseQuery->orderByDesc('po_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Distinct suppliers for autocomplete
        $suppliers = PurchaseOrder::distinct()->whereNotNull('supplier_name')->pluck('supplier_name');

        return view('purchasing.purchase_orders.index', compact(
            'purchaseOrders',
            'stores',
            'storeId',
            'status',
            'supplier',
            'startDate',
            'endDate',
            'search',
            'totalPoCount',
            'totalPoAmount',
            'totalPoUnits',
            'draftCount',
            'sentCount',
            'partialCount',
            'receivedCount',
            'suppliers',
            'activeStoreId'
        ));
    }

    /**
     * Show form to create new Purchase Order (Optionally pre-populated from Purchase Request).
     */
    public function create(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin') || $user->roles->contains(fn($r) => strtolower($r->name) === 'super admin') || $user->id === 1;
        $stores = $isSuperAdmin ? Store::orderBy('name')->get() : $user->stores()->orderBy('name')->get();
        $userStoreIds = $user->stores->pluck('id')->toArray();
        $activeStoreId = session('active_store_id') ?? ($userStoreIds[0] ?? $stores->first()?->id);

        $prId = $request->input('purchase_request_id');
        $sourcePr = null;
        if ($prId) {
            $sourcePr = PurchaseRequest::withoutGlobalScopes()
                ->with(['items.product' => fn($q) => $q->withoutGlobalScopes(), 'store', 'user'])
                ->find($prId);
        }

        $selectedStoreId = $sourcePr ? $sourcePr->store_id : ($request->input('store_id') ?? $activeStoreId);

        // Available PRs yang sudah di-approve khusus untuk cabang terpilih / cabang aktif
        $availablePrsQuery = PurchaseRequest::withoutGlobalScopes()
            ->where('status', 'approved')
            ->with(['store', 'user', 'items.product' => fn($q) => $q->withoutGlobalScopes()])
            ->orderByDesc('id');

        if ($selectedStoreId && $selectedStoreId !== 'all') {
            $availablePrsQuery->where('store_id', $selectedStoreId);
        } elseif (!$isSuperAdmin && !empty($userStoreIds)) {
            $availablePrsQuery->whereIn('store_id', $userStoreIds);
        }

        $availablePrs = $availablePrsQuery->get();

        $storeProducts = Product::withoutGlobalScopes()->where('store_id', $selectedStoreId)->orderBy('name')->get();
        $allStoreProducts = Product::withoutGlobalScopes()->orderBy('name')->get(['id', 'store_id', 'name', 'sku', 'price', 'purchase_price', 'stock']);

        // Nama Supplier diambil dari Master Data Pengaturan Sistem + histori PO
        $masterSuppliers = \App\Models\Setting::where('type', 'supplier')->orderBy('value')->pluck('value')->toArray();
        $poSuppliers = PurchaseOrder::distinct()->whereNotNull('supplier_name')->pluck('supplier_name')->toArray();
        $movementSuppliers = \App\Models\MovementDate::distinct()->whereNotNull('supplier_name')->pluck('supplier_name')->toArray();
        $suppliers = collect(array_merge($masterSuppliers, $poSuppliers, $movementSuppliers))->filter()->unique()->values();

        return view('purchasing.purchase_orders.create', compact(
            'stores',
            'sourcePr',
            'availablePrs',
            'selectedStoreId',
            'storeProducts',
            'allStoreProducts',
            'suppliers'
        ));
    }

    /**
     * Store newly created Purchase Order.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'purchase_request_id' => 'nullable|exists:purchase_requests,id',
            'store_id' => 'required|exists:stores,id',
            'supplier_name' => 'required|string|max:255',
            'po_date' => 'required|date',
            'expected_date' => 'nullable|date',
            'payment_method' => 'required|string|in:cash,transfer,tempo',
            'payment_term_days' => 'nullable|integer|min:0',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.custom_item_name' => 'nullable|string|max:255',
            'items.*.quantity_ordered' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            'items.*.purchase_request_item_id' => 'nullable|exists:purchase_request_items,id',
        ]);

        DB::beginTransaction();
        try {
            $datePrefix = date('Ymd', strtotime($validated['po_date']));
            $latestPo = PurchaseOrder::withoutGlobalScopes()
                ->where('po_number', 'like', "PO-{$datePrefix}-%")
                ->orderByDesc('id')
                ->first();

            $seq = 1;
            if ($latestPo && preg_match('/PO-\d+-(\d+)/', $latestPo->po_number, $matches)) {
                $seq = intval($matches[1]) + 1;
            }
            $poNumber = sprintf('PO-%s-%04d', $datePrefix, $seq);

            $expectedDate = $validated['expected_date'] ?? null;
            if (empty($expectedDate) && ($validated['payment_method'] ?? '') === 'tempo' && !empty($validated['payment_term_days'])) {
                $expectedDate = Carbon::parse($validated['po_date'])->addDays((int) $validated['payment_term_days'])->toDateString();
            }

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'purchase_request_id' => $validated['purchase_request_id'] ?? null,
                'store_id' => $validated['store_id'],
                'supplier_name' => trim($validated['supplier_name']),
                'po_date' => $validated['po_date'],
                'expected_date' => $expectedDate,
                'payment_method' => $validated['payment_method'],
                'payment_term_days' => $validated['payment_term_days'] ?? 0,
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            // Simpan otomatis ke Master Data Supplier jika belum terdaftar
            $trimmedSupplier = trim($validated['supplier_name']);
            if (!empty($trimmedSupplier)) {
                \App\Models\Setting::firstOrCreate([
                    'type' => 'supplier',
                    'value' => $trimmedSupplier,
                ]);
            }

            $totalAmount = 0;
            foreach ($validated['items'] as $itemData) {
                if (empty($itemData['product_id']) && empty($itemData['custom_item_name'])) {
                    continue;
                }

                $qty = intval($itemData['quantity_ordered']);
                $price = floatval($itemData['unit_price']);
                $subtotal = $qty * $price;
                $totalAmount += $subtotal;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'purchase_request_item_id' => $itemData['purchase_request_item_id'] ?? null,
                    'product_id' => !empty($itemData['product_id']) ? $itemData['product_id'] : null,
                    'custom_item_name' => !empty($itemData['custom_item_name']) ? trim($itemData['custom_item_name']) : null,
                    'quantity_ordered' => $qty,
                    'quantity_received' => 0,
                    'unit_price' => $price,
                    'subtotal' => $subtotal,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            // If generated from a Purchase Request, update PR to approved if still pending
            if (!empty($validated['purchase_request_id'])) {
                $pr = PurchaseRequest::find($validated['purchase_request_id']);
                if ($pr && $pr->status === 'pending') {
                    $pr->update([
                        'status' => 'approved',
                        'approved_by' => $user->id,
                        'approved_at' => Carbon::now(),
                        'admin_notes' => "Diteruskan ke Purchase Order #{$poNumber}",
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('success', "Purchase Order #{$poNumber} berhasil dibuat dengan total Rp " . number_format($totalAmount, 0, ',', '.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat Purchase Order: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific Purchase Order.
     */
    public function show($id)
    {
        $user = Auth::user();
        $po = PurchaseOrder::withoutGlobalScopes()
            ->with(['items.product', 'store', 'creator', 'receiver', 'purchaseRequest.user', 'stockMovements'])
            ->findOrFail($id);

        $totalUnitsOrdered = $po->items->where('is_cancelled', false)->sum('quantity_ordered');
        $totalUnitsReceived = $po->items->where('is_cancelled', false)->sum('quantity_received');
        $totalActiveAmount = $po->items->where('is_cancelled', false)->sum('subtotal');

        $storeProducts = Product::withoutGlobalScopes()->where('store_id', $po->store_id)->orderBy('name')->get();
        $categories = Setting::where('type', 'category')->pluck('value');
        $canApprovePo = ApprovalWorkflow::canUserApprove($user, 'purchase_order', 'sent');

        return view('purchasing.purchase_orders.show', compact(
            'po',
            'user',
            'canApprovePo',
            'totalUnitsOrdered',
            'totalUnitsReceived',
            'totalActiveAmount',
            'storeProducts',
            'categories'
        ));
    }

    /**
     * Add an item (existing or custom product) to the Purchase Order.
     */
    public function addItem(Request $request, $id)
    {
        $po = PurchaseOrder::withoutGlobalScopes()->findOrFail($id);

        if ($po->status === 'received') {
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Tidak dapat menambah item pada PO yang sudah selesai.');
        }

        $validated = $request->validate([
            'item_type' => 'required|in:existing,custom',
            'product_id' => 'nullable|required_if:item_type,existing|exists:products,id',
            'custom_item_name' => 'nullable|required_if:item_type,custom|string|max:255',
            'custom_sku' => 'nullable|string|max:100',
            'quantity_ordered' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $productId = $validated['item_type'] === 'existing' ? $validated['product_id'] : null;
            $customName = $validated['item_type'] === 'custom' ? trim($validated['custom_item_name']) : null;
            $customSku = $validated['item_type'] === 'custom' 
                ? (!empty($validated['custom_sku']) ? trim($validated['custom_sku']) : ('SKU-' . strtoupper(Str::random(6)))) 
                : null;
            $qty = intval($validated['quantity_ordered']);
            $unitPrice = floatval($validated['unit_price']);
            $sellingPrice = !empty($validated['selling_price']) ? floatval($validated['selling_price']) : null;
            $subtotal = $qty * $unitPrice;

            // 1. If PO is linked to a Purchase Request, also add item to PR
            $prItemId = null;
            if ($po->purchase_request_id) {
                $prItem = PurchaseRequestItem::create([
                    'purchase_request_id' => $po->purchase_request_id,
                    'product_id' => $productId,
                    'custom_item_name' => $customName,
                    'custom_sku' => $customSku,
                    'estimated_price' => $unitPrice,
                    'selling_price' => $sellingPrice,
                    'quantity_requested' => $qty,
                    'quantity_approved' => $qty,
                    'is_cancelled' => false,
                    'added_from_po' => true,
                    'notes' => !empty($validated['notes']) ? "Tambahan dari PO: {$validated['notes']}" : "Tambahan dari PO #{$po->po_number}",
                ]);
                $prItemId = $prItem->id;
            }

            // 2. Create Purchase Order Item
            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'purchase_request_item_id' => $prItemId,
                'product_id' => $productId,
                'custom_item_name' => $customName,
                'custom_sku' => $customSku,
                'quantity_ordered' => $qty,
                'quantity_received' => 0,
                'is_cancelled' => false,
                'unit_price' => $unitPrice,
                'selling_price' => $sellingPrice,
                'subtotal' => $subtotal,
                'notes' => $validated['notes'] ?? null,
            ]);

            // 3. Recalculate PO total
            $po->recalculateTotalAmount();

            DB::commit();

            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('success', 'Barang berhasil ditambahkan ke Purchase Order (dan disinkronkan ke PR terkait).');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Gagal menambahkan barang: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Purchase Order item.
     */
    public function updateItem(Request $request, $id, $itemId)
    {
        $po = PurchaseOrder::withoutGlobalScopes()->findOrFail($id);
        $item = PurchaseOrderItem::where('purchase_order_id', $po->id)->findOrFail($itemId);

        if ($po->status === 'received') {
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Tidak dapat mengubah item pada PO yang sudah selesai.');
        }

        $validated = $request->validate([
            'quantity_ordered' => 'required|integer|min:' . max(1, $item->quantity_received),
            'unit_price' => 'required|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $qty = intval($validated['quantity_ordered']);
            $unitPrice = floatval($validated['unit_price']);
            $sellingPrice = !empty($validated['selling_price']) ? floatval($validated['selling_price']) : null;
            $subtotal = $qty * $unitPrice;

            $item->update([
                'quantity_ordered' => $qty,
                'unit_price' => $unitPrice,
                'selling_price' => $sellingPrice,
                'subtotal' => $subtotal,
                'notes' => $validated['notes'] ?? $item->notes,
            ]);

            // Sync with PR item if linked
            if ($item->purchase_request_item_id) {
                $prItem = PurchaseRequestItem::find($item->purchase_request_item_id);
                if ($prItem) {
                    $prItem->update([
                        'quantity_approved' => $qty,
                        'estimated_price' => $unitPrice,
                        'selling_price' => $sellingPrice,
                    ]);
                }
            }

            // Recalculate PO total
            $po->recalculateTotalAmount();

            DB::commit();

            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('success', 'Rincian item barang berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Gagal memperbarui item: ' . $e->getMessage());
        }
    }

    /**
     * Toggle cancel status on a Purchase Order item.
     */
    public function toggleCancelItem(Request $request, $id, $itemId)
    {
        $po = PurchaseOrder::withoutGlobalScopes()->findOrFail($id);
        $item = PurchaseOrderItem::where('purchase_order_id', $po->id)->findOrFail($itemId);

        if ($item->quantity_received > 0) {
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Barang yang sudah diterima sebagian tidak dapat dibatalkan.');
        }

        DB::beginTransaction();
        try {
            $newCancelState = !$item->is_cancelled;
            $item->update(['is_cancelled' => $newCancelState]);

            // Sync with linked PR item if any
            if ($item->purchase_request_item_id) {
                $prItem = PurchaseRequestItem::find($item->purchase_request_item_id);
                if ($prItem) {
                    $prItem->update(['is_cancelled' => $newCancelState]);
                }
            }

            // Recalculate PO total
            $po->recalculateTotalAmount();

            DB::commit();

            $msg = $newCancelState 
                ? "Barang '{$item->item_name}' telah dibatalkan (digaris merah & disinkronkan ke PR)." 
                : "Barang '{$item->item_name}' telah dipulihkan kembali.";

            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('success', $msg);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Gagal mengubah status item: ' . $e->getMessage());
        }
    }

    /**
     * Delete an unfulfilled item from PO.
     */
    public function deleteItem(Request $request, $id, $itemId)
    {
        $po = PurchaseOrder::withoutGlobalScopes()->findOrFail($id);
        $item = PurchaseOrderItem::where('purchase_order_id', $po->id)->findOrFail($itemId);

        if ($item->quantity_received > 0) {
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Barang yang sudah diterima fisik tidak dapat dihapus.');
        }

        DB::beginTransaction();
        try {
            $itemName = $item->item_name;

            // If linked to a PR item that was added exclusively from PO, remove it too
            if ($item->purchase_request_item_id) {
                $prItem = PurchaseRequestItem::find($item->purchase_request_item_id);
                if ($prItem && $prItem->added_from_po) {
                    $prItem->delete();
                }
            }

            $item->delete();
            $po->recalculateTotalAmount();

            DB::commit();

            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('success', "Item '{$itemName}' berhasil dihapus dari PO.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Gagal menghapus item: ' . $e->getMessage());
        }
    }

    /**
     * Update status (e.g. Kirim ke Supplier / Draft / Cancel).
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        $po = PurchaseOrder::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,cancelled',
        ]);

        if ($validated['status'] === 'sent' && !ApprovalWorkflow::canUserApprove($user, 'purchase_order', 'sent')) {
            abort(403, 'Role Anda tidak memiliki wewenang Approval Workflow untuk menerbitkan/mengirim Purchase Order ke Supplier.');
        }

        $po->update(['status' => $validated['status']]);

        $statusMsg = match ($validated['status']) {
            'sent' => 'Purchase Order telah diterbitkan dan dikirim ke Supplier.',
            'cancelled' => 'Purchase Order telah dibatalkan.',
            default => 'Status PO diperbarui ke Draft.',
        };

        return redirect()->route('purchasing.purchase-orders.show', $po->id)
            ->with('success', $statusMsg);
    }

    /**
     * Process receiving of goods (Stock In from PO).
     */
    public function receive(Request $request, $id)
    {
        $user = Auth::user();
        $po = PurchaseOrder::withoutGlobalScopes()->with(['items.product', 'store'])->findOrFail($id);

        if (in_array($po->status, ['cancelled', 'received'])) {
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'PO ini tidak dapat diproses penerimaan barang.');
        }

        $validated = $request->validate([
            'received_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array',
            'items.*.received_qty' => 'required|integer|min:0',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.selling_price' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $receivedDateStr = $validated['received_date'];
            $totalReceivedThisBatch = 0;

            // Ensure a movement_date record exists for today/receivedDate to group movements cleanly
            $movementDateRecord = MovementDate::firstOrCreate(
                [
                    'date' => $receivedDateStr,
                    'store_id' => $po->store_id,
                    'type' => 'in',
                ],
                [
                    'payment_method' => $po->payment_method,
                    'supplier_name' => $po->supplier_name,
                    'created_by' => $user->id,
                    'notes' => "Penerimaan PO #{$po->po_number}",
                ]
            );

            foreach ($po->items as $item) {
                if ($item->is_cancelled) {
                    continue; // Skip cancelled items
                }

                $itemReceivedQty = intval($validated['items'][$item->id]['received_qty'] ?? 0);
                if ($itemReceivedQty <= 0) {
                    continue;
                }

                $actualUnitPrice = isset($validated['items'][$item->id]['unit_price']) && is_numeric($validated['items'][$item->id]['unit_price'])
                    ? floatval($validated['items'][$item->id]['unit_price'])
                    : (float) $item->unit_price;

                $actualSellingPrice = isset($validated['items'][$item->id]['selling_price']) && is_numeric($validated['items'][$item->id]['selling_price'])
                    ? floatval($validated['items'][$item->id]['selling_price'])
                    : (float) ($item->selling_price ?? 0);

                // Update item's recorded actual unit_price, selling_price and received qty
                $item->update([
                    'unit_price' => $actualUnitPrice,
                    'selling_price' => $actualSellingPrice > 0 ? $actualSellingPrice : $item->selling_price,
                    'subtotal' => $item->quantity_ordered * $actualUnitPrice,
                    'quantity_received' => $item->quantity_received + $itemReceivedQty,
                ]);

                $totalReceivedThisBatch += $itemReceivedQty;

                // Handle product: if existing, find it; if custom/new, create in master products table automatically!
                $product = null;
                if ($item->product_id) {
                    $product = Product::withoutGlobalScopes()->find($item->product_id);
                } else {
                    $sku = $item->custom_sku ?: ('SKU-' . strtoupper(Str::random(6)));
                    $product = Product::withoutGlobalScopes()
                        ->where('store_id', $po->store_id)
                        ->where(function ($q) use ($sku, $item) {
                            $q->where('sku', $sku);
                            if ($item->custom_item_name) {
                                $q->orWhere('name', $item->custom_item_name);
                            }
                        })->first();

                    if (!$product) {
                        $product = Product::create([
                            'store_id' => $po->store_id,
                            'sku' => $sku,
                            'name' => $item->custom_item_name ?? 'Produk Baru PO',
                            'purchase_price' => $actualUnitPrice,
                            'selling_price' => $actualSellingPrice > 0 ? $actualSellingPrice : ($actualUnitPrice * 1.2),
                            'stock' => 0,
                            'min_stock' => 5,
                        ]);
                    }
                    $item->update(['product_id' => $product->id]);
                }

                if ($product) {
                    // Update master product selling_price and purchase_price if provided
                    $productUpdates = [];
                    if ($actualSellingPrice > 0) {
                        $productUpdates['selling_price'] = $actualSellingPrice;
                    }
                    if ($actualUnitPrice > 0) {
                        $productUpdates['purchase_price'] = $actualUnitPrice;
                    }
                    if (!empty($productUpdates)) {
                        $product->update($productUpdates);
                    }

                    $isTempo = in_array(strtolower($po->payment_method), ['tempo', 'hutang', 'credit']);
                    $lineTotal = $itemReceivedQty * $actualUnitPrice;

                    // Create Stock Movement
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'in',
                        'quantity' => $itemReceivedQty,
                        'purchase_price' => $actualUnitPrice,
                        'selling_price' => $actualSellingPrice > 0 ? $actualSellingPrice : ($product->selling_price ?? $product->price ?? 0),
                        'total_price' => $lineTotal,
                        'payment_method' => $po->payment_method,
                        'supplier_name' => $po->supplier_name,
                        'payment_status' => $isTempo ? 'pending' : 'paid',
                        'paid_amount' => $isTempo ? 0 : $lineTotal,
                        'due_date' => $po->expected_date,
                        'reference_number' => $po->po_number,
                        'store_id' => $po->store_id,
                        'user_id' => $user->id,
                        'movement_date' => $receivedDateStr,
                        'movement_date_id' => $movementDateRecord->id,
                        'status' => 'approved',
                        'notes' => "Diterima dari PO #{$po->po_number}. " . ($validated['notes'] ?? ''),
                    ]);

                    // Physical stock increment
                    $product->increment('stock', $itemReceivedQty);

                    // Moving Average Cost (MAC) update
                    if (method_exists($product, 'updateMovingAverageCost')) {
                        $product->updateMovingAverageCost($actualUnitPrice, $itemReceivedQty);
                    }
                }
            }

            if ($totalReceivedThisBatch === 0) {
                DB::rollBack();
                return redirect()->route('purchasing.purchase-orders.show', $po->id)
                    ->with('error', 'Tidak ada kuantitas barang yang diterima (semua bernilai 0 atau dibatalkan).');
            }

            // Recalculate PO total amount
            $po->recalculateTotalAmount();

            // Check overall completion among non-cancelled items
            $allCompleted = true;
            $hasActiveItems = false;
            foreach ($po->items as $it) {
                if ($it->is_cancelled) {
                    continue;
                }
                $hasActiveItems = true;
                if ($it->quantity_received < $it->quantity_ordered) {
                    $allCompleted = false;
                    break;
                }
            }

            $newStatus = ($allCompleted && $hasActiveItems) ? 'received' : 'partial';
            $po->update([
                'status' => $newStatus,
                'received_by' => $user->id,
                'received_at' => Carbon::now(),
            ]);

            // Update linked PR if fully received
            if ($allCompleted && $po->purchase_request_id) {
                $pr = PurchaseRequest::find($po->purchase_request_id);
                if ($pr) {
                    $pr->update(['status' => 'completed']);
                }
            }

            DB::commit();

            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('success', "Sebanyak {$totalReceivedThisBatch} pcs barang berhasil diterima dan dicatat ke Stock In!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('purchasing.purchase-orders.show', $po->id)
                ->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }

    /**
     * Print View for Purchase Order (Official PO Letter).
     */
    public function print($id)
    {
        $po = PurchaseOrder::withoutGlobalScopes()
            ->with(['items.product', 'store', 'creator', 'purchaseRequest'])
            ->findOrFail($id);

        $totalUnitsOrdered = $po->items->sum('quantity_ordered');

        return view('purchasing.purchase_orders.print', compact('po', 'totalUnitsOrdered'));
    }

    /**
     * Delete / Cancel Purchase Order.
     */
    public function destroy($id)
    {
        $po = PurchaseOrder::withoutGlobalScopes()->findOrFail($id);

        if ($po->status === 'received' || $po->items()->where('quantity_received', '>', 0)->exists()) {
            return redirect()->route('purchasing.purchase-orders.index')
                ->with('error', 'PO yang sudah menerima fisik barang tidak dapat dihapus.');
        }

        $poNumber = $po->po_number;
        $po->items()->delete();
        $po->delete();

        return redirect()->route('purchasing.purchase-orders.index')
            ->with('success', "Purchase Order #{$poNumber} berhasil dihapus.");
    }
}

