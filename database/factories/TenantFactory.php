<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: penyewa yang sudah disetujui (approved), karena itu yang dibutuhkan sebagian besar test.
 *
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nik' => fake()->unique()->numerify('################'),
            'phone' => fake()->numerify('08##########'),
            'status' => TenantStatus::Approved,
        ];
    }
}
