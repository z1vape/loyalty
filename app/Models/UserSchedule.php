<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'store_id',
        'date',
        'shift_id',
        'is_off',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date'   => 'date:Y-m-d',
        'is_off' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
