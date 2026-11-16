<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LegacyDebt extends Model
{
    protected $fillable = [
        'code',
        'supplier_name',
        'location',
        'initial_amount',
        'paid_amount',
        'remaining_amount',
        'notes',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(LegacyDebtPayment::class)->orderBy('payment_date', 'desc')->orderBy('id', 'desc');
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->initial_amount <= 0) return 100.0;
        return min(100.0, round(($this->paid_amount / $this->initial_amount) * 100, 1));
    }

    /**
     * Recalculate and persist totals from recorded payments.
     */
    public function recalculateTotals(): void
    {
        $totalPaid = (float) $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;
        $this->remaining_amount = max(0, (float) $this->initial_amount - $totalPaid);
        $this->save();
    }
}
