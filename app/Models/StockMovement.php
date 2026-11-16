<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class StockMovement extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'product_id',
        'user_id',
        'reference_number',
        'movement_date',
        'type',
        'quantity',
        'purchase_price',
        'selling_price',
        'discount',
        'total_price',
        'payment_method',
        'supplier_name',
        'payment_status',
        'due_date',
        'paid_amount',
        'status',
        'notes',
        'store_id'
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_amount' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplierPayments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function getRemainingDebtAttribute(): float
    {
        // If explicitly paid, zero debt remaining
        if ($this->payment_status === 'paid') {
            return 0.0;
        }

        $method = strtolower(trim($this->payment_method ?? ''));
        $isTempo = in_array($method, ['tempo', 'hutang', 'credit']);

        // Cash / transfer purchases that are not partial payments have zero remaining debt
        if (!$isTempo && $this->payment_status !== 'partial') {
            return 0.0;
        }

        $total = (float) ($this->total_price ?: ($this->quantity * ($this->purchase_price ?? 0)));
        $paid = (float) ($this->paid_amount ?? 0);
        return max(0, $total - $paid);
    }

    public function getPaymentStatusBadgeAttribute(): string
    {
        return match ($this->payment_status) {
            'paid' => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'partial' => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
            'pending' => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
            default => 'bg-surface-container text-on-surface border-outline-variant',
        };
    }
}
