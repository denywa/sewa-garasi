<?php

namespace Database\Factories;

use App\Enums\PaymentType;
use App\Models\Payment;
use App\Models\Rental;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Default: pembayaran sewa awal; end_date mengikuti tanggal selesai sewanya.
 *
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rental_id' => Rental::factory(),
            'type' => PaymentType::Initial,
            'amount' => 150_000,
            'proof_path' => 'payment-proofs/contoh.jpg',
            'end_date' => fn (array $attributes) => Rental::find($attributes['rental_id'])->end_date,
        ];
    }
}
