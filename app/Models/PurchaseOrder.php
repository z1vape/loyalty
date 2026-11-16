<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class PurchaseOrder extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'po_number',
        'purchase_request_id',
        'store_id',
        'supplier_name',
        'po_date',
        'expected_date',
        'payment_method',
        'payment_term_days',
        'status',
        'total_amount',
        'notes',
        'created_by',
        'received_by',
        'received_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'expected_date' => 'date',
        'received_at' => 'datetime',
        'total_amount' => 'decimal:2',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_number', 'po_number');
    }

    public function recalculateTotalAmount(): float
    {
        $total = (float) $this->items()->where('is_cancelled', false)->sum('subtotal');
        $this->update(['total_amount' => $total]);
        return $total;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'sent'      => 'bg-blue-500/10 text-blue-700 border-blue-500/20',
            'partial'   => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
            'received'  => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'cancelled' => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
            default     => 'bg-slate-500/10 text-slate-700 border-slate-500/20',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'     => 'Draft',
            'sent'      => 'Dikirim ke Supplier',
            'partial'   => 'Diterima Sebagian',
            'received'  => 'Selesai (Diterima Penuh)',
            'cancelled' => 'Dibatalkan',
            default     => ucfirst($this->status),
        };
    }
}

