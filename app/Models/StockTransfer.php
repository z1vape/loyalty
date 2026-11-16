<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_store_id',
        'to_store_id',
        'transfer_date',
        'sent_at',
        'received_at',
        'status',
        'total_cost',
        'notes',
        'created_by',
        'sent_by',
        'received_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Scope to filter transfers involving a store (either as sender or receiver).
     */
    public function scopeForStore(Builder $query, int|array $storeIds): Builder
    {
        $ids = is_array($storeIds) ? $storeIds : [$storeIds];

        return $query->where(function (Builder $q) use ($ids) {
            $q->whereIn('from_store_id', $ids)
              ->orWhereIn('to_store_id', $ids);
        });
    }

    public function fromStore()
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore()
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'reference_number', 'transfer_number')->withoutGlobalScopes();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'in_transit' => 'bg-blue-500/10 text-blue-700 border-blue-500/20',
            'received'   => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'cancelled'  => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
            default      => 'bg-slate-500/10 text-slate-700 border-slate-500/20',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft'      => 'Draft',
            'in_transit' => 'Dalam Perjalanan (In-Transit)',
            'received'   => 'Diterima di Cabang Tujuan',
            'cancelled'  => 'Dibatalkan',
            default      => ucfirst($this->status),
        };
    }
}
