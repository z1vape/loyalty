<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyTier extends Model
{
    protected $fillable = [
        'name',
        'level',
        'min_spend',
        'point_multiplier',
        'discount_percent',
        'point_value_rp',
        'badge_color',
        'perks',
        'is_default',
    ];

    protected $casts = [
        'level' => 'integer',
        'min_spend' => 'decimal:2',
        'point_multiplier' => 'float',
        'discount_percent' => 'float',
        'point_value_rp' => 'integer',
        'is_default' => 'boolean',
    ];

    public function customers()
    {
        return $this->hasMany(Customer::class, 'tier', 'name');
    }
}
