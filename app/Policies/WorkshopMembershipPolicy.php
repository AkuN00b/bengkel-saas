<?php

namespace App\Policies;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;

class WorkshopMembershipPolicy
{
    public function viewAny(User $user, Workshop $workshop): bool
    {
        return $user->hasWorkshopRole($workshop, WorkshopRole::Owner);
    }

    public function create(User $user, Workshop $workshop): bool
    {
        return $this->viewAny($user, $workshop);
    }

    public function update(User $user, WorkshopMembership $membership, Workshop $workshop): bool
    {
        return $membership->workshop_id === $workshop->id
            && $membership->role === WorkshopRole::Mechanic && $this->viewAny($user, $workshop);
    }
}
