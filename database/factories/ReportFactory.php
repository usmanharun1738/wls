<?php

namespace Database\Factories;

use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Report>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        $incidentTypes = ['poaching', 'snare', 'injured_animal'];
        $statuses = ['pending', 'pending', 'pending', 'verified', 'rejected']; // weighted toward pending
        $landmarks = [
            'Near River Kaduna Bridge',
            'Kamuku National Park entrance',
            'Birnin Gwari forest area',
            'Kuyambana Game Reserve',
            'River Niger bank, Lokoja road',
            'Dagida Forest Reserve',
            'Old Oyo National Park boundary',
            'Zugurma Game Reserve',
        ];

        return [
            'reference_id' => 'WLS-'.now()->format('Ymd').'-'.strtoupper(fake()->unique()->lexify('?????')),
            'phone_number' => '+234'.fake()->numberBetween(7000000000, 9999999999),
            'incident_type' => fake()->randomElement($incidentTypes),
            'location' => fake()->randomElement($landmarks),
            'latitude' => fake()->optional()->latitude(7, 11),
            'longitude' => fake()->optional()->longitude(3, 11),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement($statuses),
            'verified_by' => null,
            'reward_amount' => 100.00,
            'reward_sent' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => 'verified',
            'verified_by' => User::factory(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'verified_by' => User::factory(),
        ]);
    }

    public function poaching(): static
    {
        return $this->state(fn () => ['incident_type' => 'poaching']);
    }

    public function snare(): static
    {
        return $this->state(fn () => ['incident_type' => 'snare']);
    }

    public function injuredAnimal(): static
    {
        return $this->state(fn () => ['incident_type' => 'injured_animal']);
    }
}
