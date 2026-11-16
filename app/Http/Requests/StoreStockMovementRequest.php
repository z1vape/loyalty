<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\BelongsToCurrentStore;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('Stock In Store') || $this->user()?->can('Stock Out Store') || $this->user()?->hasRole('Super Admin'); }
    public function rules(): array
    {
        return [
            'items' => ['nullable', 'array'],
            'items.*.product_id' => ['required_with:items', 'integer', new BelongsToCurrentStore(Product::class)],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
            'items.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer', new BelongsToCurrentStore(Product::class)],
            'product_id' => ['nullable', 'integer', new BelongsToCurrentStore(Product::class)],
            'type' => ['required', 'in:in,out'],
            'quantity' => ['nullable', 'required_without_all:items,product_ids', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'movement_date' => ['required', 'date'],
            'user_id' => ['nullable', 'exists:users,id'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total_price' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'due_date' => ['nullable', 'date'],
        ];
    }
}
