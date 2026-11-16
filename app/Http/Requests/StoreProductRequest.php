<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->can('Inventory Store') || $this->user()?->can('Inventory Update') || $this->user()?->hasRole('Super Admin'); }

    public function rules(): array
    {
        $product = $this->route('product');
        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('products', 'sku')->where(fn ($q) => $q->where('store_id', session('active_store_id')))->ignore($product?->id)],
            'name' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'],
            'purchase_price' => ['required', 'numeric', 'min:0'], 'selling_price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'], 'min_stock' => ['required', 'integer', 'min:0'],
            'category' => ['nullable', 'string', 'max:100'], 'variant' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
        ];
    }
}
