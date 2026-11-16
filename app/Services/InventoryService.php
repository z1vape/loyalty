<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;
use LogicException;

/** Atomic stock changes. HTTP authorization belongs to the controller/request. */
class InventoryService
{
    public function adjust(Product $product, int $change, string $reason, ?string $notes = null, ?int $userId = null, ?string $date = null): StockAdjustment
    {
        if ($change === 0 || abs($change) > 1000000 || !array_key_exists($reason, StockAdjustment::REASONS) || $reason === 'reversal') {
            throw new LogicException('Invalid adjustment quantity or reason.');
        }
        if (in_array($reason, ['damaged', 'lost'], true) && $change > 0) {
            throw new LogicException('Damaged or lost stock must reduce inventory.');
        }

        return DB::transaction(function () use ($product, $change, $reason, $notes, $userId, $date) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            return $this->record($locked, $change, $reason, $notes, $userId, null, $date);
        }, 3);
    }

    public function reverse(StockAdjustment $adjustment, ?int $userId = null, ?string $notes = null): StockAdjustment
    {
        return DB::transaction(function () use ($adjustment, $userId, $notes) {
            // Consistent lock order: product first, then adjustment.
            $product = Product::whereKey($adjustment->product_id)->lockForUpdate()->firstOrFail();
            $locked = StockAdjustment::whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
            if ($locked->isReversed() || $locked->reversal_of_id !== null) {
                throw new LogicException('This adjustment cannot be reversed again.');
            }
            if ($product->store_id !== $locked->store_id) {
                throw new LogicException('Product and adjustment stores do not match.');
            }
            $reversal = $this->record($product, -(int) $locked->quantity_change, 'reversal', $notes ?? 'Reversal of adjustment #' . $locked->id, $userId, $locked->id);
            $locked->forceFill(['reversed_at' => $reversal->created_at, 'reversed_by' => $userId])->save();
            return $reversal;
        }, 3);
    }

    private function record(Product $product, int $change, string $reason, ?string $notes, ?int $userId, ?int $originalId = null, ?string $date = null): StockAdjustment
    {
        $before = (int) $product->stock;
        $after = $before + $change;
        if ($after < 0 || $after > 2147483647) {
            throw new LogicException('Adjustment would produce an invalid stock balance.');
        }
        $product->update(['stock' => $after]);

        $attributes = [
            'store_id' => $product->store_id,
            'product_id' => $product->id,
            'user_id' => $userId,
            'quantity_before' => $before,
            'quantity_change' => $change,
            'quantity_after' => $after,
            'reason' => $reason,
            'notes' => $notes,
            'reversal_of_id' => $originalId,
        ];

        if ($date) {
            $dateTime = $date . ' ' . now()->format('H:i:s');
            $attributes['created_at'] = $dateTime;
            $attributes['updated_at'] = $dateTime;
        }

        $adjustment = StockAdjustment::create($attributes);

        // Record double-entry journal for inventory adjustments
        app(\App\Services\AccountingService::class)->recordStockAdjustmentJournal($adjustment);

        return $adjustment;
    }
}
