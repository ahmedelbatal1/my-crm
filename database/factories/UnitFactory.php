<?php

namespace Database\Factories;

use App\Enums\UnitStatus;
use App\Enums\UnitType;
use App\Models\Project;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Unit>
 */
class UnitFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(UnitType::cases()),
            'area' => fake()->randomFloat(2, 50, 800),
            'price' => fake()->randomFloat(2, 500000, 20000000),
            'status' => UnitStatus::Available,
        ];
    }

    /**
     * Fixture shortcut only. In the running app a Unit's status is derived from its
     * Deals by DealObserver — never set directly. Use this to stand up a "already
     * sold" starting state without also creating the winning Deal.
     */
    public function sold(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UnitStatus::Sold,
        ]);
    }

    /**
     * Fixture shortcut only — see sold().
     */
    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UnitStatus::Reserved,
        ]);
    }
}
