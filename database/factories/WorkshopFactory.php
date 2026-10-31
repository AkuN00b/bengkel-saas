<?php

namespace Database\Factories;

use App\Models\Workshop;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Workshop> */
class WorkshopFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Bengkel '.fake()->company(),
            'slug' => fake()->unique()->slug(3),
            'phone' => null,
            'address' => null,
            'timezone' => 'Asia/Jakarta',
        ];
    }
}
