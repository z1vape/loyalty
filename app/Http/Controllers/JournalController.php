<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Store;
use App\Services\AccountingService;
use App\Exports\JournalEntryExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class JournalController extends Controller
{
    /**
     * Export journal entries to Excel with date range and filter criteria.
     */
    public function export(Request $request)
    {
        Gate::authorize('Accounting Journal List');

        $query = JournalEntry::with(['items.account', 'user'])
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'LIKE', "%{$search}%")
                  ->orWhere('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($source = $request->query('source_type')) {
            $query->where('source_type', $source);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('entry_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString()
            ]);
        } elseif ($startDate) {
            $query->where('entry_date', '>=', Carbon::parse($startDate)->toDateString());
        } elseif ($endDate) {
            $query->where('entry_date', '<=', Carbon::parse($endDate)->toDateString());
        }

        $entries = $query->get();

        $sourceTypes = [
            'sale' => 'Penjualan (POS)',
            'stock_movement' => 'Mutasi Stok',
            'expense' => 'Pengeluaran Toko',
            'income' => 'Pemasukan Toko',
            'stock_adjustment' => 'Penyesuaian Stok',
            'manual' => 'Jurnal Manual',
            'fund_transfer' => 'Transfer Kas/Bank',
            'bank_reconciliation' => 'Rekonsiliasi Bank',
        ];

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'source_type' => $source,
            'source_type_label' => $source ? ($sourceTypes[$source] ?? ucfirst($source)) : null,
            'search' => $search,
        ];

        $activeStoreId = session('active_store_id');
        $storeName = $activeStoreId ? (Store::find($activeStoreId)?->name ?? 'Semua Toko') : 'Semua Toko';

        $filename = 'jurnal_transaksi_' . ($startDate ? Carbon::parse($startDate)->format('Ymd') : 'all') .
            '_' . ($endDate ? Carbon::parse($endDate)->format('Ymd') : date('Ymd')) . '.xlsx';

        return Excel::download(
            new JournalEntryExport($entries, $filters, $storeName),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );
    }

    /**
     * Display listing of journal entries.
     */
    public function index(Request $request)
    {
        Gate::authorize('Accounting Journal List');

        $query = JournalEntry::with(['items.account', 'user'])->orderBy('entry_date', 'desc')->orderBy('id', 'desc');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('entry_number', 'LIKE', "%{$search}%")
                  ->orWhere('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($source = $request->query('source_type')) {
            $query->where('source_type', $source);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        if ($startDate && $endDate) {
            $query->whereBetween('entry_date', [
                Carbon::parse($startDate)->toDateString(),
                Carbon::parse($endDate)->toDateString()
            ]);
        } elseif ($startDate) {
            $query->where('entry_date', '>=', Carbon::parse($startDate)->toDateString());
        } elseif ($endDate) {
            $query->where('entry_date', '<=', Carbon::parse($endDate)->toDateString());
        }

        // Summary metrics
        $metricsQuery = clone $query;
        $totalEntries = $metricsQuery->count();
        $totalDebits = (float) $metricsQuery->sum('total_debit');
        $totalCredits = (float) $metricsQuery->sum('total_credit');

        $journalEntries = $query->paginate(20)->withQueryString();

        $sourceTypes = [
            'sale' => 'Penjualan (POS)',
            'stock_movement' => 'Mutasi Stok',
            'expense' => 'Pengeluaran Toko',
            'income' => 'Pemasukan Toko',
            'stock_adjustment' => 'Penyesuaian Stok',
            'manual' => 'Jurnal Manual',
            'fund_transfer' => 'Transfer Kas/Bank',
            'bank_reconciliation' => 'Rekonsiliasi Bank',
        ];

        return view('accounting.journals.index', compact(
            'journalEntries',
            'totalEntries',
            'totalDebits',
            'totalCredits',
            'sourceTypes',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Show form for creating a manual journal entry.
     */
    public function create()
    {
        Gate::authorize('Accounting Journal Create');

        $accounts = Account::active()->orderBy('code')->get();

        return view('accounting.journals.create', compact('accounts'));
    }

    /**
     * Store a newly created manual journal entry.
     */
    public function store(Request $request, AccountingService $accountingService)
    {
        Gate::authorize('Accounting Journal Create');

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:1000',
            'reference_number' => 'nullable|string|max:100',
            'items' => 'required|array|min:2',
            'items.*.account_id' => 'required|exists:accounts,id',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.credit' => 'nullable|numeric|min:0',
            'items.*.memo' => 'nullable|string|max:255',
        ], [
            'items.min' => 'Jurnal harus memiliki minimal 2 baris akun (debit dan kredit).',
            'items.*.account_id.required' => 'Pilih akun untuk setiap baris jurnal.',
        ]);

        $storeId = session('active_store_id') ?? 1;

        // Calculate and validate debit-credit equality
        $totalDebit = 0;
        $totalCredit = 0;
        foreach ($validated['items'] as $it) {
            $totalDebit += (float) ($it['debit'] ?? 0);
            $totalCredit += (float) ($it['credit'] ?? 0);
        }

        if (abs($totalDebit - $totalCredit) > 0.01) {
            return back()
                ->withErrors(['items' => "Total Debit (Rp " . number_format($totalDebit, 0, ',', '.') . ") tidak sama dengan Total Kredit (Rp " . number_format($totalCredit, 0, ',', '.') . "). Jurnal harus seimbang!"])
                ->withInput();
        }

        if ($totalDebit <= 0) {
            return back()
                ->withErrors(['items' => 'Nominal jurnal harus lebih besar dari 0.'])
                ->withInput();
        }

        try {
            DB::beginTransaction();

            $entryDate = Carbon::parse($validated['entry_date'])->toDateString();
            $entryNumber = $accountingService->generateEntryNumber($storeId, $entryDate);

            $entry = JournalEntry::create([
                'store_id' => $storeId,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'source_type' => 'manual',
                'source_id' => null,
                'reference_number' => $validated['reference_number'] ?? null,
                'description' => $validated['description'],
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'is_balanced' => true,
                'user_id' => auth()->id(),
            ]);

            foreach ($validated['items'] as $itemData) {
                $d = (float) ($itemData['debit'] ?? 0);
                $c = (float) ($itemData['credit'] ?? 0);
                if ($d <= 0 && $c <= 0) continue;

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $itemData['account_id'],
                    'debit' => $d,
                    'credit' => $c,
                    'memo' => $itemData['memo'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('accounting.journals.index')
                ->with('success', "Jurnal manual #{$entryNumber} berhasil disimpan.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menyimpan jurnal: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display or redirect to the specified journal entry.
     */
    public function show(JournalEntry $journal)
    {
        Gate::authorize('Accounting Journal List');

        return redirect()->route('accounting.journals.index', ['search' => $journal->entry_number]);
    }

    /**
     * Display the General Ledger (Buku Besar).
     */
    public function ledger(Request $request)
    {
        Gate::authorize('Accounting Ledger View');

        $accounts = Account::active()->orderBy('code')->get();

        $selectedAccountId = $request->query('account_id') ?? $accounts->first()?->id;
        $selectedAccount = $selectedAccountId ? Account::find($selectedAccountId) : null;

        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        $openingBalance = 0;
        $ledgerEntries = collect();
        $totalDebitPeriod = 0;
        $totalCreditPeriod = 0;
        $closingBalance = 0;

        if ($selectedAccount) {
            // 1. Opening Balance prior to start_date
            $priorDebit = (float) JournalItem::where('account_id', $selectedAccount->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
                ->sum('debit');

            $priorCredit = (float) JournalItem::where('account_id', $selectedAccount->id)
                ->whereHas('journalEntry', fn($q) => $q->whereDate('entry_date', '<', $startDate))
                ->sum('credit');

            $openingBalance = ($selectedAccount->normal_balance === 'debit')
                ? ($priorDebit - $priorCredit)
                : ($priorCredit - $priorDebit);

            // 2. Fetch journal items in date range
            $items = JournalItem::where('account_id', $selectedAccount->id)
                ->whereHas('journalEntry', fn($q) => $q->whereBetween('entry_date', [$startDate, $endDate]))
                ->with(['journalEntry.user'])
                ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id')
                ->orderBy('journal_entries.entry_date', 'asc')
                ->orderBy('journal_entries.id', 'asc')
                ->select('journal_items.*')
                ->get();

            $runningBalance = $openingBalance;
            $ledgerEntries = $items->map(function ($item) use (&$runningBalance, $selectedAccount, &$totalDebitPeriod, &$totalCreditPeriod) {
                $d = (float) $item->debit;
                $c = (float) $item->credit;
                $totalDebitPeriod += $d;
                $totalCreditPeriod += $c;

                if ($selectedAccount->normal_balance === 'debit') {
                    $runningBalance += ($d - $c);
                } else {
                    $runningBalance += ($c - $d);
                }

                $item->running_balance = $runningBalance;
                return $item;
            });

            $closingBalance = $runningBalance;
        }

        return view('accounting.journals.ledger', compact(
            'accounts',
            'selectedAccount',
            'startDate',
            'endDate',
            'openingBalance',
            'ledgerEntries',
            'totalDebitPeriod',
            'totalCreditPeriod',
            'closingBalance'
        ));
    }
}
