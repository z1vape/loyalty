<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToStore;

class Account extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'type',
        'category',
        'normal_balance',
        'is_system',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship to journal items.
     */
    public function journalItems(): HasMany
    {
        return $this->hasMany(JournalItem::class);
    }

    /**
     * Scope active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by account type (asset, liability, equity, revenue, expense).
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Calculate account balance up to an optional end date.
     */
    public function calculateBalance(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->journalItems()
            ->join('journal_entries', 'journal_items.journal_entry_id', '=', 'journal_entries.id');

        if ($startDate) {
            $query->whereDate('journal_entries.entry_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('journal_entries.entry_date', '<=', $endDate);
        }

        $totalDebit = (float) $query->sum('journal_items.debit');
        $totalCredit = (float) $query->sum('journal_items.credit');

        if ($this->normal_balance === 'debit') {
            return $totalDebit - $totalCredit;
        }

        return $totalCredit - $totalDebit;
    }

    /**
     * Badge CSS classes for account type.
     */
    public function getTypeBadgeClassAttribute(): string
    {
        return match ($this->type) {
            'asset' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'liability' => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
            'equity' => 'bg-purple-500/10 text-purple-700 border-purple-500/20',
            'revenue' => 'bg-sky-500/10 text-sky-700 border-sky-500/20',
            'expense' => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
            default => 'bg-surface-container text-on-surface border-outline-variant',
        };
    }

    /**
     * Human readable type label in Indonesian.
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'asset' => 'Aset / Aktiva',
            'liability' => 'Kewajiban / Hutang',
            'equity' => 'Ekuitas / Modal',
            'revenue' => 'Pendapatan',
            'expense' => 'Beban / Biaya',
            default => ucfirst($this->type),
        };
    }
}
