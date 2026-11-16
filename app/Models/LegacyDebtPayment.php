<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegacyDebtPayment extends Model
{
    protected $fillable = [
        'legacy_debt_id',
        'store_id',
        'user_id',
        'payment_date',
        'amount',
        'payment_account_id',
        'payment_method',
        'reference_number',
        'notes',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function legacyDebt(): BelongsTo
    {
        return $this->belongsTo(LegacyDebt::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'payment_account_id');
    }
}
