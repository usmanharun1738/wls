<?php

namespace Database\Factories;

use App\Models\UssdSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UssdSession>
 */
class UssdSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'session_id' => fake()->unique()->uuid(),
            'phone_number' => '+234'.fake()->numberBetween(7000000000, 9999999999),
            'current_step' => fake()->numberBetween(0, 3),
            'data' => null,
        ];
    }

    public function atStep(int $step): static
    {
        return $this->state(fn () => ['current_step' => $step]);
    }
}
