<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToStore;

class BankReconciliation extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id',
        'reconciliation_number',
        'account_id',
        'reconciliation_date',
        'system_balance',
        'actual_balance',
        'difference',
        'adjustment_account_id',
        'status',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'reconciliation_date' => 'date',
        'system_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'difference' => 'decimal:2',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function adjustmentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'adjustment_account_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Get the associated journal entry if adjustment was made.
     */
    public function getJournalEntryAttribute(): ?JournalEntry
    {
        return JournalEntry::withoutGlobalScopes()
            ->where('source_type', 'bank_reconciliation')
            ->where('source_id', $this->id)
            ->first();
    }
}

