<?php

namespace Database\Factories;

use App\Models\Ranger;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ranger>
 */
class RangerFactory extends Factory
{
    protected array $nigerianNames = [
        'Ibrahim Musa', 'Fatima Bello', 'Usman Sani', 'Aisha Yusuf',
        'Mohammed Abubakar', 'Zainab Adamu', 'Suleiman Garba', 'Halima Idris',
        'Bello Tanko', 'Amina Lawal', 'Kabiru Dauda', 'Rukayya Umar',
        'Nasiru Yahaya', 'Maryam Shehu', 'Yakubu Ismail',
    ];

    protected array $baseLocations = [
        'Kamuku National Park HQ',
        'Birnin Gwari Forest Station',
        'Kuyambana Game Reserve Outpost',
        'River Kaduna Patrol Base',
        'Dagida Forest Reserve',
        'Old Oyo National Park - North Gate',
        'Zugurma Game Reserve HQ',
        'Kainji Lake National Park',
        'Yankari Game Reserve South',
    ];

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement($this->nigerianNames),
            'phone_number' => '+234'.fake()->unique()->numberBetween(7000000000, 9999999999),
            'email' => fake()->unique()->safeEmail(),
            'base_location' => fake()->randomElement($this->baseLocations),
            'latitude' => fake()->latitude(7, 11),
            'longitude' => fake()->longitude(3, 11),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
