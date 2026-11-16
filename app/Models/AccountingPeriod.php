<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToStore;

class AccountingPeriod extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'store_id',
        'name',
        'start_date',
        'end_date',
        'status',
        'closed_at',
        'closed_by',
        'closing_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'datetime',
    ];

    /**
     * User who closed the accounting period.
     */
    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Check if period is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * Check if period is open.
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }
}
