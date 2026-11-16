<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\BankReconciliation;
use App\Models\Store;
use App\Services\AccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BankReconciliationController extends Controller
{
    /**
     * Display a listing of bank reconciliations and account balances.
     */
    public function index(Request $request, AccountingService $accountingService)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->can('Accounting Journal List') && !$user->can('Accounting Transfer Manage') && !$user->can('Accounting Report View')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melihat rekonsiliasi bank.');
        }

        $storeId = session('active_store_id') ?? $user->stores()->first()?->id ?? 1;
        $accountingService->ensureStoreAccounts($storeId);

        $store = Store::find($storeId);

        // Get cash & bank accounts for the active store
        $accounts = Account::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where(function ($q) {
                $q->where('category', 'Kas & Bank')
                    ->orWhereIn('code', ['1101', '1102', '1103']);
            })
            ->orderBy('code')
            ->get();

        foreach ($accounts as $acc) {
            if ($acc->code === '1102' && $store) {
                // Bank Toko balance represents store's real-time operational balance (Saldo Toko Berjalan)
                $acc->current_balance = (float) $store->ending_balance;
            } elseif ($acc->code === '1101' && $store && ($store->auto_cash_to_bank || !empty($store->bank_name))) {
                // Physical cash drawer is auto-banked to 1102
                $acc->current_balance = 0.0;
            } else {
                $cutOff = $store?->initial_balance_date ? Carbon::parse($store->initial_balance_date)->toDateString() : null;
                $acc->current_balance = (float) $acc->calculateBalance($cutOff);
            }
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = BankReconciliation::withoutGlobalScopes()
            ->with(['account', 'adjustmentAccount', 'user'])
            ->where('store_id', $storeId);

        if (!empty($startDate)) {
            try {
                $startStr = str_contains($startDate, '/')
                    ? Carbon::createFromFormat('d/m/Y', $startDate)->toDateString()
                    : Carbon::parse($startDate)->toDateString();
                $query->whereDate('reconciliation_date', '>=', $startStr);
            } catch (\Throwable $t) {
                // ignore parse failure
            }
        }

        if (!empty($endDate)) {
            try {
                $endStr = str_contains($endDate, '/')
                    ? Carbon::createFromFormat('d/m/Y', $endDate)->toDateString()
                    : Carbon::parse($endDate)->toDateString();
                $query->whereDate('reconciliation_date', '<=', $endStr);
            } catch (\Throwable $t) {
                // ignore parse failure
            }
        }

        $reconciliations = $query->latest('reconciliation_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $mainBankAccount = $accounts->firstWhere('code', '1102') ?? $accounts->first();
        $lastRecon = BankReconciliation::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->latest('reconciliation_date')
            ->first();

        return view('accounting.reconciliations.index', compact(
            'store',
            'accounts',
            'mainBankAccount',
            'reconciliations',
            'lastRecon',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show the form for creating a new bank reconciliation.
     */
    public function create(Request $request, AccountingService $accountingService)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->can('Accounting Journal Create') && !$user->can('Accounting Transfer Manage')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melakukan rekonsiliasi bank.');
        }

        $storeId = session('active_store_id') ?? $user->stores()->first()?->id ?? 1;
        $accountingService->ensureStoreAccounts($storeId);

        $store = Store::find($storeId);

        // Accounts available to reconcile (Bank Toko, Kas Toko, Piutang QRIS)
        $accounts = Account::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where(function ($q) {
                $q->where('category', 'Kas & Bank')
                    ->orWhereIn('code', ['1101', '1102', '1103']);
            })
            ->orderBy('code')
            ->get();

        foreach ($accounts as $acc) {
            if ($acc->code === '1102' && $store) {
                // Bank Toko balance represents store's real-time operational balance (Saldo Toko Berjalan)
                $acc->current_balance = (float) $store->ending_balance;
            } elseif ($acc->code === '1101' && $store && ($store->auto_cash_to_bank || !empty($store->bank_name))) {
                // Physical cash drawer is auto-banked to 1102
                $acc->current_balance = 0.0;
            } else {
                $cutOff = $store?->initial_balance_date ? Carbon::parse($store->initial_balance_date)->toDateString() : null;
                $acc->current_balance = (float) $acc->calculateBalance($cutOff);
            }
        }

        $selectedAccountId = (int) $request->query('account_id', $accounts->firstWhere('code', '1102')?->id ?? $accounts->first()?->id);
        $selectedAccount = $accounts->firstWhere('id', $selectedAccountId) ?? $accounts->first();

        // Adjustment counter-accounts for discrepancy balancing
        $adjustmentAccounts = Account::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->whereIn('type', ['revenue', 'expense', 'equity'])
            ->orderBy('code')
            ->get();

        $suggestedNumber = 'RECON-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        return view('accounting.reconciliations.create', compact(
            'store',
            'accounts',
            'selectedAccount',
            'adjustmentAccounts',
            'suggestedNumber'
        ));
    }

    /**
     * Store a newly created bank reconciliation.
     */
    public function store(Request $request, AccountingService $accountingService)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->can('Accounting Journal Create') && !$user->can('Accounting Transfer Manage')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk melakukan rekonsiliasi bank.');
        }

        $storeId = session('active_store_id') ?? $user->stores()->first()?->id ?? 1;
        $accountingService->ensureStoreAccounts($storeId);

        $validated = $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'reconciliation_date' => 'required|date',
            'actual_balance' => 'required|numeric|min:0',
            'adjustment_account_id' => 'nullable|exists:accounts,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $store = Store::find($storeId);
        $account = Account::withoutGlobalScopes()->where('store_id', $storeId)->findOrFail($validated['account_id']);
        if ($account->code === '1102' && $store) {
            $systemBalance = (float) $store->ending_balance;
        } elseif ($account->code === '1101' && $store && ($store->auto_cash_to_bank || !empty($store->bank_name))) {
            $systemBalance = 0.0;
        } else {
            $cutOff = $store?->initial_balance_date ? Carbon::parse($store->initial_balance_date)->toDateString() : null;
            $systemBalance = (float) $account->calculateBalance($cutOff);
        }
        $actualBalance = (float) $validated['actual_balance'];
        $difference = round($actualBalance - $systemBalance, 2);

        $adjustmentAccountId = $validated['adjustment_account_id'] ?? null;

        // Auto fallback adjustment account if not specified by user
        if (abs($difference) >= 0.01 && empty($adjustmentAccountId)) {
            if ($difference > 0) {
                // Surplus: Pendapatan Selisih Kas & Rekonsiliasi Bank (4205), fallback to 4201
                $adjAcc = $accountingService->getStoreAccount($storeId, '4205')
                    ?? $accountingService->getStoreAccount($storeId, '4201');
            } else {
                // Deficit: Beban Selisih Kas & Rekonsiliasi Bank (6111), fallback to 6110
                $adjAcc = $accountingService->getStoreAccount($storeId, '6111')
                    ?? $accountingService->getStoreAccount($storeId, '6110');
            }
            $adjustmentAccountId = $adjAcc?->id;
        }

        $suggestedNumber = 'RECON-' . Carbon::now()->format('Ymd') . '-' . strtoupper(Str::random(4));

        $rawDate = $validated['reconciliation_date'];
        try {
            $parsedDate = str_contains($rawDate, '/')
                ? Carbon::createFromFormat('d/m/Y', $rawDate)->toDateString()
                : Carbon::parse($rawDate)->toDateString();
        } catch (\Throwable $t) {
            $parsedDate = Carbon::now()->toDateString();
        }

        $recon = DB::transaction(function () use ($storeId, $suggestedNumber, $account, $validated, $parsedDate, $systemBalance, $actualBalance, $difference, $adjustmentAccountId, $user, $accountingService) {
            $recon = BankReconciliation::create([
                'store_id' => $storeId,
                'reconciliation_number' => $suggestedNumber,
                'account_id' => $account->id,
                'reconciliation_date' => $parsedDate,
                'system_balance' => $systemBalance,
                'actual_balance' => $actualBalance,
                'difference' => $difference,
                'adjustment_account_id' => $adjustmentAccountId,
                'status' => abs($difference) < 0.01 ? 'balanced' : 'adjusted',
                'notes' => $validated['notes'] ?? null,
                'user_id' => $user->id,
            ]);

            // If there was a difference, automatically balance it via journal entry
            if (abs($difference) >= 0.01) {
                $accountingService->recordBankReconciliationJournal($recon);
            }

            return $recon;
        });

        $formattedActual = number_format($actualBalance, 0, ',', '.');
        if (abs($difference) < 0.01) {
            $message = "Rekonsiliasi {$account->name} selesai. Saldo sistem sudah cocok dengan ATM (Rp {$formattedActual}).";
        } else {
            $actionWord = $difference > 0 ? "penambahan" : "pengurangan";
            $formattedDiff = number_format(abs($difference), 0, ',', '.');
            $message = "Rekonsiliasi {$account->name} berhasil disesuaikan ({$actionWord} Rp {$formattedDiff}). Saldo sistem kini sama persis dengan saldo ATM (Rp {$formattedActual}).";
        }

        return redirect()->route('accounting.reconciliations.index')->with('success', $message);
    }

    /**
     * Remove the specified bank reconciliation and purge associated adjustment journal.
     */
    public function destroy(BankReconciliation $reconciliation, AccountingService $accountingService)
    {
        $user = auth()->user();
        if (!$user->hasRole('Super Admin') && !$user->can('Accounting Journal Create') && !$user->can('Accounting Transfer Manage')) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin untuk membatalkan rekonsiliasi.');
        }

        $reconNum = $reconciliation->reconciliation_number;
        $accountingService->purgeJournal('bank_reconciliation', $reconciliation->id);
        $reconciliation->delete();

        return redirect()->route('accounting.reconciliations.index')
            ->with('success', "Rekonsiliasi #{$reconNum} dan jurnal penyesuaiannya berhasil dibatalkan.");
    }
}

