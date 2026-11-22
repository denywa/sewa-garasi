<?php

namespace Database\Factories;

use App\Enums\RentalType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: sewa harian 3 hari mulai hari ini.
 *
 * @extends Factory<Rental>
 */
class RentalFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vehicle_id' => Vehicle::factory(),
            'garage_id' => Garage::factory(),
            'rental_type' => RentalType::Daily,
            'start_date' => today(),
            'end_date' => today()->addDays(2),
            'battery_remaining_kwh' => null,
        ];
    }
}
