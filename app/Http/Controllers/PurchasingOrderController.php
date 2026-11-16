<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Store;
use App\Models\ApprovalWorkflow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchasingOrderController extends Controller
{
    /**
     * Display a listing of Purchase Orders (Order Barang).
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

        // Fetch stores list for filters & creation
        $stores = $isSuperAdmin ? Store::orderBy('name')->get() : $user->stores()->orderBy('name')->get();

        // Filters
        $storeFilter = $request->input('store_id');
        // Default ke cabang toko yang sedang aktif jika filter belum dipilih
        $storeId = $storeFilter !== null ? $storeFilter : $activeStoreId;
        $prDate = $request->input('pr_date');
        $prStatus = $request->input('pr_status', 'all');
        $search = $request->input('search');

        // Base Query
        $baseQuery = PurchaseRequest::withoutGlobalScopes()
            ->with(['items.product' => fn($q) => $q->withoutGlobalScopes(), 'store', 'user', 'approvedByUser', 'purchaseOrders']);

        // Store scoping: jika pilih toko spesifik (atau default ke toko aktif)
        if ($storeId && $storeId !== 'all') {
            $baseQuery->where('store_id', $storeId);
        } elseif (!$isSuperAdmin && !empty($userStoreIds)) {
            $baseQuery->whereIn('store_id', $userStoreIds);
        }

        // Metrics Calculation Query (before applying status & date filter if needed, or based on store)
        $metricsQuery = clone $baseQuery;
        if (!empty($prDate)) {
            $metricsQuery->whereDate('request_date', $prDate);
        }

        $allOrdersForMetrics = $metricsQuery->get();
        $totalPrOrders = $allOrdersForMetrics->count();
        $totalPrUnits = $allOrdersForMetrics->sum(function ($pr) {
            return $pr->items->sum('quantity_requested');
        });
        $totalPrAmount = $allOrdersForMetrics->sum(function ($pr) {
            return $pr->items->sum(function ($item) {
                return ($item->product?->purchase_price ?? $item->product?->price ?? $item->estimated_price ?? 0) * $item->quantity_requested;
            });
        });

        $pendingCount = $allOrdersForMetrics->where('status', 'pending')->count();
        $approvedCount = $allOrdersForMetrics->where('status', 'approved')->count();
        $completedCount = $allOrdersForMetrics->where('status', 'completed')->count();
        $rejectedCount = $allOrdersForMetrics->where('status', 'rejected')->count();

        // Apply filters to list query
        if (!empty($prDate)) {
            $baseQuery->whereDate('request_date', $prDate);
        }

        if (!empty($prStatus) && $prStatus !== 'all') {
            $baseQuery->where('status', $prStatus);
        }

        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('custom_item_name', 'like', "%{$search}%")
                         ->orWhereHas('product', function ($pq) use ($search) {
                             $pq->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $purchaseRequests = $baseQuery->orderByDesc('request_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        // Products for modal creation
        $targetStoreIdForProducts = $storeId && $storeId !== 'all' ? $storeId : $activeStoreId;
        $availableProducts = Product::when($targetStoreIdForProducts, function ($q) use ($targetStoreIdForProducts) {
            $q->where('store_id', $targetStoreIdForProducts);
        })->orderBy('name')->get();

        return view('purchasing.orders.index', compact(
            'purchaseRequests',
            'stores',
            'storeId',
            'prDate',
            'prStatus',
            'search',
            'totalPrOrders',
            'totalPrUnits',
            'totalPrAmount',
            'pendingCount',
            'approvedCount',
            'completedCount',
            'rejectedCount',
            'availableProducts',
            'activeStoreId',
            'isSuperAdmin'
        ));
    }

    /**
     * Store new purchase order / request.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->can('Stock In Create') && !$user->can('Movement List') && !$user->can('Accounting Payable Manage') && !$user->can('Dashboard List') && !$user->can('Z1 App Purchase Request')) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'store_id' => 'required|exists:stores,id',
            'request_date' => 'required|date',
            'priority' => 'nullable|string|in:normal,urgent',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.custom_item_name' => 'nullable|string|max:255',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'items.*.quantity_requested' => 'nullable|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $datePrefix = date('Ymd', strtotime($validated['request_date']));
            $latestPr = PurchaseRequest::withoutGlobalScopes()
                ->where('pr_number', 'like', "PO-{$datePrefix}-%")
                ->orWhere('pr_number', 'like', "PR-{$datePrefix}-%")
                ->orderByDesc('id')
                ->first();

            $seq = 1;
            if ($latestPr && preg_match('/(?:PO|PR)-\d+-(\d+)/', $latestPr->pr_number, $matches)) {
                $seq = intval($matches[1]) + 1;
            }
            $prNumber = sprintf('PO-%s-%04d', $datePrefix, $seq);

            $pr = PurchaseRequest::create([
                'pr_number' => $prNumber,
                'store_id' => $validated['store_id'],
                'user_id' => $user->id,
                'request_date' => $validated['request_date'],
                'status' => 'pending',
                'priority' => $validated['priority'] ?? 'normal',
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!empty($validated['items'])) {
                foreach ($validated['items'] as $itemData) {
                    if (empty($itemData['product_id']) && empty($itemData['custom_item_name'])) {
                        continue;
                    }
                    PurchaseRequestItem::create([
                        'purchase_request_id' => $pr->id,
                        'product_id' => !empty($itemData['product_id']) ? $itemData['product_id'] : null,
                        'custom_item_name' => !empty($itemData['custom_item_name']) ? trim($itemData['custom_item_name']) : null,
                        'estimated_price' => $itemData['estimated_price'] ?? 0,
                        'quantity_requested' => $itemData['quantity_requested'] ?? 1,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('purchasing.orders.show', $pr->id)
                ->with('success', "Order barang #{$prNumber} berhasil dibuat. Silakan tambahkan barang yang ingin dipesan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('purchasing.orders.index')
                ->with('error', 'Gagal membuat order barang: ' . $e->getMessage());
        }
    }

    /**
     * Show detail page for a specific Purchase Order.
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user->can('Stock In Create') && !$user->can('Movement List') && !$user->can('Accounting Payable Manage') && !$user->can('Dashboard List') && !$user->can('Z1 App Purchase Request')) {
            abort(403, 'Akses ditolak.');
        }

        $pr = PurchaseRequest::withoutGlobalScopes()
            ->with(['items.product', 'store', 'user', 'approvedByUser', 'purchaseOrders'])
            ->findOrFail($id);

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->roles->contains(fn($r) => strtolower($r->name) === 'super admin') || $user->id === 1;
        $canApprovePr = ApprovalWorkflow::canUserApprove($user, 'purchase_request', 'approved');

        $workflowPr = ApprovalWorkflow::where('module', 'purchase_request')->where('status_to', 'approved')->first();
        $workflowRolesText = $workflowPr ? $workflowPr->getAuthorizedRoles()->pluck('name')->implode(', ') : 'Super Admin';

        $totalItemsCount = $pr->items->count();
        $totalUnits = $pr->items->sum('quantity_requested');
        $totalAmount = $pr->items->sum(function ($i) {
            return $i->item_price * $i->quantity_requested;
        });

        $storeProducts = Product::withoutGlobalScopes()->where('store_id', $pr->store_id)
            ->orderBy('name')
            ->get();

        return view('purchasing.orders.show', compact(
            'pr',
            'user',
            'isSuperAdmin',
            'canApprovePr',
            'workflowRolesText',
            'totalItemsCount',
            'totalUnits',
            'totalAmount',
            'storeProducts'
        ));
    }

    /**
     * Add a product item to an existing Purchase Order.
     */
    public function addItem(Request $request, $id)
    {
        $user = Auth::user();
        $pr = PurchaseRequest::withoutGlobalScopes()->findOrFail($id);

        if ($pr->status === 'completed' || $pr->status === 'approved') {
            return redirect()->route('purchasing.orders.show', $pr->id)
                ->with('error', 'Order yang sudah disetujui atau selesai tidak dapat diubah.');
        }

        $validated = $request->validate([
            'product_id' => 'nullable|exists:products,id',
            'custom_item_name' => 'nullable|string|max:255',
            'estimated_price' => 'nullable|numeric|min:0',
            'quantity_requested' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
        ]);

        if (empty($validated['product_id']) && empty($validated['custom_item_name'])) {
            return redirect()->route('purchasing.orders.show', $pr->id)
                ->with('error', 'Pilih produk toko atau ketik nama barang manual.');
        }

        $prItem = null;
        if (!empty($validated['product_id'])) {
            $existing = PurchaseRequestItem::where('purchase_request_id', $pr->id)
                ->where('product_id', $validated['product_id'])
                ->first();

            if ($existing) {
                $existing->increment('quantity_requested', $validated['quantity_requested']);
                if (!empty($validated['notes'])) {
                    $existing->update(['notes' => $validated['notes']]);
                }
                $prItem = $existing->fresh();
            } else {
                $product = Product::withoutGlobalScopes()->findOrFail($validated['product_id']);
                $prItem = PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => $product->id,
                    'custom_item_name' => null,
                    'estimated_price' => $product->purchase_price ?? $product->price ?? 0,
                    'quantity_requested' => $validated['quantity_requested'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        } else {
            $existing = PurchaseRequestItem::where('purchase_request_id', $pr->id)
                ->where('custom_item_name', trim($validated['custom_item_name']))
                ->first();

            if ($existing) {
                $existing->increment('quantity_requested', $validated['quantity_requested']);
                $prItem = $existing->fresh();
            } else {
                $prItem = PurchaseRequestItem::create([
                    'purchase_request_id' => $pr->id,
                    'product_id' => null,
                    'custom_item_name' => trim($validated['custom_item_name']),
                    'estimated_price' => $validated['estimated_price'] ?? 0,
                    'quantity_requested' => $validated['quantity_requested'],
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        }

        // Sinkronisasi otomatis ke Purchase Order (PO Supplier) yang aktif jika sudah dibuat dari PR ini
        if ($prItem) {
            $activePos = PurchaseOrder::withoutGlobalScopes()
                ->where('purchase_request_id', $pr->id)
                ->where('status', '!=', 'received')
                ->get();

            foreach ($activePos as $po) {
                $existingPoItem = PurchaseOrderItem::where('purchase_order_id', $po->id)
                    ->when($prItem->product_id, fn($q) => $q->where('product_id', $prItem->product_id))
                    ->when(!$prItem->product_id, fn($q) => $q->where('custom_item_name', $prItem->custom_item_name))
                    ->first();

                $unitPrice = $prItem->product?->purchase_price ?? $prItem->product?->price ?? $prItem->estimated_price ?? 0;

                if ($existingPoItem) {
                    $existingPoItem->increment('quantity_ordered', $validated['quantity_requested']);
                    $existingPoItem->update([
                        'subtotal' => $existingPoItem->quantity_ordered * $existingPoItem->unit_price
                    ]);
                } else {
                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'purchase_request_item_id' => $prItem->id,
                        'product_id' => $prItem->product_id,
                        'custom_item_name' => $prItem->custom_item_name,
                        'quantity_ordered' => $validated['quantity_requested'],
                        'quantity_received' => 0,
                        'unit_price' => $unitPrice,
                        'subtotal' => $validated['quantity_requested'] * $unitPrice,
                        'notes' => $prItem->notes,
                    ]);
                }
                $po->update(['total_amount' => $po->items()->sum('subtotal')]);
            }
        }

        return redirect()->route('purchasing.orders.show', $pr->id)
            ->with('success', 'Barang berhasil ditambahkan ke order #' . $pr->pr_number);
    }

    /**
     * Remove an item from Purchase Order.
     */
    public function destroyItem($id)
    {
        $item = PurchaseRequestItem::with('purchaseRequest')->findOrFail($id);
        $pr = $item->purchaseRequest;

        if ($pr->status === 'completed' || $pr->status === 'approved') {
            return redirect()->route('purchasing.orders.show', $pr->id)
                ->with('error', 'Order yang sudah disetujui atau selesai tidak dapat diubah.');
        }

        $item->delete();

        return redirect()->route('purchasing.orders.show', $pr->id)
            ->with('success', 'Barang berhasil dihapus dari order #' . $pr->pr_number);
    }

    /**
     * Update order status (Approve, Reject, or Complete).
     */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->can('Stock In Create') && !$user->can('Movement List') && !$user->can('Accounting Payable Manage') && !$user->can('Z1 App Purchase Request') && !$user->hasRole('Super Admin')) {
            abort(403, 'Akses ditolak.');
        }

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->roles->contains(fn($r) => strtolower($r->name) === 'super admin') || $user->id === 1;
        $canApprovePr = ApprovalWorkflow::canUserApprove($user, 'purchase_request', 'approved');

        $pr = PurchaseRequest::withoutGlobalScopes()->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected,completed',
            'admin_notes' => 'nullable|string|max:500',
        ]);

        if (in_array($validated['status'], ['approved', 'rejected']) && !$canApprovePr) {
            abort(403, 'Role Anda tidak memiliki wewenang Approval Workflow untuk menyetujui (approve) atau menolak Order Barang.');
        }

        if ($pr->status === 'approved' && $validated['status'] === 'pending' && !$canApprovePr) {
            abort(403, 'Role Anda tidak memiliki wewenang Approval Workflow untuk merevisi order yang sudah disetujui kembali ke Pending.');
        }

        $updateData = [
            'status' => $validated['status'],
        ];

        if (!empty($validated['admin_notes'])) {
            $updateData['admin_notes'] = $validated['admin_notes'];
        }

        if ($validated['status'] === 'approved') {
            $updateData['approved_by'] = $user->id;
            $updateData['approved_at'] = Carbon::now();
        } elseif ($validated['status'] === 'pending') {
            $updateData['approved_by'] = null;
            $updateData['approved_at'] = null;
        }

        $pr->update($updateData);

        $statusLabels = [
            'approved' => 'disetujui',
            'rejected' => 'ditolak',
            'completed' => 'diselesaikan (barang diterima)',
            'pending' => 'dikembalikan ke status Pending',
        ];

        return back()->with('success', "Order #{$pr->pr_number} berhasil " . ($statusLabels[$validated['status']] ?? 'diperbarui') . '.');
    }

    /**
     * Delete / Cancel Purchase Order.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $pr = PurchaseRequest::withoutGlobalScopes()->findOrFail($id);

        $isSuperAdmin = $user->hasRole('Super Admin') || $user->roles->contains(fn($r) => strtolower($r->name) === 'super admin') || $user->id === 1;

        $isPendingAndEmpty = $pr->status === 'pending' && $pr->items()->count() === 0;

        if (!$isPendingAndEmpty && !$isSuperAdmin && $pr->user_id !== $user->id && !$user->can('Stock In Create')) {
            abort(403, 'Akses ditolak.');
        }

        if ($pr->status === 'approved') {
            return redirect()->route('purchasing.orders.show', $pr->id)
                ->with('error', 'Order yang sudah disetujui (Approved) tidak dapat dihapus. Silakan revisi status ke Pending terlebih dahulu.');
        }

        if ($pr->status === 'completed' && !$isSuperAdmin) {
            return redirect()->route('purchasing.orders.index')
                ->with('error', 'Order yang sudah selesai tidak dapat dihapus.');
        }

        $prNumber = $pr->pr_number;
        $pr->items()->delete();
        $pr->delete();

        return redirect()->route('purchasing.orders.index')
            ->with('success', "Order barang #{$prNumber} berhasil dihapus.");
    }

    /**
     * Print View for Purchase Order.
     */
    public function print($id)
    {
        $pr = PurchaseRequest::withoutGlobalScopes()
            ->with(['items.product', 'store', 'user', 'approvedByUser', 'purchaseOrders'])
            ->findOrFail($id);

        $totalItemsCount = $pr->items->count();
        $totalUnits = $pr->items->sum('quantity_requested');
        $totalAmount = $pr->items->sum(function ($i) {
            return $i->item_price * $i->quantity_requested;
        });

        return view('purchasing.orders.print', compact(
            'pr',
            'totalItemsCount',
            'totalUnits',
            'totalAmount'
        ));
    }
}
