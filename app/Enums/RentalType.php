<?php

namespace App\Enums;

/**
 * Tipe sewa: harian (1 … N−1 hari) atau bulanan (N hari, 25 × harga harian).
 */
enum RentalType: string
{
    case Daily = 'daily';
    case Monthly = 'monthly';

    /**
     * Nama tipe sewa untuk ditampilkan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Daily => 'Harian',
            self::Monthly => 'Bulanan',
        };
    }
}
