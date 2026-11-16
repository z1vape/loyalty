<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Stock In Store') === true;
    }

    public function rules(): array
    {
        if ($this->has('items') && is_array($this->input('items'))) {
            return [
                'items' => ['required', 'array', 'min:1'],
                'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('store_id', session('active_store_id'))],
                'items.*.quantity_change' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
                'items.*.reason' => ['required', Rule::in(['physical_count', 'damaged', 'lost', 'correction', 'return'])],
                'items.*.notes' => ['nullable', 'string', 'max:1000'],
                'adjustment_date' => ['nullable', 'date_format:Y-m-d'],
                'redirect_to_date' => ['nullable', 'boolean'],
            ];
        }

        return [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('store_id', session('active_store_id'))],
            'quantity_change' => ['required', 'integer', 'not_in:0', 'between:-1000000,1000000'],
            'reason' => ['required', Rule::in(['physical_count', 'damaged', 'lost', 'correction', 'return'])],
            'notes' => ['nullable', 'string', 'max:1000'],
            'adjustment_date' => ['nullable', 'date_format:Y-m-d'],
            'redirect_to_date' => ['nullable', 'boolean'],
        ];
    }
}
