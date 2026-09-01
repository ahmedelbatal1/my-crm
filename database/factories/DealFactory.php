<?php

namespace Database\Factories;

use App\Enums\DealStage;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'unit_id' => Unit::factory(),
            'full_price' => fake()->randomFloat(2, 500000, 20000000),
            'deposit_amount' => null,
            'deposit_paid_at' => null,
            'stage' => DealStage::Lead,
        ];
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => DealStage::Reserved,
            'deposit_amount' => round(($attributes['full_price'] ?? 1000000) * 0.1, 2),
            'deposit_paid_at' => now()->subDays(7)->toDateString(),
        ]);
    }

    public function contractedWon(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => DealStage::ContractedWon,
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (array $attributes) => [
            'stage' => DealStage::Lost,
        ]);
    }
}
