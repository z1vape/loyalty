<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToStore;

class Setting extends Model
{
    use BelongsToStore;

    protected $fillable = ['type', 'value'];
}
