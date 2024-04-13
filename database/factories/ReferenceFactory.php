<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reference>
 */
class ReferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => $this->faker->postcode(),
            'operation_id' => $this->faker->numberBetween(10000000,99999999),
            'user_id' => User::factory()->create(),
            'transaction_id' => 1//TODO: create Transaction factory
        ];
    }
}
