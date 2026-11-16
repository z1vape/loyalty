<?php

namespace App\Traits;

use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToStore
{
    /**
     * Boot the BelongsToStore trait.
     */
    protected static function bootBelongsToStore(): void
    {
        static::addGlobalScope('store', function (Builder $builder) {
            if (session()->has('active_store_id')) {
                $builder->where($builder->getQuery()->from . '.store_id', session('active_store_id'));
            }
        });

        static::creating(function ($model) {
            if (session()->has('active_store_id') && is_null($model->store_id)) {
                $model->store_id = session('active_store_id');
            }
        });
    }

    /**
     * Get the store that owns the record.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }
}
