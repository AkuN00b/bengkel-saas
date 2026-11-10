<?php

namespace App\Policies;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Model;

class CatalogPolicy
{
    public function viewAny(User $user, Workshop $workshop): bool
    {
        return $user->hasWorkshopRole($workshop, WorkshopRole::Owner) || $user->hasWorkshopRole($workshop, WorkshopRole::Cashier);
    }

    public function create(User $user, Workshop $workshop): bool
    {
        return $user->hasWorkshopRole($workshop, WorkshopRole::Owner);
    }

    public function view(User $user, Model $record, Workshop $workshop): bool
    {
        return $record->workshop_id === $workshop->id && $this->viewAny($user, $workshop);
    }

    public function update(User $user, Model $record, Workshop $workshop): bool
    {
        return $record->workshop_id === $workshop->id && $this->create($user, $workshop);
    }

    public function stock(User $user, Model $record, Workshop $workshop): bool
    {
        return $this->view($user, $record, $workshop);
    }

    public function adjust(User $user, Model $record, Workshop $workshop): bool
    {
        return $this->update($user, $record, $workshop);
    }
}
