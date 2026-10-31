<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'price_monthly', 'user_limit', 'is_active'])]
class Plan extends Model
{
    protected function casts(): array
    {
        return ['price_monthly' => 'integer', 'user_limit' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
