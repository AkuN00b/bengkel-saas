<?php

namespace App\Policies;

use App\Enums\WorkshopRole;
use App\Models\Invoice;
use App\Models\User;
use App\Models\Workshop;

class InvoicePolicy
{
    public function viewAny(User $user, Workshop $workshop): bool
    {
        return $user->hasWorkshopRole($workshop, WorkshopRole::Owner) || $user->hasWorkshopRole($workshop, WorkshopRole::Cashier);
    }

    public function create(User $user, Workshop $workshop): bool
    {
        return $this->viewAny($user, $workshop);
    }

    public function view(User $user, Invoice $invoice, Workshop $workshop): bool
    {
        return $invoice->workshop_id === $workshop->id && $this->viewAny($user, $workshop);
    }

    public function update(User $user, Invoice $invoice, Workshop $workshop): bool
    {
        return $invoice->status === 'draft' && $this->view($user, $invoice, $workshop);
    }

    public function issue(User $user, Invoice $invoice, Workshop $workshop): bool
    {
        return $this->update($user, $invoice, $workshop);
    }

    public function pay(User $user, Invoice $invoice, Workshop $workshop): bool
    {
        return $invoice->status === 'issued' && $this->view($user, $invoice, $workshop);
    }

    public function correct(User $user, Invoice $invoice, Workshop $workshop): bool
    {
        return $this->view($user, $invoice, $workshop) && $user->hasWorkshopRole($workshop, WorkshopRole::Owner);
    }
}
