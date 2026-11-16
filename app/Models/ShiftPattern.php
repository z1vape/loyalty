<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftPattern extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'description',
        'cycle_days',
        'is_active',
    ];

    protected $casts = [
        'cycle_days' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function items()
    {
        return $this->hasMany(ShiftPatternItem::class)->orderBy('day_number', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
