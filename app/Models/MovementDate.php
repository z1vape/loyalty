<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class MovementDate extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'date',
        'type',
        'created_by',
        'notes',
        'store_id',
        'supplier_name',
        'payment_method',
        'due_date',
    ];

    protected $casts = [
        'due_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class, 'movement_date', 'date');
    }
}
