<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\Expense;
use App\Models\Income;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Store;
use Database\Seeders\AccountSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingService
{
    /**
     * Ensure a store has its default Chart of Accounts seeded.
     */
    public function ensureStoreAccounts(int $storeId): void
    {
        $defaultTemplate = AccountSeeder::defaultAccounts();
        foreach ($defaultTemplate as $accountData) {
            Account::withoutGlobalScopes()->firstOrCreate(
                [
                    'store_id' => $storeId,
                    'code' => $accountData['code'],
                ],
                array_merge($accountData, ['store_id' => $storeId])
            );
        }
    }

    /**
     * Retrieve an account by code for a given store.
     */
    public function getStoreAccount(int $storeId, string $code): ?Account
    {
        $account = Account::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('code', $code)
            ->first();

        if (!$account) {
            $this->ensureStoreAccounts($storeId);
            $account = Account::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->where('code', $code)
                ->first();
        }

        return $account;
    }

    /**
     * Generate the next sequential journal entry number for a store.
     */
    public function generateEntryNumber(int $storeId, ?string $date = null): string
    {
        $prefixDate = $date ? Carbon::parse($date)->format('Ym') : Carbon::now()->format('Ym');
        $prefix = "JE-{$prefixDate}-";

        $lastEntry = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('entry_number', 'LIKE', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->value('entry_number');

        $nextSeq = 1;
        if ($lastEntry && preg_match('/-(\d+)$/', $lastEntry, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Determine primary operational liquid account for cash sales, expenses, and operational flows.
     * If store has auto_cash_to_bank enabled and Bank Toko (1102) exists, returns Bank Toko.
     * Otherwise returns Kas Toko (1101).
     */
    public function getPrimaryOperationalAccount(int $storeId): Account
    {
        $store = Store::withoutGlobalScopes()->find($storeId);
        $bankAcc = $this->getStoreAccount($storeId, '1102');
        $cashAcc = $this->getStoreAccount($storeId, '1101');

        $autoBank = $store && ($store->auto_cash_to_bank ?? (!empty($store->bank_name)));

        return ($autoBank && $bankAcc) ? $bankAcc : ($cashAcc ?? $bankAcc);
    }

    /**
     * Record a balanced journal entry from sales transactions (POS / Retail).
     */
    public function recordSaleJournal(Sale $sale): ?JournalEntry
    {
        if ($sale->status === 'cancelled') {
            return null;
        }

        // Find existing journal entry if any (search by source_type & source_id to prevent store mismatch)
        $existing = JournalEntry::withoutGlobalScopes()
            ->where('source_type', 'sale')
            ->where('source_id', $sale->id)
            ->first();

        $storeId = $sale->store_id ?: ($existing?->store_id ?: (session('active_store_id') ?: 1));
        if ($sale->store_id !== $storeId) {
            $sale->updateQuietly(['store_id' => $storeId]);
        }
        if (!$storeId) {
            return null;
        }

        return DB::transaction(function () use ($sale, $storeId, $existing) {
            $entryDate = $sale->sale_date ? Carbon::parse($sale->sale_date)->toDateString() : Carbon::parse($sale->created_at)->toDateString();
            $entryNumber = $existing ? $existing->entry_number : $this->generateEntryNumber($storeId, $entryDate);

            $this->ensureStoreAccounts($storeId);

            $cashAcc = $this->getStoreAccount($storeId, '1101'); // Kas Toko
            $bankAcc = $this->getStoreAccount($storeId, '1102'); // Bank Toko
            $qrisAcc = $this->getStoreAccount($storeId, '1103'); // Piutang QRIS & EDC
            $primaryCashAcc = $this->getPrimaryOperationalAccount($storeId);
            $revAcc  = $this->getStoreAccount($storeId, '4101'); // Pendapatan Penjualan
            $discAcc = $this->getStoreAccount($storeId, '4102'); // Diskon Penjualan
            $cogsAcc = $this->getStoreAccount($storeId, '5101'); // HPP
            $invAcc  = $this->getStoreAccount($storeId, '1201'); // Persediaan Barang

            $totalAmount = (float) $sale->total_amount;
            $items = $sale->items()->with('product')->get();

            // 1. Calculate Gross Sales, Discounts, and COGS
            $grossRevenue = 0;
            $totalDiscount = 0;
            $totalCogs = 0;

            if ($items->isNotEmpty()) {
                foreach ($items as $item) {
                    $itemQty = (float) $item->quantity;
                    $sellingPrice = (float) $item->selling_price;
                    $discount = (float) ($item->discount ?? 0);
                    $productCost = (float) ($item->product?->purchase_price ?? 0);

                    $grossRevenue += ($sellingPrice * $itemQty);
                    $totalDiscount += ($discount * $itemQty);
                    $totalCogs += ($productCost * $itemQty);
                }
            } else {
                $grossRevenue = $totalAmount;
            }

            if ($totalDiscount == 0 && (float) ($sale->discount ?? 0) > 0) {
                $totalDiscount = (float) $sale->discount;
            }

            if ($grossRevenue <= $totalAmount && $totalDiscount > 0) {
                $grossRevenue = $totalAmount + $totalDiscount;
            }

            // Create or update Journal Entry Header
            if ($existing) {
                // Delete any duplicate entries for this sale in other stores
                JournalEntry::withoutGlobalScopes()
                    ->where('source_type', 'sale')
                    ->where('source_id', $sale->id)
                    ->where('id', '!=', $existing->id)
                    ->delete();

                $existing->update([
                    'store_id' => $storeId,
                    'entry_date' => $entryDate,
                    'description' => "Penjualan #{$sale->reference_number} (" . ($sale->customer_name ?: 'Umum') . ")",
                    'user_id' => $sale->user_id,
                ]);
                $existing->items()->delete();
                $journalEntry = $existing;
            } else {
                $journalEntry = JournalEntry::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'entry_number' => $entryNumber,
                    'entry_date' => $entryDate,
                    'source_type' => 'sale',
                    'source_id' => $sale->id,
                    'reference_number' => $sale->reference_number,
                    'description' => "Penjualan #{$sale->reference_number} (" . ($sale->customer_name ?: 'Umum') . ")",
                    'user_id' => $sale->user_id,
                ]);
            }

            // 2. Debit Payment Account(s)
            $method = strtolower($sale->payment_method ?? 'cash');

            if ($method === 'split' && !empty($sale->payment_splits) && is_array($sale->payment_splits)) {
                foreach ($sale->payment_splits as $split) {
                    $m = strtolower($split['method'] ?? 'cash');
                    $amt = (float) ($split['amount'] ?? 0);
                    if ($amt <= 0) continue;

                    $targetAcc = match ($m) {
                        'transfer' => $bankAcc,
                        'qris', 'debit', 'credit', 'edc' => $qrisAcc,
                        default => $primaryCashAcc,
                    };

                    JournalItem::create([
                        'journal_entry_id' => $journalEntry->id,
                        'account_id' => $targetAcc->id,
                        'debit' => $amt,
                        'credit' => 0,
                        'memo' => "Penerimaan " . strtoupper($m) . " #{$sale->reference_number}",
                    ]);
                }
            } else {
                $targetAcc = match ($method) {
                    'transfer' => $bankAcc,
                    'qris', 'debit', 'credit', 'edc' => $qrisAcc,
                    default => $primaryCashAcc,
                };

                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $targetAcc->id,
                    'debit' => $totalAmount,
                    'credit' => 0,
                    'memo' => "Penerimaan kas/bank #{$sale->reference_number}",
                ]);
            }

            // 3. Debit Discount (if any)
            if ($totalDiscount > 0 && $discAcc) {
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $discAcc->id,
                    'debit' => $totalDiscount,
                    'credit' => 0,
                    'memo' => "Diskon penjualan #{$sale->reference_number}",
                ]);
            }

            // 4. Credit Sales Revenue
            JournalItem::create([
                'journal_entry_id' => $journalEntry->id,
                'account_id' => $revAcc->id,
                'debit' => 0,
                'credit' => $grossRevenue,
                'memo' => "Pendapatan kotor penjualan #{$sale->reference_number}",
            ]);

            // 5. Debit COGS & Credit Inventory (if COGS calculated)
            if ($totalCogs > 0 && $cogsAcc && $invAcc) {
                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $cogsAcc->id,
                    'debit' => $totalCogs,
                    'credit' => 0,
                    'memo' => "Beban Pokok Penjualan (HPP) #{$sale->reference_number}",
                ]);

                JournalItem::create([
                    'journal_entry_id' => $journalEntry->id,
                    'account_id' => $invAcc->id,
                    'debit' => 0,
                    'credit' => $totalCogs,
                    'memo' => "Pengurangan persediaan keluar #{$sale->reference_number}",
                ]);
            }

            // Finalize totals and balanced status
            $sumDebit = (float) $journalEntry->items()->sum('debit');
            $sumCredit = (float) $journalEntry->items()->sum('credit');

            $journalEntry->update([
                'total_debit' => $sumDebit,
                'total_credit' => $sumCredit,
                'is_balanced' => abs($sumDebit - $sumCredit) < 0.01,
            ]);

            return $journalEntry;
        });
    }

    /**
     * Reverse a sale's journal entry upon cancellation.
     */
    public function reverseSaleJournal(Sale $sale, ?string $reason = null): ?JournalEntry
    {
        $existing = JournalEntry::withoutGlobalScopes()
            ->when($sale->store_id, fn($q) => $q->where('store_id', $sale->store_id))
            ->where('source_type', 'sale')
            ->where('source_id', $sale->id)
            ->first();

        if (!$existing) {
            return null;
        }

        // Avoid duplicate reversals
        $reversalExisting = JournalEntry::withoutGlobalScopes()
            ->when($sale->store_id, fn($q) => $q->where('store_id', $sale->store_id))
            ->where('source_type', 'sale_cancellation')
            ->where('source_id', $sale->id)
            ->first();

        if ($reversalExisting) {
            return $reversalExisting;
        }

        return DB::transaction(function () use ($sale, $existing, $reason) {
            $reversalDate = Carbon::now()->toDateString();
            $reversalNumber = $this->generateEntryNumber($sale->store_id, $reversalDate);

            $reversalEntry = JournalEntry::withoutGlobalScopes()->create([
                'store_id' => $sale->store_id,
                'entry_number' => $reversalNumber,
                'entry_date' => $reversalDate,
                'source_type' => 'sale_cancellation',
                'source_id' => $sale->id,
                'reference_number' => "REV-{$sale->reference_number}",
                'description' => "Pembalikan Penjualan #{$sale->reference_number}" . ($reason ? " ({$reason})" : ''),
                'user_id' => auth()->id() ?? $sale->user_id,
            ]);

            // Flip debits and credits of original entry
            foreach ($existing->items as $item) {
                JournalItem::create([
                    'journal_entry_id' => $reversalEntry->id,
                    'account_id' => $item->account_id,
                    'debit' => $item->credit,
                    'credit' => $item->debit,
                    'memo' => "Pembalikan item: " . ($item->memo ?? ''),
                ]);
            }

            $sumDebit = (float) $reversalEntry->items()->sum('debit');
            $sumCredit = (float) $reversalEntry->items()->sum('credit');

            $reversalEntry->update([
                'total_debit' => $sumDebit,
                'total_credit' => $sumCredit,
                'is_balanced' => abs($sumDebit - $sumCredit) < 0.01,
            ]);

            return $reversalEntry;
        });
    }

    /**
     * Record a balanced journal entry for approved Stock In (purchases/restock).
     */
    public function recordStockInJournal(StockMovement $movement): ?JournalEntry
    {
        if ($movement->type !== 'in' || $movement->status !== 'approved') {
            return null;
        }

        $storeId = $movement->store_id ?: (session('active_store_id') ?: 1);
        if (!$storeId) {
            return null;
        }

        // Idempotency check
        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'stock_movement')
            ->where('source_id', $movement->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $cost = (float) ($movement->total_price ?: ($movement->quantity * ($movement->purchase_price ?? $movement->price ?? 0)));
        if ($cost <= 0) {
            return null;
        }

        return DB::transaction(function () use ($movement, $storeId, $cost) {
            $entryDate = $movement->movement_date ? Carbon::parse($movement->movement_date)->toDateString() : Carbon::parse($movement->created_at)->toDateString();
            $entryNumber = $this->generateEntryNumber($storeId, $entryDate);

            $invAcc     = $this->getStoreAccount($storeId, '1201'); // Persediaan Barang Dagang
            $cashAcc    = $this->getStoreAccount($storeId, '1101'); // Kas Toko
            $bankAcc    = $this->getStoreAccount($storeId, '1102'); // Bank Toko
            $payableAcc = $this->getStoreAccount($storeId, '2101'); // Hutang Dagang
            $primaryCashAcc = $this->getPrimaryOperationalAccount($storeId);

            $paymentMethod = strtolower($movement->payment_method ?? 'cash');
            $creditAcc = match ($paymentMethod) {
                'transfer' => $bankAcc,
                'tempo', 'hutang', 'credit' => $payableAcc,
                default => $primaryCashAcc,
            };

            $productName = $movement->product?->name ?? 'Barang';
            $ref = $movement->reference_number ?: "IN-{$movement->id}";

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'source_type' => 'stock_movement',
                'source_id' => $movement->id,
                'reference_number' => $ref,
                'description' => "Penerimaan Stok Masuk #{$ref} ({$productName})",
                'total_debit' => $cost,
                'total_credit' => $cost,
                'is_balanced' => true,
                'user_id' => $movement->user_id ?? auth()->id(),
            ]);

            // Debit Inventory
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $invAcc->id,
                'debit' => $cost,
                'credit' => 0,
                'memo' => "Penambahan nilai persediaan {$productName} (+{$movement->quantity})",
            ]);

            // Credit Cash / Bank / Payable
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAcc->id,
                'debit' => 0,
                'credit' => $cost,
                'memo' => "Pembayaran pembelian stok {$productName}",
            ]);

            return $entry;
        });
    }

    /**
     * Record or update balanced journal entry for a store's initial balance.
     */
    public function recordStoreInitialBalanceJournal(Store $store): ?JournalEntry
    {
        $amount = (float) ($store->initial_balance ?? 0);
        if ($amount <= 0) {
            $this->purgeJournal('store_initial_balance', $store->id);
            return null;
        }

        $storeId = $store->id;
        $this->ensureStoreAccounts($storeId);

        // Date of initial balance: 1 day prior to cut-off date so it serves as opening balance
        $cutOffDate = $store->initial_balance_date 
            ? Carbon::parse($store->initial_balance_date) 
            : ($store->created_at ? Carbon::parse($store->created_at) : Carbon::today());
        $entryDate = $cutOffDate->copy()->subDay()->toDateString();

        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'store_initial_balance')
            ->where('source_id', $store->id)
            ->first();

        // Target: Bank Toko (1102) if bank_name specified, otherwise Kas Toko (1101)
        $targetAcc = !empty($store->bank_name)
            ? $this->getStoreAccount($storeId, '1102')
            : $this->getStoreAccount($storeId, '1101');

        $equityAcc = $this->getStoreAccount($storeId, '3101'); // Modal Pemilik

        if (!$targetAcc || !$equityAcc) {
            return null;
        }

        return DB::transaction(function () use ($store, $storeId, $amount, $entryDate, $existing, $targetAcc, $equityAcc) {
            if ($existing) {
                $existing->update([
                    'entry_date' => $entryDate,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'description' => "Saldo Awal Modal & Kas/Bank Toko {$store->name}",
                ]);

                $existing->items()->delete();
                $entry = $existing;
            } else {
                $entryNumber = $this->generateEntryNumber($storeId, $entryDate);
                $entry = JournalEntry::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'entry_number' => $entryNumber,
                    'entry_date' => $entryDate,
                    'source_type' => 'store_initial_balance',
                    'source_id' => $store->id,
                    'reference_number' => "INIT-{$store->id}",
                    'description' => "Saldo Awal Modal & Kas/Bank Toko {$store->name}",
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                    'user_id' => auth()->id() ?? \App\Models\User::withoutGlobalScopes()->value('id'),
                ]);
            }

            // Debit: Kas Toko / Bank Toko
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $targetAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Saldo awal kas/bank toko {$store->name}",
            ]);

            // Credit: Modal Pemilik
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $equityAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Setoran modal awal pemilik toko {$store->name}",
            ]);

            return $entry;
        });
    }

    /**
     * Record or update a balanced journal entry for store expenses.
     */
    public function recordExpenseJournal(Expense $expense): ?JournalEntry
    {
        $storeId = $expense->store_id;
        if (!$storeId && $expense->account_id) {
            $storeId = Account::withoutGlobalScopes()->where('id', $expense->account_id)->value('store_id');
        }
        $storeId = $storeId ?: (session('active_store_id') ?: 1);
        if ($expense->store_id !== $storeId) {
            $expense->updateQuietly(['store_id' => $storeId]);
        }
        if (!$storeId) {
            return null;
        }

        $amount = (float) $expense->amount;
        if ($amount <= 0) {
            return null;
        }

        $this->ensureStoreAccounts($storeId);

        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'expense')
            ->where('source_id', $expense->id)
            ->first();

        return DB::transaction(function () use ($expense, $storeId, $amount, $existing) {
            $entryDate = Carbon::parse($expense->expense_date)->toDateString();

            // 1. Determine target debit account (Expense / Receivable / Payable / Equity)
            $expenseAcc = null;
            if ($expense->account_id) {
                $expenseAcc = Account::withoutGlobalScopes()
                    ->where('store_id', $storeId)
                    ->where('id', $expense->account_id)
                    ->first();
            }

            if (!$expenseAcc) {
                $cat = strtolower($expense->category ?? '');
                $code = match (true) {
                    str_contains($cat, 'gaji') => '6101',
                    str_contains($cat, 'sewa') => '6102',
                    str_contains($cat, 'listrik') || str_contains($cat, 'air') || str_contains($cat, 'internet') => '6103',
                    str_contains($cat, 'konsumsi') || str_contains($cat, 'makan') || str_contains($cat, 'minum') || str_contains($cat, 'dapur') => '6106',
                    str_contains($cat, 'pasar') || str_contains($cat, 'iklan') || str_contains($cat, 'promosi') => '6107',
                    str_contains($cat, 'transport') || str_contains($cat, 'bensin') || str_contains($cat, 'ongkir') || str_contains($cat, 'kirim') => '6108',
                    str_contains($cat, 'rawat') || str_contains($cat, 'servis') || str_contains($cat, 'perbaikan') => '6109',
                    str_contains($cat, 'cabang') || str_contains($cat, 'antar toko') || (str_contains($cat, 'talangan') && !str_contains($cat, 'pribadi')) => '1104',
                    str_contains($cat, 'kasbon') || str_contains($cat, 'pinjam') => '1105',
                    str_contains($cat, 'bayar balik') || str_contains($cat, 'lunas talangan') => '2102',
                    str_contains($cat, 'pribadi') || str_contains($cat, 'reimburse') => '2103',
                    str_contains($cat, 'bagi hasil') || str_contains($cat, 'prive') || str_contains($cat, 'dividen') => '3102',
                    str_contains($cat, 'supplier') || str_contains($cat, 'hutang dagang') => '2101',
                    str_contains($cat, 'perlengkapan') => '6104',
                    default => '6104',
                };
                $expenseAcc = $this->getStoreAccount($storeId, $code);
            }

            // 2. Determine credit payment account (Kas Tunai vs Bank Toko)
            $method = strtolower($expense->payment_method ?? 'cash');
            $paymentAcc = match ($method) {
                'transfer', 'bank' => $this->getStoreAccount($storeId, '1102'),
                default => $this->getPrimaryOperationalAccount($storeId),
            };

            if (!$paymentAcc) {
                $paymentAcc = $this->getPrimaryOperationalAccount($storeId);
            }

            $desc = "Pengeluaran: {$expense->title} (" . ($expenseAcc->name ?? $expense->category) . ")";

            if ($existing) {
                $existing->update([
                    'entry_date' => $entryDate,
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                ]);
                $existing->items()->delete();
                $entry = $existing;
            } else {
                $entryNumber = $this->generateEntryNumber($storeId, $entryDate);
                $entry = JournalEntry::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'entry_number' => $entryNumber,
                    'entry_date' => $entryDate,
                    'source_type' => 'expense',
                    'source_id' => $expense->id,
                    'reference_number' => "EXP-{$expense->id}",
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                    'user_id' => $expense->user_id ?? auth()->id(),
                ]);
            }

            // Debit Target Account
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $expenseAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => $expense->title,
            ]);

            // Credit Cash / Bank
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $paymentAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Kas/Bank keluar untuk {$expense->title} (" . strtoupper($method) . ")",
            ]);

            return $entry;
        });
    }

    /**
     * Record or update a balanced journal entry for store incomes.
     */
    public function recordIncomeJournal(Income $income): ?JournalEntry
    {
        $storeId = $income->store_id;
        if (!$storeId && $income->account_id) {
            $storeId = Account::withoutGlobalScopes()->where('id', $income->account_id)->value('store_id');
        }
        $storeId = $storeId ?: (session('active_store_id') ?: 1);
        if ($income->store_id !== $storeId) {
            $income->updateQuietly(['store_id' => $storeId]);
        }
        if (!$storeId) {
            return null;
        }

        $amount = (float) $income->amount;
        if ($amount <= 0) {
            return null;
        }

        $this->ensureStoreAccounts($storeId);

        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'income')
            ->where('source_id', $income->id)
            ->first();

        return DB::transaction(function () use ($income, $storeId, $amount, $existing) {
            $entryDate = Carbon::parse($income->income_date)->toDateString();

            // 1. Determine debit receiving account (Kas vs Bank)
            $method = strtolower($income->payment_method ?? 'cash');
            $receivingAcc = match ($method) {
                'transfer', 'bank' => $this->getStoreAccount($storeId, '1102'),
                default => $this->getPrimaryOperationalAccount($storeId),
            };

            if (!$receivingAcc) {
                $receivingAcc = $this->getPrimaryOperationalAccount($storeId);
            }

            // 2. Determine credit target account
            $creditAcc = null;
            if ($income->account_id) {
                $creditAcc = Account::withoutGlobalScopes()
                    ->where('store_id', $storeId)
                    ->where('id', $income->account_id)
                    ->first();
            }

            if (!$creditAcc) {
                $cat = strtolower($income->category ?? '');
                $code = match (true) {
                    str_contains($cat, 'jasa') || str_contains($cat, 'coil') || str_contains($cat, 'kapas') || str_contains($cat, 'build') => '4103',
                    str_contains($cat, 'bonus') || str_contains($cat, 'cashback') || str_contains($cat, 'rebate') => '4202',
                    str_contains($cat, 'komisi') || str_contains($cat, 'konsinyasi') || str_contains($cat, 'titip') => '4203',
                    str_contains($cat, 'bunga') || str_contains($cat, 'giro') => '4204',
                    str_contains($cat, 'pengembalian talangan') || str_contains($cat, 'terima talangan') || str_contains($cat, 'pelunasan talangan') => '1104',
                    str_contains($cat, 'kasbon') => '1105',
                    str_contains($cat, 'modal') => '3101',
                    default => '4201',
                };
                $creditAcc = $this->getStoreAccount($storeId, $code);
            }

            $desc = "Pemasukan: {$income->title} (" . ($creditAcc->name ?? $income->category) . ")";

            if ($existing) {
                $existing->update([
                    'entry_date' => $entryDate,
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                ]);
                $existing->items()->delete();
                $entry = $existing;
            } else {
                $entryNumber = $this->generateEntryNumber($storeId, $entryDate);
                $entry = JournalEntry::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'entry_number' => $entryNumber,
                    'entry_date' => $entryDate,
                    'source_type' => 'income',
                    'source_id' => $income->id,
                    'reference_number' => "INC-{$income->id}",
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                    'user_id' => $income->user_id ?? auth()->id(),
                ]);
            }

            // Debit Cash or Bank
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $receivingAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Penerimaan kas/bank masuk dari {$income->title} (" . strtoupper($method) . ")",
            ]);

            // Credit Target Account
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $creditAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => $income->title,
            ]);

            return $entry;
        });
    }

    /**
     * Record a balanced journal entry for stock adjustments (opname shrinkage or found stock).
     */
    public function recordStockAdjustmentJournal(StockAdjustment $adjustment): ?JournalEntry
    {
        $storeId = $adjustment->store_id ?? session('active_store_id');
        if (!$storeId) {
            return null;
        }

        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'stock_adjustment')
            ->where('source_id', $adjustment->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $qtyChange = (int) $adjustment->quantity_change;
        if ($qtyChange === 0) {
            return null;
        }

        $product = $adjustment->product;
        $unitCost = (float) ($product?->purchase_price ?? 0);
        $totalValuation = abs($qtyChange) * $unitCost;

        if ($totalValuation <= 0) {
            return null;
        }

        return DB::transaction(function () use ($adjustment, $storeId, $qtyChange, $totalValuation, $product) {
            $entryDate = Carbon::parse($adjustment->created_at)->toDateString();
            $entryNumber = $this->generateEntryNumber($storeId, $entryDate);

            $invAcc  = $this->getStoreAccount($storeId, '1201'); // Persediaan Barang
            $lossAcc = $this->getStoreAccount($storeId, '6105'); // Beban Selisih Stok
            $gainAcc = $this->getStoreAccount($storeId, '4201'); // Pendapatan Lain-lain

            $productName = $product?->name ?? 'Produk';
            $reason = $adjustment->reason ?: 'Penyesuaian Stok';

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'source_type' => 'stock_adjustment',
                'source_id' => $adjustment->id,
                'reference_number' => "ADJ-{$adjustment->id}",
                'description' => "Penyesuaian Stok: {$productName} ({$reason})",
                'total_debit' => $totalValuation,
                'total_credit' => $totalValuation,
                'is_balanced' => true,
                'user_id' => $adjustment->user_id ?? auth()->id(),
            ]);

            if ($qtyChange < 0) {
                // Shrinkage/Loss: Debit Loss, Credit Inventory
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $lossAcc->id,
                    'debit' => $totalValuation,
                    'credit' => 0,
                    'memo' => "Beban selisih/kerusakan {$productName} ({$qtyChange})",
                ]);

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $invAcc->id,
                    'debit' => 0,
                    'credit' => $totalValuation,
                    'memo' => "Pengurangan persediaan {$productName}",
                ]);
            } else {
                // Surplus: Debit Inventory, Credit Gain
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $invAcc->id,
                    'debit' => $totalValuation,
                    'credit' => 0,
                    'memo' => "Penambahan persediaan ditemukan {$productName} (+{$qtyChange})",
                ]);

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $gainAcc->id,
                    'debit' => 0,
                    'credit' => $totalValuation,
                    'memo' => "Pendapatan penyesuaian stok opname",
                ]);
            }

            return $entry;
        });
    }

    /**
     * Delete or purge journal entry linked to a removed operational record.
     */
    public function purgeJournal(string $sourceType, int $sourceId): void
    {
        $entries = JournalEntry::withoutGlobalScopes()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->get();

        foreach ($entries as $entry) {
            JournalItem::where('journal_entry_id', $entry->id)->delete();
            $entry->delete();
        }
    }

    /**
     * Delete or purge journal entry linked to a reference number (and its reversals).
     */
    public function purgeJournalByReference(string $referenceNumber): void
    {
        $cleanRef = preg_replace('/^REV-/', '', $referenceNumber);
        $entries = JournalEntry::withoutGlobalScopes()
            ->where(function ($q) use ($cleanRef) {
                $q->where('reference_number', $cleanRef)
                  ->orWhere('reference_number', "REV-{$cleanRef}");
            })
            ->get();

        foreach ($entries as $entry) {
            JournalItem::where('journal_entry_id', $entry->id)->delete();
            $entry->delete();
        }
    }

    /**
     * Record a balanced journal entry for internal fund transfer (e.g. cash deposit or bank withdrawal).
     */
    public function recordFundTransferJournal(\App\Models\FundTransfer $transfer): ?JournalEntry
    {
        $storeId = $transfer->store_id;
        $amount = (float) $transfer->amount;
        if ($amount <= 0 || !$storeId) {
            return null;
        }

        return DB::transaction(function () use ($transfer, $storeId, $amount) {
            $entryDate = Carbon::parse($transfer->transfer_date)->toDateString();
            $entryNumber = $this->generateEntryNumber($storeId, $entryDate);

            $fromAcc = $transfer->fromAccount;
            $toAcc = $transfer->toAccount;

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'source_type' => 'fund_transfer',
                'source_id' => $transfer->id,
                'reference_number' => $transfer->transfer_number,
                'description' => "Transfer Kas/Bank: {$fromAcc->name} ke {$toAcc->name}" . ($transfer->notes ? " ({$transfer->notes})" : ''),
                'total_debit' => $amount,
                'total_credit' => $amount,
                'is_balanced' => true,
                'user_id' => $transfer->user_id ?? auth()->id(),
            ]);

            // Debit target account (receiving)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $toAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Penerimaan transfer dari {$fromAcc->name}",
            ]);

            // Credit source account (sending)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $fromAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Pengiriman transfer ke {$toAcc->name}",
            ]);

            return $entry;
        });
    }

    /**
     * Record a balanced journal entry for bank reconciliation / cash-bank balance adjustment.
     */
    public function recordBankReconciliationJournal(\App\Models\BankReconciliation $recon): ?JournalEntry
    {
        $this->purgeJournal('bank_reconciliation', $recon->id);

        $diff = (float) $recon->difference;
        if (abs($diff) < 0.01) {
            return null; // Balanced, no adjustment journal needed
        }

        $amount = abs($diff);
        $storeId = $recon->store_id;

        return DB::transaction(function () use ($recon, $storeId, $amount, $diff) {
            $entryDate = Carbon::parse($recon->reconciliation_date)->toDateString();
            $entryNumber = $this->generateEntryNumber($storeId, $entryDate);

            $bankAcc = $recon->account;
            $adjAcc = $recon->adjustmentAccount;

            $actionText = $diff > 0 ? "Penambahan Saldo Bank (Surplus ATM)" : "Pengurangan Saldo Bank (Defisit ATM)";
            $desc = "Rekonsiliasi Bank: {$actionText} - {$bankAcc->name}" . ($recon->notes ? " ({$recon->notes})" : '');

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'source_type' => 'bank_reconciliation',
                'source_id' => $recon->id,
                'reference_number' => $recon->reconciliation_number,
                'description' => $desc,
                'total_debit' => $amount,
                'total_credit' => $amount,
                'is_balanced' => true,
                'user_id' => $recon->user_id ?? auth()->id(),
            ]);

            if ($diff > 0) {
                // ATM > Sistem: Menambah saldo Bank (Debit Bank, Kredit Akun Penyeimbang)
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $bankAcc->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Penyesuaian saldo riil bank {$bankAcc->name} (+)",
                ]);

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $adjAcc->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Akun penyeimbang rekonsiliasi: {$adjAcc->name}",
                ]);
            } else {
                // ATM < Sistem: Mengurangi saldo Bank (Debit Akun Penyeimbang/Beban, Kredit Bank)
                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $adjAcc->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'memo' => "Akun penyeimbang rekonsiliasi: {$adjAcc->name}",
                ]);

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $bankAcc->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'memo' => "Penyesuaian saldo riil bank {$bankAcc->name} (-)",
                ]);
            }

            return $entry;
        });
    }

    /**
     * Record a balanced journal entry for supplier debt repayment.
     */
    public function recordSupplierPaymentJournal(\App\Models\SupplierPayment $payment): ?JournalEntry
    {
        $storeId = $payment->store_id;
        $amount = (float) $payment->amount;
        if ($amount <= 0 || !$storeId) {
            return null;
        }

        return DB::transaction(function () use ($payment, $storeId, $amount) {
            $entryDate = Carbon::parse($payment->payment_date)->toDateString();
            $entryNumber = $this->generateEntryNumber($storeId, $entryDate);

            $payableAcc = $this->getStoreAccount($storeId, '2101'); // Hutang Dagang
            $paymentAcc = $payment->paymentAccount; // Kas atau Bank

            $ref = $payment->stockMovement?->reference_number ?? "IN-{$payment->stock_movement_id}";
            $supplierName = $payment->stockMovement?->supplier_name ?: 'Supplier';

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'store_id' => $storeId,
                'entry_number' => $entryNumber,
                'entry_date' => $entryDate,
                'source_type' => 'supplier_payment',
                'source_id' => $payment->id,
                'reference_number' => $payment->reference_number ?: "PAY-{$payment->id}",
                'description' => "Pelunasan Hutang Pembelian #{$ref} ({$supplierName})",
                'total_debit' => $amount,
                'total_credit' => $amount,
                'is_balanced' => true,
                'user_id' => $payment->user_id ?? auth()->id(),
            ]);

            // Debit Hutang Dagang (reducing liability)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $payableAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Pelunasan hutang supplier #{$ref}",
            ]);

            // Credit Kas / Bank (paying out)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $paymentAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Pembayaran ke {$supplierName}",
            ]);

            return $entry;
        });
    }

    /**
     * Record a balanced journal entry for legacy debt installment payment.
     */
    public function recordLegacyDebtPaymentJournal(\App\Models\LegacyDebtPayment $payment): ?JournalEntry
    {
        $storeId = $payment->store_id;
        $amount = (float) $payment->amount;
        if ($amount <= 0 || !$storeId) {
            return null;
        }

        $this->ensureStoreAccounts($storeId);

        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'legacy_debt_payment')
            ->where('source_id', $payment->id)
            ->first();

        return DB::transaction(function () use ($payment, $storeId, $amount, $existing) {
            $entryDate = Carbon::parse($payment->payment_date)->toDateString();
            $debtAcc = $this->getStoreAccount($storeId, '2105') ?? $this->getStoreAccount($storeId, '2101');
            $paymentAcc = $payment->paymentAccount 
                ?? ($payment->payment_method === 'cash' ? $this->getStoreAccount($storeId, '1101') : $this->getStoreAccount($storeId, '1102')) 
                ?? $this->getPrimaryOperationalAccount($storeId);

            $debt = $payment->legacyDebt;
            $supplierName = $debt ? "{$debt->supplier_name} ({$debt->location})" : 'Supplier Warisan';
            $desc = "Pelunasan Cicilan Utang Warisan: {$supplierName}";

            if ($existing) {
                $existing->update([
                    'entry_date' => $entryDate,
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                ]);
                $existing->items()->delete();
                $entry = $existing;
            } else {
                $entryNumber = $this->generateEntryNumber($storeId, $entryDate);
                $entry = JournalEntry::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'entry_number' => $entryNumber,
                    'entry_date' => $entryDate,
                    'source_type' => 'legacy_debt_payment',
                    'source_id' => $payment->id,
                    'reference_number' => $payment->reference_number ?: "PAY-LEG-{$payment->id}",
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                    'user_id' => $payment->user_id ?? auth()->id(),
                ]);
            }

            // Debit 2105 Hutang Warisan (Mengurangi Kewajiban)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $debtAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Pengurangan saldo hutang {$supplierName}",
            ]);

            // Credit Kas / Bank (Uang Kas Keluar)
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $paymentAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Pembayaran cicilan {$supplierName}",
            ]);

            return $entry;
        });
    }

    /**
     * Record a balanced journal entry at the sender store when a stock transfer is sent (In-Transit).
     * Debit 1104 (Piutang Antar Cabang / Toko) / Credit 1201 (Persediaan Barang Dagang).
     */
    public function recordStockTransferOutJournal(StockTransfer $transfer): ?JournalEntry
    {
        $storeId = (int) $transfer->from_store_id;
        $amount = (float) $transfer->items()->get()->sum(fn ($item) => (float) $item->quantity_sent * (float) $item->unit_cost);

        if ($amount <= 0 || !$storeId) {
            return null;
        }

        $this->ensureStoreAccounts($storeId);

        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'stock_transfer_out')
            ->where('source_id', $transfer->id)
            ->first();

        return DB::transaction(function () use ($transfer, $storeId, $amount, $existing) {
            $entryDate = $transfer->sent_at
                ? Carbon::parse($transfer->sent_at)->toDateString()
                : Carbon::parse($transfer->transfer_date)->toDateString();

            $receivableAcc = $this->getStoreAccount($storeId, '1104');
            $inventoryAcc = $this->getStoreAccount($storeId, '1201');
            $toStoreName = $transfer->toStore?->name ?? "Cabang #{$transfer->to_store_id}";
            $desc = "Pengiriman Mutasi Barang #{$transfer->transfer_number} ke {$toStoreName}";

            if ($existing) {
                $existing->update([
                    'entry_date' => $entryDate,
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                ]);
                $existing->items()->delete();
                $entry = $existing;
            } else {
                $entryNumber = $this->generateEntryNumber($storeId, $entryDate);
                $entry = JournalEntry::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'entry_number' => $entryNumber,
                    'entry_date' => $entryDate,
                    'source_type' => 'stock_transfer_out',
                    'source_id' => $transfer->id,
                    'reference_number' => $transfer->transfer_number,
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                    'user_id' => $transfer->sent_by ?? $transfer->created_by ?? auth()->id(),
                ]);
            }

            // Debit 1104 Piutang Antar Cabang / Toko
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $receivableAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Piutang mutasi barang ke {$toStoreName} ({$transfer->transfer_number})",
            ]);

            // Credit 1201 Persediaan Barang Dagang
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Keluar persediaan mutasi ke {$toStoreName} ({$transfer->transfer_number})",
            ]);

            return $entry;
        });
    }

    /**
     * Record a balanced journal entry at the destination store when a stock transfer is received.
     * Debit 1201 (Persediaan Barang Dagang) / Credit 2102 (Hutang Antar Cabang / Toko).
     */
    public function recordStockTransferInJournal(StockTransfer $transfer): ?JournalEntry
    {
        $storeId = (int) $transfer->to_store_id;
        $amount = (float) $transfer->items()->get()->sum(fn ($item) => (float) $item->quantity_received * (float) $item->unit_cost);

        if ($amount <= 0 || !$storeId) {
            return null;
        }

        $this->ensureStoreAccounts($storeId);

        $existing = JournalEntry::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('source_type', 'stock_transfer_in')
            ->where('source_id', $transfer->id)
            ->first();

        return DB::transaction(function () use ($transfer, $storeId, $amount, $existing) {
            $entryDate = $transfer->received_at
                ? Carbon::parse($transfer->received_at)->toDateString()
                : Carbon::parse($transfer->transfer_date)->toDateString();

            $inventoryAcc = $this->getStoreAccount($storeId, '1201');
            $payableAcc = $this->getStoreAccount($storeId, '2102');
            $fromStoreName = $transfer->fromStore?->name ?? "Cabang #{$transfer->from_store_id}";
            $desc = "Penerimaan Mutasi Barang #{$transfer->transfer_number} dari {$fromStoreName}";

            if ($existing) {
                $existing->update([
                    'entry_date' => $entryDate,
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                ]);
                $existing->items()->delete();
                $entry = $existing;
            } else {
                $entryNumber = $this->generateEntryNumber($storeId, $entryDate);
                $entry = JournalEntry::withoutGlobalScopes()->create([
                    'store_id' => $storeId,
                    'entry_number' => $entryNumber,
                    'entry_date' => $entryDate,
                    'source_type' => 'stock_transfer_in',
                    'source_id' => $transfer->id,
                    'reference_number' => $transfer->transfer_number,
                    'description' => $desc,
                    'total_debit' => $amount,
                    'total_credit' => $amount,
                    'is_balanced' => true,
                    'user_id' => $transfer->received_by ?? $transfer->created_by ?? auth()->id(),
                ]);
            }

            // Debit 1201 Persediaan Barang Dagang
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAcc->id,
                'debit' => $amount,
                'credit' => 0,
                'memo' => "Masuk persediaan mutasi dari {$fromStoreName} ({$transfer->transfer_number})",
            ]);

            // Credit 2102 Hutang Antar Cabang / Toko
            JournalItem::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $payableAcc->id,
                'debit' => 0,
                'credit' => $amount,
                'memo' => "Hutang mutasi barang ke {$fromStoreName} ({$transfer->transfer_number})",
            ]);

            return $entry;
        });
    }

    /**
     * Check if a transaction date falls into a closed accounting period.
     */
    public function isDateLocked(int $storeId, string $date): bool
    {
        $dateFormatted = Carbon::parse($date)->toDateString();
        return \App\Models\AccountingPeriod::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('status', 'closed')
            ->where('start_date', '<=', $dateFormatted)
            ->where('end_date', '>=', $dateFormatted)
            ->exists();
    }

    /**
     * Assert date is not locked, or throw exception.
     */
    public function assertDateNotLocked(int $storeId, string $date): void
    {
        if ($this->isDateLocked($storeId, $date)) {
            $formatted = Carbon::parse($date)->format('d F Y');
            throw new \LogicException("Periode akuntansi untuk tanggal {$formatted} telah ditutup (Locked). Transaksi tidak dapat diubah.");
        }
    }
}
