<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\LegacyDebt;
use App\Models\LegacyDebtPayment;
use App\Models\StockMovement;
use App\Models\SupplierPayment;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SupplierPaymentController extends Controller
{
    /**
     * Display a listing of supplier payables and bills.
     */
    public function index(Request $request)
    {
        Gate::authorize('Accounting Payable Manage');

        $tab = $request->query('tab', 'branch'); // 'branch' or 'legacy'
        $statusFilter = $request->query('status', 'unpaid'); // unpaid, pending, partial, paid, all
        $search = $request->query('search');

        $query = StockMovement::with(['product', 'user', 'supplierPayments.paymentAccount'])
            ->where('type', 'in')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'approved');
            })
            ->where(function ($q) {
                $q->whereIn('payment_method', ['tempo', 'hutang', 'credit'])
                    ->orWhereIn('payment_status', ['pending', 'partial']);
            });

        if ($statusFilter === 'unpaid') {
            $query->whereIn('payment_status', ['pending', 'partial']);
        } elseif (in_array($statusFilter, ['pending', 'partial', 'paid'])) {
            $query->where('payment_status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('supplier_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
            });
        }

        $rawMovements = $query->latest('movement_date')->latest('id')->get();

        // Group into Folders by: movement_date + supplier_name + (due_date)
        $groupedBills = $rawMovements->groupBy(function ($item) {
            $date = $item->movement_date ? Carbon::parse($item->movement_date)->format('Y-m-d') : 'no_date';
            $supplier = strtolower(trim($item->supplier_name ?: 'umum'));
            $due = $item->due_date ? Carbon::parse($item->due_date)->format('Y-m-d') : 'no_due';
            return "{$date}|{$supplier}|{$due}";
        })->map(function ($items, $key) {
            $first = $items->first();
            $totalBill = (float) $items->sum(fn($m) => (float) ($m->total_price ?: ($m->quantity * ($m->purchase_price ?? 0))));
            $totalPaid = (float) $items->sum(fn($m) => (float) ($m->paid_amount ?? 0));
            $remaining = max(0, $totalBill - $totalPaid);
            $isOverdue = $first->due_date && Carbon::parse($first->due_date)->lt(Carbon::today()) && $remaining > 0.01;

            $status = ($remaining <= 0.01) ? 'paid' : (($totalPaid > 0.01) ? 'partial' : 'pending');

            return (object) [
                'key' => md5($key),
                'movement_date' => $first->movement_date ?: $first->created_at,
                'supplier_name' => $first->supplier_name ?: 'Supplier Umum',
                'due_date' => $first->due_date,
                'items' => $items,
                'item_ids' => $items->pluck('id')->toArray(),
                'total_items' => $items->count(),
                'total_units' => $items->sum('quantity'),
                'total_bill' => $totalBill,
                'total_paid' => $totalPaid,
                'remaining_debt' => $remaining,
                'payment_status' => $status,
                'is_overdue' => $isOverdue,
                'first_id' => $first->id,
                'reference_numbers' => $items->pluck('reference_number')->filter()->unique()->values()->toArray(),
            ];
        })->values();

        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $bills = new \Illuminate\Pagination\LengthAwarePaginator(
            $groupedBills->forPage($page, $perPage),
            $groupedBills->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Calculate summary statistics for payables
        $allPayableMovements = StockMovement::where('type', 'in')
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', 'approved');
            })
            ->where(function ($q) {
                $q->whereIn('payment_method', ['tempo', 'hutang', 'credit'])
                    ->orWhereIn('payment_status', ['pending', 'partial']);
            })
            ->whereIn('payment_status', ['pending', 'partial'])
            ->get();

        $totalRemainingDebt = $allPayableMovements->sum(fn($m) => $m->remaining_debt);

        $today = Carbon::today();
        $overdueCount = $allPayableMovements->filter(fn($m) => $m->due_date && Carbon::parse($m->due_date)->lt($today))->count();
        $overdueAmount = $allPayableMovements->filter(fn($m) => $m->due_date && Carbon::parse($m->due_date)->lt($today))->sum(fn($m) => $m->remaining_debt);

        $totalPaid = SupplierPayment::sum('amount');

        // Legacy Debts (Hutang Warisan Bersama)
        $legacyDebts = LegacyDebt::with([
            'payments' => function ($q) {
                $q->latest('payment_date')->latest('id');
            },
            'payments.store',
            'payments.user',
            'payments.paymentAccount',
        ])->get();

        $totalLegacyInitial = (float) $legacyDebts->sum('initial_amount');
        $totalLegacyPaid = (float) $legacyDebts->sum('paid_amount');
        $totalLegacyRemaining = (float) $legacyDebts->sum('remaining_amount');

        // Rekap kontribusi cicilan per toko
        $legacyStoreContributions = DB::table('legacy_debt_payments')
            ->join('stores', 'legacy_debt_payments.store_id', '=', 'stores.id')
            ->select('stores.id', 'stores.name', DB::raw('SUM(legacy_debt_payments.amount) as total_contributed'), DB::raw('COUNT(legacy_debt_payments.id) as payment_count'))
            ->groupBy('stores.id', 'stores.name')
            ->orderByDesc('total_contributed')
            ->get();

        // Kas & Bank accounts for payment modals
        $paymentAccounts = $this->getPaymentAccounts();

        return view('accounting.payables.index', compact(
            'tab',
            'bills',
            'statusFilter',
            'search',
            'totalRemainingDebt',
            'overdueCount',
            'overdueAmount',
            'totalPaid',
            'legacyDebts',
            'totalLegacyInitial',
            'totalLegacyPaid',
            'totalLegacyRemaining',
            'legacyStoreContributions',
            'paymentAccounts'
        ));
    }

    /**
     * Show batch payment form for multiple stock movements in the same procurement folder.
     */
    public function payBatchForm(Request $request)
    {
        Gate::authorize('Accounting Payable Manage');

        $ids = explode(',', $request->query('ids', ''));
        $movements = StockMovement::with(['product', 'supplierPayments.paymentAccount'])
            ->whereIn('id', array_filter($ids))
            ->get();

        if ($movements->isEmpty()) {
            return redirect()->route('accounting.payables.index')
                ->with('error', 'Tidak ada faktur pembelian yang dipilih.');
        }

        $totalRemainingDebt = $movements->sum(fn($m) => $m->remaining_debt);
        if ($totalRemainingDebt <= 0) {
            return redirect()->route('accounting.payables.index')
                ->with('info', 'Seluruh faktur pengadaan ini telah lunas.');
        }

        $paymentAccounts = $this->getPaymentAccounts($movements->first()?->store_id);

        $first = $movements->first();

        return view('accounting.payables.pay_batch', compact('movements', 'totalRemainingDebt', 'paymentAccounts', 'first'));
    }

    /**
     * Store batch supplier payment across multiple stock movements.
     */
    public function storeBatchPayment(Request $request, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Payable Manage');

        $ids = explode(',', $request->input('movement_ids', ''));
        $movements = StockMovement::whereIn('id', array_filter($ids))
            ->orderBy('id', 'asc')
            ->get();

        if ($movements->isEmpty()) {
            return redirect()->route('accounting.payables.index')
                ->with('error', 'Tidak ada data pengadaan untuk dibayar.');
        }

        $totalRemainingDebt = $movements->sum(fn($m) => $m->remaining_debt);
        if ($totalRemainingDebt <= 0) {
            return redirect()->route('accounting.payables.index')
                ->with('error', 'Semua tagihan ini sudah lunas.');
        }

        $request->validate([
            'payment_date' => 'required|date',
            'payment_account_id' => 'required|exists:accounts,id',
            'amount' => "required|numeric|min:1|max:{$totalRemainingDebt}",
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa hutang (Rp ' . number_format($totalRemainingDebt, 0, ',', '.') . ').',
        ]);

        $storeId = $movements->first()->store_id;

        // Period lock check
        try {
            $accountingService->assertDateNotLocked($storeId, $request->payment_date);
        } catch (\LogicException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        DB::transaction(function () use ($request, $movements, $storeId, $accountingService) {
            $remainingPayment = (float) $request->amount;
            $baseRef = $request->reference_number ?: ('PAY-' . Carbon::parse($request->payment_date)->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));

            foreach ($movements as $idx => $movement) {
                if ($remainingPayment <= 0) break;

                $movRemaining = (float) $movement->remaining_debt;
                if ($movRemaining <= 0) continue;

                $allocatedAmount = min($remainingPayment, $movRemaining);
                $remainingPayment -= $allocatedAmount;

                $payment = SupplierPayment::create([
                    'store_id' => $storeId,
                    'stock_movement_id' => $movement->id,
                    'payment_date' => $request->payment_date,
                    'payment_account_id' => $request->payment_account_id,
                    'amount' => $allocatedAmount,
                    'reference_number' => count($movements) > 1 ? "{$baseRef}-" . ($idx + 1) : $baseRef,
                    'notes' => $request->notes,
                    'user_id' => auth()->id(),
                ]);

                // Update StockMovement
                $newPaid = (float) $movement->paid_amount + $allocatedAmount;
                $totalBill = (float) ($movement->total_price ?: ($movement->quantity * ($movement->purchase_price ?? 0)));
                $newStatus = ($newPaid >= ($totalBill - 0.01)) ? 'paid' : 'partial';

                $movement->update([
                    'paid_amount' => $newPaid,
                    'payment_status' => $newStatus,
                ]);

                // Record accounting journal
                $accountingService->recordSupplierPaymentJournal($payment);
            }
        });

        return redirect()->route('accounting.payables.index')
            ->with('success', "Pembayaran hutang pengadaan sebesar Rp " . number_format($request->amount, 0, ',', '.') . " berhasil dicatat.");
    }

    /**
     * Show payment form for a specific stock in purchase bill.
     */
    public function payForm(StockMovement $stockMovement)
    {
        Gate::authorize('Accounting Payable Manage');

        if ($stockMovement->remaining_debt <= 0) {
            return redirect()->route('accounting.payables.index')
                ->with('info', "Faktur pembelian #{$stockMovement->reference_number} telah lunas.");
        }

        $paymentAccounts = $this->getPaymentAccounts($stockMovement->store_id);

        $stockMovement->load(['product', 'supplierPayments.paymentAccount', 'supplierPayments.user']);

        return view('accounting.payables.pay', compact('stockMovement', 'paymentAccounts'));
    }

    /**
     * Store supplier debt repayment.
     */
    public function storePayment(Request $request, StockMovement $stockMovement, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Payable Manage');

        $remainingDebt = $stockMovement->remaining_debt;
        if ($remainingDebt <= 0) {
            return redirect()->route('accounting.payables.index')
                ->with('error', 'Faktur ini sudah lunas.');
        }

        $request->validate([
            'payment_date' => 'required|date',
            'payment_account_id' => 'required|exists:accounts,id',
            'amount' => "required|numeric|min:1|max:{$remainingDebt}",
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa hutang (Rp ' . number_format($remainingDebt, 0, ',', '.') . ').',
        ]);

        $storeId = $stockMovement->store_id;

        // Period lock check
        try {
            $accountingService->assertDateNotLocked($storeId, $request->payment_date);
        } catch (\LogicException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        DB::transaction(function () use ($request, $stockMovement, $storeId, $accountingService) {
            $payment = SupplierPayment::create([
                'store_id' => $storeId,
                'stock_movement_id' => $stockMovement->id,
                'payment_date' => $request->payment_date,
                'payment_account_id' => $request->payment_account_id,
                'amount' => $request->amount,
                'reference_number' => $request->reference_number ?: ('PAY-' . Carbon::parse($request->payment_date)->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4))),
                'notes' => $request->notes,
                'user_id' => auth()->id(),
            ]);

            // Update StockMovement paid_amount and payment_status
            $newPaid = (float) $stockMovement->paid_amount + (float) $request->amount;
            $totalBill = (float) ($stockMovement->total_price ?: ($stockMovement->quantity * ($stockMovement->purchase_price ?? 0)));
            $newStatus = ($newPaid >= ($totalBill - 0.01)) ? 'paid' : 'partial';

            $stockMovement->update([
                'paid_amount' => $newPaid,
                'payment_status' => $newStatus,
            ]);

            // Record journal entry
            $accountingService->recordSupplierPaymentJournal($payment);
        });

        return redirect()->route('accounting.payables.index')
            ->with('success', "Pembayaran hutang pembelian #{$stockMovement->reference_number} sebesar Rp " . number_format($request->amount, 0, ',', '.') . " berhasil dicatat.");
    }

    /**
     * Remove or cancel a supplier payment.
     */
    public function destroy(SupplierPayment $supplierPayment, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Payable Manage');

        $stockMovement = $supplierPayment->stockMovement;
        $storeId = $supplierPayment->store_id;

        // Period lock check
        try {
            $accountingService->assertDateNotLocked($storeId, $supplierPayment->payment_date->toDateString());
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        DB::transaction(function () use ($supplierPayment, $stockMovement, $accountingService) {
            $amount = (float) $supplierPayment->amount;

            // Purge journal
            $accountingService->purgeJournal('supplier_payment', $supplierPayment->id);

            // Delete payment record
            $supplierPayment->delete();

            // Recompute stock movement status
            if ($stockMovement) {
                $newPaid = max(0, (float) $stockMovement->paid_amount - $amount);
                $totalBill = (float) ($stockMovement->total_price ?: ($stockMovement->quantity * ($stockMovement->purchase_price ?? 0)));
                $newStatus = ($newPaid <= 0.01) ? 'pending' : (($newPaid >= ($totalBill - 0.01)) ? 'paid' : 'partial');

                $stockMovement->update([
                    'paid_amount' => $newPaid,
                    'payment_status' => $newStatus,
                ]);
            }
        });

        return redirect()->route('accounting.payables.index')
            ->with('success', "Riwayat pembayaran berhasil dibatalkan dan status hutang disesuaikan.");
    }

    /**
     * Store legacy debt installment payment from active store.
     */
    public function storeLegacyPayment(Request $request, LegacyDebt $legacyDebt, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Payable Manage');

        $storeId = session('active_store_id') ?? auth()->user()->stores()->first()?->id;
        if (!$storeId) {
            return redirect()->route('accounting.payables.index', ['tab' => 'legacy'])
                ->with('error', 'Silakan pilih toko aktif terlebih dahulu.');
        }

        $remaining = (float) $legacyDebt->remaining_amount;
        if ($remaining <= 0) {
            return redirect()->route('accounting.payables.index', ['tab' => 'legacy'])
                ->with('error', "Hutang warisan ke {$legacyDebt->supplier_name} sudah lunas.");
        }

        $request->validate([
            'payment_date' => 'required|date',
            'payment_account_id' => 'required|exists:accounts,id',
            'amount' => "required|numeric|min:1|max:{$remaining}",
            'payment_method' => 'nullable|string|in:cash,transfer,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ], [
            'amount.max' => 'Nominal pembayaran tidak boleh melebihi sisa hutang (Rp ' . number_format($remaining, 0, ',', '.') . ').',
        ]);

        // Period lock check
        try {
            $accountingService->assertDateNotLocked($storeId, $request->input('payment_date'));
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        DB::transaction(function () use ($request, $legacyDebt, $storeId, $accountingService) {
            $payment = LegacyDebtPayment::create([
                'legacy_debt_id' => $legacyDebt->id,
                'store_id' => $storeId,
                'user_id' => auth()->id(),
                'payment_date' => $request->input('payment_date'),
                'amount' => $request->input('amount'),
                'payment_account_id' => $request->input('payment_account_id'),
                'payment_method' => $request->input('payment_method', 'transfer'),
                'reference_number' => $request->input('reference_number'),
                'notes' => $request->input('notes'),
            ]);

            $legacyDebt->recalculateTotals();

            $accountingService->recordLegacyDebtPaymentJournal($payment);
        });

        return redirect()->route('accounting.payables.index', ['tab' => 'legacy'])
            ->with('success', "Pembayaran cicilan utang warisan ke {$legacyDebt->supplier_name} berhasil dicatat!");
    }

    /**
     * Delete legacy debt installment payment and reverse journal.
     */
    public function destroyLegacyPayment(LegacyDebtPayment $legacyPayment, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Payable Manage');

        $storeId = $legacyPayment->store_id;

        // Period lock check
        try {
            $accountingService->assertDateNotLocked($storeId, $legacyPayment->payment_date->toDateString());
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        $legacyDebt = $legacyPayment->legacyDebt;

        DB::transaction(function () use ($legacyPayment, $legacyDebt, $accountingService) {
            $accountingService->purgeJournal('legacy_debt_payment', $legacyPayment->id);
            $legacyPayment->delete();

            if ($legacyDebt) {
                $legacyDebt->recalculateTotals();
            }
        });

        return redirect()->route('accounting.payables.index', ['tab' => 'legacy'])
            ->with('success', "Riwayat pembayaran cicilan berhasil dibatalkan dan saldo hutang warisan disesuaikan.");
    }

    /**
     * Get and balance-enrich Kas & Bank payment accounts for active store.
     * When auto_cash_to_bank is enabled, Bank Toko balance matches the real-time store ending balance.
     */
    private function getPaymentAccounts(?int $storeId = null)
    {
        $activeStoreId = $storeId ?? session('active_store_id') ?? auth()->user()->stores()->first()?->id;
        $activeStore = $activeStoreId ? \App\Models\Store::find($activeStoreId) : null;

        $accounts = Account::active()
            ->where('type', 'asset')
            ->where(function ($q) {
                $q->where('category', 'Kas & Bank')
                    ->orWhereIn('code', ['1101', '1102']);
            })
            ->orderBy('code')
            ->get();

        foreach ($accounts as $acc) {
            if ($acc->code === '1102' && $activeStore && ($activeStore->auto_cash_to_bank ?? true)) {
                $acc->current_balance = (float) $activeStore->ending_balance;
            } elseif ($acc->code === '1101' && $activeStore && ($activeStore->auto_cash_to_bank ?? true)) {
                $acc->current_balance = 0.0;
            } else {
                $acc->current_balance = $acc->calculateBalance();
            }
        }

        return $accounts;
    }
}
