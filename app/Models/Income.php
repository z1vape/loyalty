<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class Income extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id',
        'user_id',
        'account_id',
        'income_date',
        'category',
        'title',
        'amount',
        'payment_method',
        'notes',
    ];

    protected $casts = [
        'income_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
