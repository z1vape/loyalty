<?php

namespace App\Http\Controllers;

use App\Models\MovementDate;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StockInTemplateExport;
use App\Imports\StockInMovementImport;

class MovementDateController extends Controller
{
    /**
     * Download Excel template for Stock In movements.
     */
    public function importTemplate()
    {
        Gate::authorize('Movement List');

        return Excel::download(
            new StockInTemplateExport,
            'template_stock_in_' . date('Ymd') . '.xlsx',
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Import products into an existing Movement Date (Stock In).
     */
    public function importProducts(Request $request, $id)
    {
        $movementDate = MovementDate::findOrFail($id);
        $type = $movementDate->type ?? 'in';

        if ($type !== 'in') {
            return back()->withErrors(['error' => 'Import produk Excel saat ini hanya didukung untuk Stock In.']);
        }

        if (!Gate::allows('Stock In Store') && !Gate::allows('Stock In Create') && !Gate::allows('Movement List')) {
            abort(403);
        }

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'user_id' => 'nullable|exists:users,id',
        ], [
            'file.required' => 'Pilih file Excel atau CSV terlebih dahulu.',
            'file.mimes' => 'Format file harus berupa Excel (.xlsx, .xls) atau CSV (.csv).',
            'file.max' => 'Ukuran file maksimal adalah 10 MB.',
        ]);

        try {
            $userId = $request->input('user_id', auth()->id()) ?: auth()->id();
            $import = new StockInMovementImport($movementDate, $userId);
            Excel::import($import, $request->file('file'));

            $importedCount = $import->getImportedCount();
            $skipped = $import->getSkippedRows();

            $dateLabel = \Carbon\Carbon::parse($movementDate->date)->translatedFormat('d F Y');
            $message = "Berhasil meng-import {$importedCount} produk sebagai DRAFT ke Stock In tanggal {$dateLabel}. Silakan periksa kembali sebelum melakukan Approve.";
            if (!empty($skipped)) {
                $message .= ' Catatan: ' . count($skipped) . ' baris dilewati (' . implode('; ', array_slice($skipped, 0, 3)) . (count($skipped) > 3 ? '...' : '') . ')';
            }

            return redirect()
                ->route('movement-dates.show', $movementDate->id)
                ->with('success', $message);

        } catch (\Throwable $e) {
            return back()->withErrors(['error' => 'Gagal meng-import file: ' . $e->getMessage()]);
        }
    }

    /**
     * Show movements for a specific date (dedicated page).
     */
    public function show(Request $request, $id)
    {
        Gate::authorize('Movement List');

        $movementDate = \App\Models\MovementDate::with('creator')->findOrFail($id);
        $formattedDate = \Illuminate\Support\Carbon::parse($movementDate->date)->format('Y-m-d');
        $movementType = $request->query('type', $movementDate->type ?? 'in');
        $movementDate->type = $movementType;

        $baseQuery = \App\Models\StockMovement::where('movement_date', $formattedDate)
            ->where('type', $movementType);

        // Optional filters
        if ($request->filled('product_id')) {
            $baseQuery->where('product_id', $request->product_id);
        }

        // Summary calculations across ALL records for this date (excluding cancelled movements)
        $summaryQuery = (clone $baseQuery)->where(function($q) {
            $q->whereNull('status')->orWhere('status', '!=', 'cancelled');
        });
        $grandTotalRecords = (int) (clone $summaryQuery)->count();
        $grandTotalQuantity = (int) (clone $summaryQuery)->sum('quantity');
        $grandTotalDiscount = (float) (clone $summaryQuery)->sum('discount');
        $grandTotalHarga = (float) (clone $summaryQuery)->sum(
            \Illuminate\Support\Facades\DB::raw("COALESCE(total_price, CASE WHEN type = 'in' THEN quantity * COALESCE(purchase_price, 0) ELSE (quantity * COALESCE(selling_price, 0)) - COALESCE(discount, 0) END, 0)")
        );
        $totalCancelledRecords = (int) (clone $baseQuery)->where('status', 'cancelled')->count();

        // Payment method breakdown for all movements on this date
        $paymentMethodsRaw = (clone $summaryQuery)
            ->select(
                \Illuminate\Support\Facades\DB::raw("COALESCE(NULLIF(TRIM(payment_method), ''), 'cash') as method"),
                \Illuminate\Support\Facades\DB::raw('COUNT(*) as total_count'),
                \Illuminate\Support\Facades\DB::raw("SUM(COALESCE(total_price, CASE WHEN type = 'in' THEN quantity * COALESCE(purchase_price, 0) ELSE (quantity * COALESCE(selling_price, 0)) - COALESCE(discount, 0) END, 0)) as total_amount")
            )
            ->groupBy(\Illuminate\Support\Facades\DB::raw("COALESCE(NULLIF(TRIM(payment_method), ''), 'cash')"))
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

        $movementsQuery = (clone $baseQuery)->with(['product', 'user'])->orderBy('created_at', 'desc');
        $movements = $movementsQuery->get();
        $products  = \App\Models\Product::orderBy('name')->get();
        $users     = \App\Models\User::orderBy('name')->get();
        $suppliers = Setting::where('type', 'supplier')->orderBy('value')->pluck('value')->unique()->values();

        return view('movements.date_show', compact(
            'movementDate',
            'movements',
            'products',
            'users',
            'suppliers',
            'grandTotalHarga',
            'grandTotalDiscount',
            'grandTotalQuantity',
            'grandTotalRecords',
            'totalCancelledRecords',
            'paymentBreakdown'
        ));
    }

    /**
     * Store a newly created movement date.
     */
    public function store(Request $request)
    {
        Gate::authorize('Movement List');

        $validated = $request->validate([
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'file' => 'nullable|file|mimes:xlsx,xls,csv|max:10240',
            'supplier_name' => 'nullable|string|max:150',
            'payment_method' => 'nullable|string|max:50',
            'due_date' => 'nullable|date',
        ]);

        $type = $request->input('type', 'in');
        if (!in_array($type, ['in', 'out'])) {
            $type = 'in';
        }

        $paymentMethod = $type === 'in' ? ($validated['payment_method'] ?? 'cash') : null;
        $isTempo = in_array(strtolower($paymentMethod ?? ''), ['tempo', 'hutang', 'credit']);
        $dueDate = ($type === 'in' && $isTempo) ? ($validated['due_date'] ?? null) : null;
        $supplierName = $type === 'in' ? ($validated['supplier_name'] ?? null) : null;

        // Auto-save supplier to Master Data: Pengaturan Sistem
        if ($type === 'in' && !empty($supplierName)) {
            $trimmedSupplier = trim($supplierName);
            if ($trimmedSupplier !== '') {
                Setting::firstOrCreate([
                    'type' => 'supplier',
                    'value' => $trimmedSupplier,
                ]);
            }
        }

        $movementDate = MovementDate::firstOrCreate(
            [
                'date' => $validated['date'],
                'type' => $type,
            ],
            [
                'created_by'     => auth()->id(),
                'notes'          => $validated['notes'] ?? null,
                'supplier_name'  => $supplierName,
                'payment_method' => $paymentMethod,
                'due_date'       => $dueDate,
            ]
        );

        if (!$movementDate->wasRecentlyCreated) {
            $updateData = [];
            if ($request->filled('notes')) {
                $updateData['notes'] = $validated['notes'];
            }
            if ($type === 'in') {
                if ($request->filled('supplier_name')) {
                    $updateData['supplier_name'] = $validated['supplier_name'];
                }
                if ($request->filled('payment_method')) {
                    $updateData['payment_method'] = $validated['payment_method'];
                }
                if ($request->filled('due_date')) {
                    $updateData['due_date'] = $validated['due_date'];
                }
            }
            if (!empty($updateData)) {
                $movementDate->update($updateData);
            }
        }

        $label = $type === 'out' ? 'Stock Out' : 'Stock In';
        $dateFormatted = \Carbon\Carbon::parse($validated['date'])->translatedFormat('d F Y');

        // If user uploaded an Excel file during creation of Stock In
        if ($request->hasFile('file') && $type === 'in') {
            try {
                $import = new StockInMovementImport($movementDate, auth()->id());
                Excel::import($import, $request->file('file'));

                $importedCount = $import->getImportedCount();
                $skipped = $import->getSkippedRows();

                $msg = "Date Movement {$label} {$dateFormatted} berhasil disiapkan dan {$importedCount} produk berhasil di-import sebagai DRAFT. Silakan periksa kembali sebelum melakukan Approve.";
                if (!empty($skipped)) {
                    $msg .= ' (Catatan: ' . count($skipped) . ' baris dilewati)';
                }

                return redirect()
                    ->route('movement-dates.show', array_filter(['id' => $movementDate->id, 'type' => $type]))
                    ->with('success', $msg);
            } catch (\Throwable $e) {
                return redirect()
                    ->route('movement-dates.show', array_filter(['id' => $movementDate->id, 'type' => $type]))
                    ->withErrors(['error' => "Date Movement dibuat, namun gagal meng-import file: " . $e->getMessage()]);
            }
        }

        return redirect()
            ->route('movement-dates.show', array_filter(['id' => $movementDate->id, 'type' => $type]))
            ->with('success', "Date Movement {$label} {$dateFormatted} berhasil disiapkan.");
    }

    /**
     * Update movement date attributes (notes, supplier_name, payment_method, due_date).
     */
    public function update(Request $request, $id)
    {
        Gate::authorize('Movement List');

        $movementDate = MovementDate::findOrFail($id);

        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
            'supplier_name' => 'nullable|string|max:150',
            'payment_method' => 'nullable|string|max:50',
            'due_date' => 'nullable|date',
        ]);

        $updateData = [];
        if ($request->has('notes')) {
            $updateData['notes'] = $validated['notes'];
        }
        if (($movementDate->type ?? 'in') === 'in') {
            if ($request->has('supplier_name')) {
                $trimmedSupplier = trim($validated['supplier_name'] ?? '');
                $updateData['supplier_name'] = $trimmedSupplier ?: null;
                if ($trimmedSupplier !== '') {
                    Setting::firstOrCreate([
                        'type' => 'supplier',
                        'value' => $trimmedSupplier,
                    ]);
                    // Sync to draft movements under this movement date
                    \App\Models\StockMovement::where('movement_date', \Carbon\Carbon::parse($movementDate->date)->format('Y-m-d'))
                        ->where('type', 'in')
                        ->where(function ($q) use ($movementDate) {
                            $q->whereNull('supplier_name')
                              ->orWhere('supplier_name', '')
                              ->orWhere('supplier_name', $movementDate->supplier_name);
                        })
                        ->update(['supplier_name' => $trimmedSupplier]);
                }
            }
            if ($request->has('payment_method')) {
                $pm = $validated['payment_method'] ?: 'cash';
                $updateData['payment_method'] = $pm;
                $isTempo = in_array(strtolower($pm), ['tempo', 'hutang', 'credit']);
                $updateData['due_date'] = $isTempo ? ($validated['due_date'] ?? null) : null;
            } elseif ($request->has('due_date')) {
                $updateData['due_date'] = $validated['due_date'];
            }
        }

        $movementDate->update($updateData);

        return back()->with('success', 'Informasi sesi movement berhasil diperbarui.');
    }

    /**
     * Delete a movement date.
     */
    public function destroy(Request $request, $id)
    {
        $movementDate = MovementDate::findOrFail($id);
        $type = $movementDate->type ?? 'in';

        if ($type === 'in') {
            if (!Gate::allows('Stock In Destroy') && !Gate::allows('Movement Destroy')) {
                abort(403);
            }
        } elseif ($type === 'out') {
            if (!Gate::allows('Stock Out Destroy') && !Gate::allows('Movement Destroy')) {
                abort(403);
            }
        } else {
            Gate::authorize('Movement Destroy');
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Revert all movements under this date
            $movements = \App\Models\StockMovement::where('movement_date', \Illuminate\Support\Carbon::parse($movementDate->date)->format('Y-m-d'))
                ->where('type', $type)
                ->get();

            foreach ($movements as $movement) {
                $product = \App\Models\Product::find($movement->product_id);
                if ($product && $movement->status !== 'draft') {
                    if ($movement->type === 'in') {
                        $product->stock = max(0, $product->stock - $movement->quantity);
                    } else {
                        $product->stock += $movement->quantity;
                    }
                    $product->save();
                }
                app(\App\Services\AccountingService::class)->purgeJournal('stock_movement', $movement->id);
                $movement->delete();
            }

            if ($type === 'out') {
                $refNumbers = $movements->pluck('reference_number')->unique();
                if ($refNumbers->isNotEmpty()) {
                    \App\Models\Sale::withoutGlobalScopes()->whereIn('reference_number', $refNumbers)->each(function($sale) {
                        $sale->items()->delete();
                        $sale->delete();
                    });
                }
            }

            $dateLabel = \Illuminate\Support\Carbon::parse($movementDate->date)->translatedFormat('d F Y');
            $typeLabel = $type === 'out' ? 'Stock Out' : 'Stock In';
            $movementDate->delete();

            \Illuminate\Support\Facades\DB::commit();

            return redirect()
                ->route('movements.index', request('type') ? ['type' => request('type')] : [])
                ->with('success', "Date Movement {$typeLabel} ({$dateLabel}) dan seluruh log terkait berhasil dihapus.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus date movement: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk delete movement dates.
     */
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
            \Illuminate\Support\Facades\DB::beginTransaction();

            if ($request->boolean('delete_all')) {
                $query = MovementDate::query();
                if ($type && in_array($type, ['in', 'out'])) {
                    $query->where('type', $type);
                }
                $dates = $query->get();
            } else {
                $validated = $request->validate([
                    'ids' => 'required|array|min:1',
                    'ids.*' => 'integer|exists:movement_dates,id',
                ], [
                    'ids.required' => 'Pilih minimal satu tanggal untuk dihapus.',
                ]);
                $dates = MovementDate::whereIn('id', $validated['ids'])->get();
            }

            $count = $dates->count();
            if ($count === 0) {
                return back()->with('error', 'Tidak ada data untuk dihapus.');
            }

            foreach ($dates as $movementDate) {
                $mType = $movementDate->type ?? 'in';
                $movements = \App\Models\StockMovement::where('movement_date', \Illuminate\Support\Carbon::parse($movementDate->date)->format('Y-m-d'))
                    ->where('type', $mType)
                    ->get();

                foreach ($movements as $movement) {
                    $product = \App\Models\Product::find($movement->product_id);
                    if ($product && $movement->status !== 'draft') {
                        if ($movement->type === 'in') {
                            $product->stock = max(0, $product->stock - $movement->quantity);
                        } else {
                            $product->stock += $movement->quantity;
                        }
                        $product->save();
                    }
                    app(\App\Services\AccountingService::class)->purgeJournal('stock_movement', $movement->id);
                    $movement->delete();
                }

                if ($mType === 'out') {
                    $refNumbers = $movements->pluck('reference_number')->unique();
                    if ($refNumbers->isNotEmpty()) {
                        \App\Models\Sale::withoutGlobalScopes()->whereIn('reference_number', $refNumbers)->each(function($sale) {
                            $sale->items()->delete();
                            $sale->delete();
                        });
                    }
                }

                $movementDate->delete();
            }

            \Illuminate\Support\Facades\DB::commit();

            return redirect()
                ->route('movements.index', $type ? ['type' => $type] : [])
                ->with('success', "Berhasil menghapus {$count} tanggal movement beserta penyesuaian stok.");

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menghapus date movements: ' . $e->getMessage()]);
        }
    }
}
