<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceStatus extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'badge_color',
        'counts_as_workday',
        'requires_attachment',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'counts_as_workday'   => 'boolean',
        'requires_attachment' => 'boolean',
        'is_system'           => 'boolean',
        'is_active'           => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
