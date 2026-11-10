<?php

namespace Database\Factories;

use App\Models\Vehicle;
use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Vehicle> */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workshop_id' => Workshop::factory(),
            'plate_number' => 'B '.fake()->unique()->numberBetween(1, 999999999).' UT',
            'customer_id' => null,
            'brand' => null,
            'model' => null,
            'year' => null,
            'notes' => null,
        ];
    }
}
