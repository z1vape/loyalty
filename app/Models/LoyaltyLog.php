<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyLog extends Model
{
    // Note: Loyalty logs reflect the customer's full point history across all stores,
    // so we intentionally do NOT apply the BelongsToStore global scope.

    protected $fillable = [
        'customer_id',
        'store_id',
        'sale_id',
        'user_id',
        'type',
        'points_change',
        'stamps_change',
        'points_balance',
        'stamps_balance',
        'description',
    ];

    protected static function booted()
    {
        static::creating(function ($log) {
            if (session()->has('active_store_id') && empty($log->store_id)) {
                $log->store_id = session('active_store_id');
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}

