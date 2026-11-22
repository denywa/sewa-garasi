<?php

namespace Database\Factories;

use App\Enums\VehicleType;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: mobil biasa berplat.
 *
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => VehicleType::Car,
            'plate_number' => strtoupper(fake()->unique()->bothify('B####???')),
            'brand_model' => fake()->randomElement(['Toyota Avanza', 'Honda Jazz', 'Suzuki Ertiga']),
            'battery_capacity_kwh' => null,
        ];
    }
}
