<?php

namespace App\Policies;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Workshop;

class VehiclePolicy
{
    public function viewAny(User $user, Workshop $workshop): bool
    {
        return $user->isMemberOf($workshop);
    }

    public function view(User $user, Vehicle $vehicle, Workshop $workshop): bool
    {
        return $vehicle->workshop_id === $workshop->id && $this->viewAny($user, $workshop);
    }

    public function create(User $user, Workshop $workshop): bool
    {
        return $user->workshops()->whereKey($workshop->id)
            ->wherePivotIn('role', [WorkshopRole::Owner->value, WorkshopRole::Cashier->value])->exists();
    }

    public function update(User $user, Vehicle $vehicle, Workshop $workshop): bool
    {
        return $vehicle->workshop_id === $workshop->id && $this->create($user, $workshop);
    }

    public function delete(User $user, Vehicle $vehicle): bool
    {
        return false;
    }
}
