<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FundTransfer;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class FundTransferController extends Controller
{
    /**
     * Display a listing of fund transfers.
     */
    public function index(Request $request)
    {
        Gate::authorize('Accounting Transfer Manage');

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->toDateString());

        $transfers = FundTransfer::with(['fromAccount', 'toAccount', 'user'])
            ->whereBetween('transfer_date', [$startDate, $endDate])
            ->latest('transfer_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $totalTransferred = FundTransfer::whereBetween('transfer_date', [$startDate, $endDate])->sum('amount');

        return view('accounting.transfers.index', compact(
            'transfers',
            'startDate',
            'endDate',
            'totalTransferred'
        ));
    }

    /**
     * Show the form for creating a new fund transfer.
     */
    public function create()
    {
        Gate::authorize('Accounting Transfer Manage');

        $accounts = Account::active()
            ->where('type', 'asset')
            ->where(function ($q) {
                $q->where('category', 'Kas & Bank')
                    ->orWhereIn('code', ['1101', '1102', '1103']);
            })
            ->orderBy('code')
            ->get();

        // Calculate balances for convenience
        $store = \App\Models\Store::find($storeId);
        foreach ($accounts as $acc) {
            if ($acc->code === '1102' && $store) {
                $acc->current_balance = (float) $store->ending_balance;
            } elseif ($acc->code === '1101' && $store && ($store->auto_cash_to_bank || !empty($store->bank_name))) {
                $acc->current_balance = 0.0;
            } else {
                $cutOff = $store?->initial_balance_date ? Carbon::parse($store->initial_balance_date)->toDateString() : null;
                $acc->current_balance = (float) $acc->calculateBalance($cutOff);
            }
        }

        $suggestedNumber = 'TRF-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        return view('accounting.transfers.create', compact('accounts', 'suggestedNumber'));
    }

    /**
     * Store a newly created fund transfer in storage.
     */
    public function store(Request $request, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Transfer Manage');

        $request->validate([
            'transfer_date' => 'required|date',
            'from_account_id' => 'required|exists:accounts,id',
            'to_account_id' => 'required|exists:accounts,id|different:from_account_id',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ], [
            'to_account_id.different' => 'Rekening tujuan harus berbeda dengan rekening asal.',
            'amount.min' => 'Jumlah transfer minimal Rp 1.',
        ]);

        $storeId = session('active_store_id') ?? auth()->user()->stores()->first()?->id;
        if (!$storeId) {
            return back()->withInput()->with('error', 'Silakan pilih toko aktif terlebih dahulu.');
        }

        // Period lock check
        try {
            $accountingService->assertDateNotLocked($storeId, $request->transfer_date);
        } catch (\LogicException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $transferNumber = 'TRF-' . Carbon::parse($request->transfer_date)->format('Ymd') . '-' . strtoupper(Str::random(4));

        DB::transaction(function () use ($request, $storeId, $transferNumber, $accountingService) {
            $transfer = FundTransfer::create([
                'store_id' => $storeId,
                'transfer_number' => $transferNumber,
                'transfer_date' => $request->transfer_date,
                'from_account_id' => $request->from_account_id,
                'to_account_id' => $request->to_account_id,
                'amount' => $request->amount,
                'notes' => $request->notes,
                'user_id' => auth()->id(),
            ]);

            $accountingService->recordFundTransferJournal($transfer);
        });

        return redirect()->route('accounting.transfers.index')
            ->with('success', "Transfer kas/bank {$transferNumber} berhasil dicatat dan dijurnal.");
    }

    /**
     * Remove the specified fund transfer.
     */
    public function destroy(FundTransfer $transfer, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Transfer Manage');

        $storeId = $transfer->store_id;

        // Period lock check
        try {
            $accountingService->assertDateNotLocked($storeId, $transfer->transfer_date->toDateString());
        } catch (\LogicException $e) {
            return back()->with('error', $e->getMessage());
        }

        DB::transaction(function () use ($transfer, $accountingService) {
            $accountingService->purgeJournal('fund_transfer', $transfer->id);
            $transfer->delete();
        });

        return redirect()->route('accounting.transfers.index')
            ->with('success', "Data transfer {$transfer->transfer_number} beserta jurnal terkait berhasil dibatalkan.");
    }
}
