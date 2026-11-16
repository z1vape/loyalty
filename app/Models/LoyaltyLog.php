<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class LoyaltyLog extends Model
{
    use BelongsToStore;

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

    public function customer()
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

