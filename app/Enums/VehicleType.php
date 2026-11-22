<?php

namespace App\Enums;

/**
 * Tipe kendaraan beserta aturan garasi yang boleh dipakai.
 */
enum VehicleType: string
{
    case Bicycle = 'bicycle';
    case Motorcycle = 'motorcycle';
    case Car = 'car';
    case ElectricCar = 'electric_car';

    /**
     * Tipe garasi yang boleh ditempati kendaraan ini.
     *
     * @return list<GarageType>
     */
    public function allowedGarageTypes(): array
    {
        return match ($this) {
            self::Bicycle, self::Motorcycle => [GarageType::Bicycle],
            self::Car => [GarageType::Car],
            self::ElectricCar => [GarageType::Car, GarageType::ElectricCar],
        };
    }

    /**
     * Semua kendaraan wajib punya nomor plat, kecuali sepeda.
     */
    public function requiresPlate(): bool
    {
        return $this !== self::Bicycle;
    }

    /**
     * Hanya mobil listrik yang punya kapasitas & sisa baterai.
     */
    public function isElectric(): bool
    {
        return $this === self::ElectricCar;
    }

    /**
     * Nama tipe kendaraan untuk ditampilkan.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bicycle => 'Sepeda',
            self::Motorcycle => 'Sepeda Motor',
            self::Car => 'Mobil',
            self::ElectricCar => 'Mobil Listrik',
        };
    }
}
