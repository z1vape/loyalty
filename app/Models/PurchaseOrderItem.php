<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'purchase_request_item_id',
        'product_id',
        'custom_item_name',
        'custom_sku',
        'quantity_ordered',
        'quantity_received',
        'is_cancelled',
        'unit_price',
        'selling_price',
        'subtotal',
        'notes',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'quantity_ordered' => 'integer',
        'quantity_received' => 'integer',
        'is_cancelled' => 'boolean',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function purchaseRequestItem()
    {
        return $this->belongsTo(PurchaseRequestItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getItemNameAttribute(): string
    {
        return $this->product ? $this->product->name : ($this->custom_item_name ?? 'Item Kustom');
    }

    public function getItemSkuAttribute(): string
    {
        return $this->product ? ($this->product->sku ?? '-') : ($this->custom_sku ?? 'MANUAL');
    }

    public function getItemSellingPriceAttribute(): float
    {
        return (float) ($this->selling_price ?? $this->product?->selling_price ?? $this->product?->price ?? 0);
    }
}

