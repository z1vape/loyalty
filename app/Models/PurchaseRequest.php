<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class PurchaseRequest extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'pr_number',
        'store_id',
        'user_id',
        'request_date',
        'status',
        'priority',
        'notes',
        'approved_by',
        'approved_at',
        'admin_notes',
    ];

    protected $casts = [
        'request_date' => 'date',
        'approved_at'  => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedByUser()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseRequestItem::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class)->latestOfMany();
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'approved'  => 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20',
            'rejected'  => 'bg-rose-500/10 text-rose-700 border-rose-500/20',
            'completed' => 'bg-blue-500/10 text-blue-700 border-blue-500/20',
            default     => 'bg-amber-500/10 text-amber-700 border-amber-500/20',
        };
    }
}
