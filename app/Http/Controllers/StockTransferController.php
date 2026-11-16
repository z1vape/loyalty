<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\MovementDate;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Store;
use App\Services\AccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    protected function authorizeTransferAccess(): void
    {
        $user = Auth::user();
        if (
            !$user->hasRole('Super Admin')
            && !$user->can('Inventory List')
            && !$user->can('Movement List')
            && !$user->can('Stock Out Create')
            && !$user->can('Stock In Create')
            && !$user->can('Dashboard List')
        ) {
            abort(403, 'Anda tidak memiliki akses ke Modul Mutasi Antar Cabang.');
        }
    }

    protected function authorizeStoreInvolvement(StockTransfer $transfer): void
    {
        $user = Auth::user();
        if ($user->hasRole('Super Admin')) {
            return;
        }

        $userStoreIds = $user->stores()->pluck('stores.id')->map(fn ($id) => (int) $id)->toArray();
        if (!empty($userStoreIds) && !in_array((int) $transfer->from_store_id, $userStoreIds, true) && !in_array((int) $transfer->to_store_id, $userStoreIds, true)) {
            abort(403, 'Anda tidak memiliki akses ke dokumen mutasi cabang ini.');
        }
    }

    /**
     * Display a listing of Stock Transfers.
     */
    public function index(Request $request)
    {
        $this->authorizeTransferAccess();

        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $userStoreIds = $user->stores()->pluck('stores.id')->toArray();
        $activeStoreId = session('active_store_id') ?? ($userStoreIds[0] ?? Store::first()?->id);

        $stores = Store::orderBy('name')->get();

        $storeId = $request->input('store_id', 'all');
        $direction = $request->input('direction', 'all'); // all, outgoing, incoming
        $status = $request->input('status', 'all');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');

        $baseQuery = StockTransfer::with(['items.sourceProduct', 'fromStore', 'toStore', 'creator', 'sender', 'receiver']);

        if (!$isSuperAdmin && !empty($userStoreIds)) {
            if (!empty($storeId) && $storeId !== 'all' && in_array((int) $storeId, array_map('intval', $userStoreIds), true)) {
                $baseQuery->forStore((int) $storeId);
            } else {
                $baseQuery->forStore($userStoreIds);
            }
        } elseif (!empty($storeId) && $storeId !== 'all') {
            $baseQuery->forStore((int) $storeId);
        }

        $perspectiveStoreId = (!empty($storeId) && $storeId !== 'all') ? (int) $storeId : (int) $activeStoreId;
        if ($direction === 'outgoing' && $perspectiveStoreId) {
            $baseQuery->where('from_store_id', $perspectiveStoreId);
        } elseif ($direction === 'incoming' && $perspectiveStoreId) {
            $baseQuery->where('to_store_id', $perspectiveStoreId);
        }

        // Metrics query before status/search filters
        $metricsQuery = clone $baseQuery;
        if (!empty($startDate) && !empty($endDate)) {
            $metricsQuery->whereBetween('transfer_date', [$startDate, $endDate]);
        }

        $allTransfers = $metricsQuery->get();
        $totalTransferCount = $allTransfers->count();
        $totalTransferCost = $allTransfers->whereIn('status', ['in_transit', 'received'])->sum('total_cost');
        $draftCount = $allTransfers->where('status', 'draft')->count();
        $inTransitCount = $allTransfers->where('status', 'in_transit')->count();
        $receivedCount = $allTransfers->where('status', 'received')->count();

        if (!empty($status) && $status !== 'all') {
            $baseQuery->where('status', $status);
        }

        if (!empty($startDate) && !empty($endDate)) {
            $baseQuery->whereBetween('transfer_date', [$startDate, $endDate]);
        }

        if (!empty($search)) {
            $baseQuery->where(function ($q) use ($search) {
                $q->where('transfer_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('fromStore', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('toStore', fn ($sq) => $sq->where('name', 'like', "%{$search}%"));
            });
        }

        $transfers = $baseQuery->orderByDesc('transfer_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('stock_transfers.index', compact(
            'transfers',
            'stores',
            'storeId',
            'direction',
            'status',
            'startDate',
            'endDate',
            'search',
            'totalTransferCount',
            'totalTransferCost',
            'draftCount',
            'inTransitCount',
            'receivedCount',
            'activeStoreId'
        ));
    }

    /**
     * Show form to create a new Stock Transfer.
     */
    public function create(Request $request)
    {
        $this->authorizeTransferAccess();

        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('Super Admin');
        $accessibleStores = $isSuperAdmin ? Store::orderBy('name')->get() : $user->stores()->orderBy('name')->get();
        if ($accessibleStores->isEmpty()) {
            $accessibleStores = Store::orderBy('name')->get();
        }
        $allStores = Store::orderBy('name')->get();

        $selectedFromStoreId = (int) ($request->input('from_store_id') ?: (session('active_store_id') ?: $accessibleStores->first()?->id));
        $storeProducts = Product::withoutGlobalScopes()
            ->where('store_id', $selectedFromStoreId)
            ->orderBy('name')
            ->get();

        return view('stock_transfers.create', compact(
            'accessibleStores',
            'allStores',
            'selectedFromStoreId',
            'storeProducts'
        ));
    }

    /**
     * Store a newly created Stock Transfer (as draft or immediately sent).
     */
    public function store(Request $request, AccountingService $accountingService)
    {
        $this->authorizeTransferAccess();

        $user = Auth::user();

        $validated = $request->validate([
            'from_store_id' => 'required|exists:stores,id',
            'to_store_id' => 'required|exists:stores,id|different:from_store_id',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'send_immediately' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.notes' => 'nullable|string|max:255',
        ], [
            'to_store_id.different' => 'Cabang tujuan harus berbeda dengan cabang pengirim.',
        ]);

        $fromStoreId = (int) $validated['from_store_id'];
        $toStoreId = (int) $validated['to_store_id'];

        DB::beginTransaction();
        try {
            $datePrefix = date('Ymd', strtotime($validated['transfer_date']));
            $latestTransfer = StockTransfer::where('transfer_number', 'like', "TRF-{$datePrefix}-%")
                ->orderByDesc('id')
                ->first();

            $seq = 1;
            if ($latestTransfer && preg_match('/TRF-\d+-(\d+)/', $latestTransfer->transfer_number, $matches)) {
                $seq = intval($matches[1]) + 1;
            }
            $transferNumber = sprintf('TRF-%s-%04d', $datePrefix, $seq);

            // Merge duplicate product rows if any
            $groupedItems = [];
            foreach ($validated['items'] as $itemData) {
                $pid = (int) $itemData['product_id'];
                $qty = (int) $itemData['quantity'];
                if (!isset($groupedItems[$pid])) {
                    $groupedItems[$pid] = [
                        'product_id' => $pid,
                        'quantity' => $qty,
                        'notes' => $itemData['notes'] ?? null,
                    ];
                } else {
                    $groupedItems[$pid]['quantity'] += $qty;
                }
            }

            $transfer = StockTransfer::create([
                'transfer_number' => $transferNumber,
                'from_store_id' => $fromStoreId,
                'to_store_id' => $toStoreId,
                'transfer_date' => $validated['transfer_date'],
                'status' => 'draft',
                'total_cost' => 0,
                'notes' => $validated['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $totalCost = 0;
            foreach ($groupedItems as $itemData) {
                $product = Product::withoutGlobalScopes()
                    ->where('store_id', $fromStoreId)
                    ->find($itemData['product_id']);

                if (!$product) {
                    throw new \RuntimeException('Produk tidak ditemukan di cabang pengirim.');
                }

                if ($product->stock < $itemData['quantity']) {
                    throw new \RuntimeException("Stok produk {$product->name} di cabang asal tidak mencukupi (tersedia: {$product->stock}, diminta: {$itemData['quantity']}).");
                }

                $unitCost = (float) ($product->purchase_price ?? 0);
                $subtotal = $itemData['quantity'] * $unitCost;
                $totalCost += $subtotal;

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'source_product_id' => $product->id,
                    'quantity_requested' => $itemData['quantity'],
                    'quantity_sent' => 0,
                    'quantity_received' => 0,
                    'unit_cost' => $unitCost,
                    'subtotal_cost' => $subtotal,
                    'notes' => $itemData['notes'],
                ]);
            }

            $transfer->update(['total_cost' => $totalCost]);

            if (!empty($validated['send_immediately'])) {
                $this->executeSendTransfer($transfer, $user->id, $accountingService);
            }

            DB::commit();

            $msg = !empty($validated['send_immediately'])
                ? "Surat Jalan #{$transferNumber} berhasil dibuat dan dikirim (In-Transit)."
                : "Draft Surat Jalan #{$transferNumber} berhasil dibuat.";

            return redirect()->route('stock-transfers.show', $transfer->id)
                ->with('success', $msg);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat mutasi stok: ' . $e->getMessage());
        }
    }

    /**
     * Display a specific Stock Transfer.
     */
    public function show($id)
    {
        $this->authorizeTransferAccess();

        $transfer = StockTransfer::with([
            'items.sourceProduct',
            'items.destinationProduct',
            'fromStore',
            'toStore',
            'creator',
            'sender',
            'receiver',
            'stockMovements.product',
        ])->findOrFail($id);

        $this->authorizeStoreInvolvement($transfer);

        $journals = JournalEntry::withoutGlobalScopes()
            ->with(['items.account', 'store'])
            ->whereIn('source_type', ['stock_transfer_out', 'stock_transfer_in'])
            ->where('source_id', $transfer->id)
            ->orderBy('id')
            ->get();

        return view('stock_transfers.show', compact('transfer', 'journals'));
    }

    /**
     * Send the Stock Transfer from the source store (Draft -> In-Transit).
     */
    public function send(Request $request, $id, AccountingService $accountingService)
    {
        $this->authorizeTransferAccess();

        $transfer = StockTransfer::with(['items.sourceProduct', 'fromStore', 'toStore'])->findOrFail($id);
        $this->authorizeStoreInvolvement($transfer);

        if ($transfer->status !== 'draft') {
            return redirect()->route('stock-transfers.show', $transfer->id)
                ->with('error', 'Hanya dokumen berstatus Draft yang dapat dikirim.');
        }

        DB::beginTransaction();
        try {
            $lockedTransfer = StockTransfer::with(['items', 'fromStore', 'toStore'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($lockedTransfer->status !== 'draft') {
                throw new \RuntimeException('Status dokumen sudah berubah.');
            }

            $this->executeSendTransfer($lockedTransfer, Auth::id(), $accountingService, $request->input('items', []));

            DB::commit();

            return redirect()->route('stock-transfers.show', $lockedTransfer->id)
                ->with('success', "Barang untuk Surat Jalan #{$lockedTransfer->transfer_number} telah dikirim (In-Transit) dan stok cabang asal berhasil dipotong.");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('stock-transfers.show', $transfer->id)
                ->with('error', 'Gagal mengirim barang: ' . $e->getMessage());
        }
    }

    /**
     * Internal helper to execute sending of a draft transfer within an active DB transaction.
     */
    protected function executeSendTransfer(
        StockTransfer $transfer,
        int $userId,
        AccountingService $accountingService,
        array $itemOverrides = []
    ): void {
        $sentDateStr = Carbon::now()->toDateString();
        $accountingService->assertDateNotLocked((int) $transfer->from_store_id, $sentDateStr);

        $toStoreName = $transfer->toStore?->name ?? "Cabang #{$transfer->to_store_id}";

        $movementDate = MovementDate::withoutGlobalScopes()->firstOrCreate(
            [
                'date' => $sentDateStr,
                'store_id' => $transfer->from_store_id,
                'type' => 'out',
            ],
            [
                'created_by' => $userId,
                'notes' => "Pengiriman Mutasi #{$transfer->transfer_number} ke {$toStoreName}",
            ]
        );

        $totalSentCost = 0;

        foreach ($transfer->items as $item) {
            $sourceProduct = Product::withoutGlobalScopes()
                ->where('store_id', $transfer->from_store_id)
                ->lockForUpdate()
                ->find($item->source_product_id);

            if (!$sourceProduct) {
                throw new \RuntimeException("Produk asal #{$item->source_product_id} tidak ditemukan.");
            }

            $qtySent = isset($itemOverrides[$item->id]['quantity_sent'])
                ? (int) $itemOverrides[$item->id]['quantity_sent']
                : (int) $item->quantity_requested;

            if ($qtySent <= 0) {
                throw new \RuntimeException("Kuantitas kirim untuk {$sourceProduct->name} harus lebih dari 0.");
            }

            if ($sourceProduct->stock < $qtySent) {
                throw new \RuntimeException("Stok produk {$sourceProduct->name} di cabang asal tidak mencukupi (tersedia: {$sourceProduct->stock}, akan dikirim: {$qtySent}).");
            }

            $unitCost = (float) ($sourceProduct->purchase_price > 0 ? $sourceProduct->purchase_price : $item->unit_cost);
            $subtotalCost = round($qtySent * $unitCost, 2);
            $totalSentCost += $subtotalCost;

            $item->update([
                'quantity_sent' => $qtySent,
                'unit_cost' => $unitCost,
                'subtotal_cost' => $subtotalCost,
            ]);

            $sourceProduct->decrement('stock', $qtySent);

            StockMovement::withoutGlobalScopes()->create([
                'product_id' => $sourceProduct->id,
                'type' => 'out',
                'quantity' => $qtySent,
                'purchase_price' => $unitCost,
                'selling_price' => $unitCost,
                'total_price' => $subtotalCost,
                'reference_number' => $transfer->transfer_number,
                'store_id' => $transfer->from_store_id,
                'user_id' => $userId,
                'movement_date' => $sentDateStr,
                'movement_date_id' => $movementDate->id,
                'status' => 'approved',
                'notes' => "Mutasi Keluar #{$transfer->transfer_number} ke {$toStoreName}",
            ]);
        }

        $transfer->update([
            'status' => 'in_transit',
            'sent_at' => Carbon::now(),
            'sent_by' => $userId,
            'total_cost' => $totalSentCost,
        ]);

        $accountingService->recordStockTransferOutJournal($transfer->fresh(['items', 'toStore']));
    }

    /**
     * Receive the Stock Transfer at the destination store (In-Transit -> Received).
     */
    public function receive(Request $request, $id, AccountingService $accountingService)
    {
        $this->authorizeTransferAccess();

        $transfer = StockTransfer::with(['items.sourceProduct', 'fromStore', 'toStore'])->findOrFail($id);
        $this->authorizeStoreInvolvement($transfer);

        if ($transfer->status !== 'in_transit') {
            return redirect()->route('stock-transfers.show', $transfer->id)
                ->with('error', 'Hanya pengiriman berstatus Dalam Perjalanan (In-Transit) yang dapat diterima.');
        }

        $validated = $request->validate([
            'received_date' => 'nullable|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'nullable|array',
            'items.*.quantity_received' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $lockedTransfer = StockTransfer::with(['items.sourceProduct', 'fromStore', 'toStore'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($lockedTransfer->status !== 'in_transit') {
                throw new \RuntimeException('Status dokumen sudah berubah.');
            }

            $receivedDateStr = !empty($validated['received_date'])
                ? Carbon::parse($validated['received_date'])->toDateString()
                : Carbon::now()->toDateString();

            $accountingService->assertDateNotLocked((int) $lockedTransfer->to_store_id, $receivedDateStr);

            $fromStoreName = $lockedTransfer->fromStore?->name ?? "Cabang #{$lockedTransfer->from_store_id}";

            $movementDate = MovementDate::withoutGlobalScopes()->firstOrCreate(
                [
                    'date' => $receivedDateStr,
                    'store_id' => $lockedTransfer->to_store_id,
                    'type' => 'in',
                ],
                [
                    'created_by' => Auth::id(),
                    'supplier_name' => $fromStoreName,
                    'notes' => "Penerimaan Mutasi #{$lockedTransfer->transfer_number} dari {$fromStoreName}",
                ]
            );

            $totalReceivedUnits = 0;

            foreach ($lockedTransfer->items as $item) {
                $sourceProduct = $item->sourceProduct;
                if (!$sourceProduct) {
                    throw new \RuntimeException("Data produk asal untuk item #{$item->id} tidak ditemukan.");
                }

                $qtyReceived = isset($validated['items'][$item->id]['quantity_received'])
                    ? (int) $validated['items'][$item->id]['quantity_received']
                    : (int) $item->quantity_sent;

                if ($qtyReceived < 0 || $qtyReceived > $item->quantity_sent) {
                    throw new \RuntimeException("Kuantitas diterima untuk {$sourceProduct->name} tidak boleh melebihi kuantitas dikirim ({$item->quantity_sent}).");
                }

                // Match or auto-create product in destination store
                $destQuery = Product::withoutGlobalScopes()
                    ->where('store_id', $lockedTransfer->to_store_id)
                    ->lockForUpdate();

                if (!empty($sourceProduct->sku)) {
                    $destProduct = (clone $destQuery)->where('sku', $sourceProduct->sku)->first();
                } else {
                    $destProduct = (clone $destQuery)
                        ->where('name', $sourceProduct->name)
                        ->where('variant', $sourceProduct->variant)
                        ->first();
                }

                if (!$destProduct) {
                    $destProduct = Product::withoutGlobalScopes()->create([
                        'sku' => $sourceProduct->sku,
                        'name' => $sourceProduct->name,
                        'description' => $sourceProduct->description,
                        'purchase_price' => $item->unit_cost,
                        'selling_price' => $sourceProduct->selling_price,
                        'stock' => 0,
                        'min_stock' => $sourceProduct->min_stock ?? 5,
                        'category' => $sourceProduct->category,
                        'variant' => $sourceProduct->variant,
                        'photo' => $sourceProduct->photo,
                        'store_id' => $lockedTransfer->to_store_id,
                    ]);
                }

                $item->update([
                    'destination_product_id' => $destProduct->id,
                    'quantity_received' => $qtyReceived,
                ]);

                if ($qtyReceived > 0) {
                    $totalReceivedUnits += $qtyReceived;
                    $unitCost = (float) $item->unit_cost;
                    $lineTotal = round($qtyReceived * $unitCost, 2);

                    // Update Moving Average Cost and increment destination stock
                    $destProduct->applyStockInMovingAverage((float) $qtyReceived, $unitCost);

                    StockMovement::withoutGlobalScopes()->create([
                        'product_id' => $destProduct->id,
                        'type' => 'in',
                        'quantity' => $qtyReceived,
                        'purchase_price' => $unitCost,
                        'selling_price' => $destProduct->selling_price ?? 0,
                        'total_price' => $lineTotal,
                        'supplier_name' => $fromStoreName,
                        'reference_number' => $lockedTransfer->transfer_number,
                        'store_id' => $lockedTransfer->to_store_id,
                        'user_id' => Auth::id(),
                        'movement_date' => $receivedDateStr,
                        'movement_date_id' => $movementDate->id,
                        'status' => 'approved',
                        'notes' => "Mutasi Masuk #{$lockedTransfer->transfer_number} dari {$fromStoreName}" . (!empty($validated['notes']) ? '. ' . $validated['notes'] : ''),
                    ]);
                }
            }

            if ($totalReceivedUnits <= 0) {
                throw new \RuntimeException('Total kuantitas barang yang diterima harus lebih dari 0.');
            }

            $updatedNotes = $lockedTransfer->notes;
            if (!empty($validated['notes'])) {
                $updatedNotes = trim(($updatedNotes ? $updatedNotes . "\n" : '') . 'Catatan Penerima: ' . $validated['notes']);
            }

            $lockedTransfer->update([
                'status' => 'received',
                'received_at' => Carbon::setTestNow() ? Carbon::now() : Carbon::parse($receivedDateStr)->setTimeFrom(Carbon::now()),
                'received_by' => Auth::id(),
                'notes' => $updatedNotes,
            ]);

            $accountingService->recordStockTransferInJournal($lockedTransfer->fresh(['items', 'fromStore']));

            DB::commit();

            return redirect()->route('stock-transfers.show', $lockedTransfer->id)
                ->with('success', "Sebanyak {$totalReceivedUnits} unit barang berhasil diterima di {$lockedTransfer->toStore?->name}. Stok, HPP (Moving Average), dan Jurnal otomatis diperbarui!");
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('stock-transfers.show', $transfer->id)
                ->with('error', 'Gagal memproses penerimaan barang: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a Draft Stock Transfer.
     */
    public function cancel($id)
    {
        $this->authorizeTransferAccess();

        $transfer = StockTransfer::findOrFail($id);
        $this->authorizeStoreInvolvement($transfer);

        if ($transfer->status !== 'draft') {
            return redirect()->route('stock-transfers.show', $transfer->id)
                ->with('error', 'Hanya dokumen berstatus Draft yang dapat dibatalkan.');
        }

        $transfer->update(['status' => 'cancelled']);

        return redirect()->route('stock-transfers.show', $transfer->id)
            ->with('success', "Surat Jalan #{$transfer->transfer_number} telah dibatalkan.");
    }

    /**
     * Delete a Draft or Cancelled Stock Transfer.
     */
    public function destroy($id)
    {
        $this->authorizeTransferAccess();

        $transfer = StockTransfer::findOrFail($id);
        $this->authorizeStoreInvolvement($transfer);

        if (!in_array($transfer->status, ['draft', 'cancelled'], true)) {
            return redirect()->route('stock-transfers.index')
                ->with('error', 'Dokumen mutasi yang sudah dikirim atau diterima tidak dapat dihapus.');
        }

        $number = $transfer->transfer_number;
        $transfer->items()->delete();
        $transfer->delete();

        return redirect()->route('stock-transfers.index')
            ->with('success', "Dokumen Mutasi #{$number} berhasil dihapus.");
    }

    /**
     * Print View for Stock Transfer (Surat Jalan Mutasi Antar Cabang).
     */
    public function print($id)
    {
        $this->authorizeTransferAccess();

        $transfer = StockTransfer::with([
            'items.sourceProduct',
            'items.destinationProduct',
            'fromStore',
            'toStore',
            'creator',
            'sender',
            'receiver',
        ])->findOrFail($id);

        $this->authorizeStoreInvolvement($transfer);

        return view('stock_transfers.print', compact('transfer'));
    }
}
