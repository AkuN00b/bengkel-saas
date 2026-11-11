<?php

namespace App\Policies;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Workshop;

class ReportPolicy
{
    public function finances(User $user, Workshop $workshop): bool
    {
        return $user->hasWorkshopRole($workshop, WorkshopRole::Owner);
    }

    public function history(User $user, Workshop $workshop): bool
    {
        return $user->isMemberOf($workshop) && ! $user->needs_password_setup;
    }
}
