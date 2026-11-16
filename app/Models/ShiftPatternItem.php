<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftPatternItem extends Model
{
    protected $fillable = [
        'shift_pattern_id',
        'day_number',
        'shift_id',
        'is_off',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'is_off'     => 'boolean',
    ];

    public function pattern()
    {
        return $this->belongsTo(ShiftPattern::class, 'shift_pattern_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }
}
