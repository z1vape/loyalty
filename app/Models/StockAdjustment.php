<?php

namespace App\Models;

use App\Traits\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockAdjustment extends Model
{
    use BelongsToStore;

    public const REASONS = [
        'physical_count' => 'Stok fisik', 'damaged' => 'Barang rusak',
        'lost' => 'Barang hilang', 'correction' => 'Koreksi input',
        'return' => 'Retur', 'reversal' => 'Pembatalan',
    ];

    protected static function booted(): void
    {
        static::deleting(function () {
            throw new \LogicException('Stock history cannot be deleted; create a reversal instead.');
        });
        static::updating(function ($adjustment) {
            foreach (array_keys($adjustment->getDirty()) as $attribute) {
                if (!in_array($attribute, ['reversed_at', 'reversed_by', 'updated_at'], true)) {
                    throw new \LogicException('Stock history snapshots are immutable.');
                }
            }
        });
    }

    protected $fillable = [
        'store_id', 'product_id', 'user_id', 'quantity_before',
        'quantity_change', 'quantity_after', 'reason', 'notes', 'reversal_of_id',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'quantity_before' => 'integer',
        'quantity_change' => 'integer',
        'quantity_after' => 'integer',
        'reversed_at' => 'datetime',
    ];

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reversedBy(): BelongsTo { return $this->belongsTo(User::class, 'reversed_by'); }

    public function isReversed(): bool { return $this->reversed_at !== null; }

    public function original(): BelongsTo { return $this->belongsTo(self::class, 'reversal_of_id'); }
    public function reversal(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(self::class, 'reversal_of_id'); }
}
