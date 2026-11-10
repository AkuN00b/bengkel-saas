<?php

namespace App\Policies;

use App\Enums\WorkOrderStatus;
use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Workshop;

class WorkOrderPolicy
{
    public function viewAny(User $user, Workshop $workshop): bool
    {
        return $user->isMemberOf($workshop);
    }

    public function create(User $user, Workshop $workshop): bool
    {
        return $user->hasWorkshopRole($workshop, WorkshopRole::Owner) || $user->hasWorkshopRole($workshop, WorkshopRole::Cashier);
    }

    public function view(User $user, WorkOrder $order, Workshop $workshop): bool
    {
        return $order->workshop_id === $workshop->id && ($this->create($user, $workshop)
            || ($user->hasWorkshopRole($workshop, WorkshopRole::Mechanic) && ! $user->needs_password_setup && $order->assigned_mechanic_id === $user->id));
    }

    public function update(User $user, WorkOrder $order, Workshop $workshop): bool
    {
        return $order->workshop_id === $workshop->id && $order->status->isActive() && $this->create($user, $workshop);
    }

    public function assign(User $user, WorkOrder $order, Workshop $workshop): bool
    {
        return $this->update($user, $order, $workshop);
    }

    public function process(User $user, WorkOrder $order, Workshop $workshop): bool
    {
        return in_array($order->status, [WorkOrderStatus::Open, WorkOrderStatus::InProgress], true) && $this->view($user, $order, $workshop);
    }

    public function cancel(User $user, WorkOrder $order, Workshop $workshop): bool
    {
        return $order->workshop_id === $workshop->id && $order->status->isActive() && $user->hasWorkshopRole($workshop, WorkshopRole::Owner);
    }
}
