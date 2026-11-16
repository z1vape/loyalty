<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class Product extends Model
{
    use BelongsToStore;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'purchase_price',
        'selling_price',
        'stock',
        'min_stock',
        'category',
        'variant',
        'photo',
        'store_id'
    ];

    protected $appends = ['photo_url'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/' . $this->photo) : null;
    }

    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Calculate and update Moving Average Cost (HPP) when receiving new stock.
     */
    public function applyStockInMovingAverage(float $incomingQty, ?float $incomingPrice): void
    {
        $currentStock = max(0, (float) $this->stock);
        $currentCost = (float) $this->purchase_price;
        $price = ($incomingPrice !== null && $incomingPrice > 0) ? (float) $incomingPrice : $currentCost;

        if ($price > 0) {
            if ($currentStock <= 0) {
                $newCost = $price;
            } else {
                $totalCurrentVal = $currentStock * $currentCost;
                $totalIncomingVal = $incomingQty * $price;
                $newCost = ($totalCurrentVal + $totalIncomingVal) / ($currentStock + $incomingQty);
            }
            $this->purchase_price = round($newCost, 2);
        }

        $this->stock += $incomingQty;
        $this->save();
    }

    /**
     * Revert stock and Moving Average Cost (HPP) when an approved Stock In is reverted or unapproved.
     */
    public function revertStockInMovingAverage(float $outgoingQty, ?float $incomingPrice): void
    {
        $currentStock = (float) $this->stock;
        $currentCost = (float) $this->purchase_price;
        $price = ($incomingPrice !== null && $incomingPrice > 0) ? (float) $incomingPrice : $currentCost;

        $newStock = max(0, $currentStock - $outgoingQty);
        if ($newStock > 0 && $price > 0) {
            $totalCurrentVal = $currentStock * $currentCost;
            $totalIncomingVal = $outgoingQty * $price;
            $diffVal = $totalCurrentVal - $totalIncomingVal;
            if ($diffVal > 0) {
                $this->purchase_price = round($diffVal / $newStock, 2);
            }
        }
        $this->stock = $newStock;
        $this->save();
    }
}
