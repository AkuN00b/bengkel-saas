<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'name' => fake()->name(),
            'phone' => null,
            'notes' => null,
        ];
    }
}
