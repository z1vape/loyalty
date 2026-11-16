<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToStore;

class JournalEntry extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id',
        'entry_number',
        'entry_date',
        'source_type',
        'source_id',
        'reference_number',
        'description',
        'total_debit',
        'total_credit',
        'is_balanced',
        'user_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'is_balanced' => 'boolean',
    ];

    /**
     * Relationship to journal detail items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class);
    }

    /**
     * User who created or triggered the journal entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Badge CSS classes for source type.
     */
    public function getSourceBadgeClassAttribute(): string
    {
        return match ($this->source_type) {
            'sale' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'stock_movement' => 'bg-sky-500/10 text-sky-700 border-sky-500/20',
            'expense' => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
            'income' => 'bg-teal-500/10 text-teal-700 border-teal-500/20',
            'stock_adjustment' => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
            'manual' => 'bg-purple-500/10 text-purple-700 border-purple-500/20',
            default => 'bg-surface-container text-on-surface border-outline-variant',
        };
    }

    /**
     * Human readable source label in Indonesian.
     */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source_type) {
            'sale' => 'Penjualan (POS)',
            'stock_movement' => 'Mutasi Stok',
            'expense' => 'Pengeluaran Toko',
            'income' => 'Pemasukan Toko',
            'stock_adjustment' => 'Penyesuaian Stok',
            'manual' => 'Jurnal Manual',
            default => ucfirst(str_replace('_', ' ', $this->source_type ?? 'Umum')),
        };
    }
}
