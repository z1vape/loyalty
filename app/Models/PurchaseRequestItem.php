<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRequestItem extends Model
{
    protected $fillable = [
        'purchase_request_id',
        'product_id',
        'custom_item_name',
        'custom_sku',
        'estimated_price',
        'selling_price',
        'quantity_requested',
        'quantity_approved',
        'is_cancelled',
        'added_from_po',
        'notes',
    ];

    protected $casts = [
        'estimated_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity_requested' => 'integer',
        'quantity_approved' => 'integer',
        'is_cancelled' => 'boolean',
        'added_from_po' => 'boolean',
    ];

    public function purchaseRequest()
    {
        return $this->belongsTo(PurchaseRequest::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withoutGlobalScopes();
    }

    public function getItemNameAttribute(): string
    {
        return $this->product ? $this->product->name : ($this->custom_item_name ?? 'Item Kustom');
    }

    public function getItemSkuAttribute(): string
    {
        return $this->product ? ($this->product->sku ?? '-') : ($this->custom_sku ?? 'MANUAL');
    }

    public function getItemPriceAttribute(): float
    {
        if ($this->product) {
            return (float) ($this->product->purchase_price ?? $this->product->price ?? 0);
        }
        return (float) ($this->estimated_price ?? 0);
    }

    public function getItemSellingPriceAttribute(): float
    {
        if ($this->product) {
            return (float) ($this->product->selling_price ?? $this->product->price ?? 0);
        }
        return (float) ($this->estimated_price ?? 0);
    }

    public function getIsCustomAttribute(): bool
    {
        return empty($this->product_id);
    }
}
