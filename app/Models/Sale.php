<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class Sale extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'reference_number',
        'sale_date',
        'customer_id',
        'customer_name',
        'total_amount',
        'points_earned',
        'points_redeemed',
        'points_discount_amount',
        'liquid_stamps_earned',
        'notes',
        'payment_method',
        'payment_splits',
        'status',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
        'user_id',
        'store_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'cancelled_at' => 'datetime',
        'payment_splits' => 'array',
    ];

    /**
     * Get payment method, fallback to notes or 'cash'
     */
    public function getPaymentMethodAttribute($value): string
    {
        // If notes explicitly record a specific payment method (e.g. from POS receipt), respect it
        if (!empty($this->notes) && preg_match('/Bayar:\s*([A-Za-z]+)/i', $this->notes, $matches)) {
            $fromNote = strtolower($matches[1]);
            if ($fromNote !== 'cash') {
                return $fromNote;
            }
        }

        if (!empty($value)) {
            return strtolower($value);
        }

        return 'cash';
    }

    /**
     * Human-friendly payment method label
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        $method = $this->payment_method;
        if ($method === 'split') {
            if (!empty($this->payment_splits) && is_array($this->payment_splits)) {
                $subLabels = array_map(function ($s) {
                    $m = strtolower($s['method'] ?? '');
                    $names = [
                        'cash' => 'Tunai',
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                        'debit' => 'Debit',
                        'credit' => 'Kredit',
                    ];
                    return $names[$m] ?? strtoupper($m);
                }, $this->payment_splits);
                return 'Split (' . implode(' + ', array_unique($subLabels)) . ')';
            }
            return 'Split Payment';
        }

        $labels = [
            'cash' => 'Tunai (Cash)',
            'transfer' => 'Transfer Bank',
            'qris' => 'QRIS',
            'debit' => 'Kartu Debit',
            'credit' => 'Kartu Kredit',
            'split' => 'Split Payment',
            'other' => 'Lainnya',
        ];
        return $labels[$method] ?? strtoupper($method);
    }

    /**
     * Badge CSS classes for payment method
     */
    public function getPaymentBadgeClassAttribute(): string
    {
        $classes = [
            'cash' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'transfer' => 'bg-indigo-500/10 text-indigo-700 border-indigo-500/20',
            'qris' => 'bg-sky-500/10 text-sky-700 border-sky-500/20',
            'debit' => 'bg-purple-500/10 text-purple-700 border-purple-500/20',
            'credit' => 'bg-pink-500/10 text-pink-700 border-pink-500/20',
            'split' => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
            'other' => 'bg-gray-100 text-gray-800 border-gray-200',
        ];
        return $classes[$this->payment_method] ?? 'bg-surface-container text-on-surface border-outline-variant';
    }

    /**
     * Get cash portion amount for this sale
     */
    public function getCashPortion(): float
    {
        if ($this->payment_method === 'cash') {
            return (float) $this->total_amount;
        }

        if ($this->payment_method === 'split') {
            if (!empty($this->payment_splits) && is_array($this->payment_splits)) {
                $cash = 0;
                foreach ($this->payment_splits as $split) {
                    if (strtolower($split['method'] ?? '') === 'cash') {
                        $cash += (float) ($split['amount'] ?? 0);
                    }
                }
                return $cash;
            }
            // Fallback from notes if notes mentions Tunai
            if (preg_match('/Tunai:\s*Rp([\d\.]+)/i', $this->notes ?? '', $m)) {
                return (float) str_replace('.', '', $m[1]);
            }
        }

        return 0;
    }

    /**
     * Get non-cash portion amount for this sale
     */
    public function getNonCashPortion(): float
    {
        return max(0, (float) $this->total_amount - $this->getCashPortion());
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
