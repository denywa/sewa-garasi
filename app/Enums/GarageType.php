<?php

namespace App\Enums;

/**
 * Tipe garasi. Prefix dipakai untuk membentuk kode garasi (S001, C010, E005).
 */
enum GarageType: string
{
    case Bicycle = 'bicycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    /**
     * Huruf awal kode garasi untuk tipe ini.
     */
    public function prefix(): string
    {
        return match ($this) {
            self::Bicycle => 'S',
            self::Car => 'C',
            self::ElectricCar => 'E',
        };
    }

    /**
     * Nama tipe garasi untuk ditampilkan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Car => 'Mobil',
            self::ElectricCar => 'Mobil Listrik',
        };
    }
}
