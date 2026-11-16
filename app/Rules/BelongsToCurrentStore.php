<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;

class BelongsToCurrentStore implements ValidationRule
{
    public function __construct(private readonly string $modelClass) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $storeId = session('active_store_id');
        if (!$storeId || !is_numeric($value) || !$this->modelClass::query()->whereKey($value)->where('store_id', $storeId)->exists()) {
            $fail('Data tidak tersedia pada toko aktif.');
        }
    }
}
