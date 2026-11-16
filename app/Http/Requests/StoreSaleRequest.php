<?php

namespace App\Http\Requests;

use App\Models\Product;
use App\Rules\BelongsToCurrentStore;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('Sales Create') || $this->user()?->hasRole('Super Admin'); }
    public function rules(): array
    {
        return [
            'sale_date' => ['required', 'date'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'points_redeemed' => ['nullable', 'integer', 'min:0'],
            'points_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', new BelongsToCurrentStore(Product::class)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.selling_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0']
        ];
    }
}
