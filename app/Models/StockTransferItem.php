<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id',
        'source_product_id',
        'destination_product_id',
        'quantity_requested',
        'quantity_sent',
        'quantity_received',
        'unit_cost',
        'subtotal_cost',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'integer',
        'quantity_sent' => 'integer',
        'quantity_received' => 'integer',
        'unit_cost' => 'decimal:2',
        'subtotal_cost' => 'decimal:2',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'stock_transfer_id');
    }

    public function sourceProduct()
    {
        return $this->belongsTo(Product::class, 'source_product_id')->withoutGlobalScopes();
    }

    public function destinationProduct()
    {
        return $this->belongsTo(Product::class, 'destination_product_id')->withoutGlobalScopes();
    }
}
