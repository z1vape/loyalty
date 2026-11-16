<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyCategoryRule extends Model
{
    protected $fillable = [
        'category_name',
        'spend_per_point',
        'is_active',
    ];

    protected $casts = [
        'spend_per_point' => 'integer',
        'is_active' => 'boolean',
    ];
}
