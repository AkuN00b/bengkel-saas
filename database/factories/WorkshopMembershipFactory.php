<?php

namespace Database\Factories;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkshopMembership> */
class WorkshopMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'workshop_id' => Workshop::factory(),
            'role' => WorkshopRole::Mechanic,
        ];
    }
}
