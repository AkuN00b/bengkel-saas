<?php

namespace App\Policies;

use App\Enums\WorkshopRole;
use App\Models\Customer;
use App\Models\User;
use App\Models\Workshop;

class CustomerPolicy
{
    public function viewAny(User $user, Workshop $workshop): bool
    {
        return $user->isMemberOf($workshop);
    }

    public function view(User $user, Customer $customer, Workshop $workshop): bool
    {
        return $customer->workshop_id === $workshop->id && $this->viewAny($user, $workshop);
    }

    public function create(User $user, Workshop $workshop): bool
    {
        return $user->workshops()->whereKey($workshop->id)
            ->wherePivotIn('role', [WorkshopRole::Owner->value, WorkshopRole::Cashier->value])->exists();
    }

    public function update(User $user, Customer $customer, Workshop $workshop): bool
    {
        return $customer->workshop_id === $workshop->id && $this->create($user, $workshop);
    }

    public function delete(User $user, Customer $customer): bool
    {
        return false;
    }
}
