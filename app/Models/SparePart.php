<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SparePart extends Model
{
    protected $guarded = ['*'];

    protected function casts(): array
    {
        return ['selling_price' => 'integer', 'current_stock' => 'integer', 'minimum_stock' => 'integer', 'is_active' => 'boolean', 'version' => 'integer'];
    }
}
