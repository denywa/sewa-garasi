<?php

namespace App\Enums;

/**
 * Jenis pembayaran: sewa awal atau perpanjangan.
 */
enum PaymentType: string
{
    case Initial = 'initial';
    case Extension = 'extension';

    /**
     * Nama jenis pembayaran untuk ditampilkan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Initial => 'Sewa awal',
            self::Extension => 'Perpanjangan',
        };
    }
}
