<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalItem extends Model
{
    protected $fillable = [
        'journal_entry_id',
        'account_id',
        'debit',
        'credit',
        'memo',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Parent journal entry.
     */
    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /**
     * Related account in the Chart of Accounts.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
