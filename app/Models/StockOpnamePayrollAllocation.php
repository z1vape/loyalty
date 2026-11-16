<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockOpnamePayrollAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'opname_date',
        'user_id',
        'payroll_id',
        'amount',
    ];

    protected $casts = [
        'opname_date' => 'date:Y-m-d',
        'amount'      => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }
}
