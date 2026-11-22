<?php

namespace Database\Factories;

use App\Enums\GarageType;
use App\Models\Garage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: garasi mobil Rp50.000/hari.
 *
 * @extends Factory<Garage>
 */
class GarageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => GarageType::Car,
            'number' => fake()->unique()->numberBetween(1, 999),
            'daily_price' => 50_000,
            'price_per_kwh' => null,
        ];
    }
}
