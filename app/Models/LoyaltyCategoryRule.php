<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCategoryRule extends Model
{
    protected $fillable = [
        'category_name',
        'points_per_item',
        'spend_per_point',
        'is_active',
    ];

    protected $casts = [
        'points_per_item' => 'integer',
        'spend_per_point' => 'integer',
        'is_active' => 'boolean',
    ];
}
