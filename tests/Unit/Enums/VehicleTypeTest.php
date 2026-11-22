<?php

namespace Tests\Unit\Enums;

use App\Enums\GarageType;
use App\Enums\VehicleType;
use PHPUnit\Framework\TestCase;

class VehicleTypeTest extends TestCase
{
    public function test_sepeda_dan_motor_hanya_garasi_sepeda(): void
    {
        $this->assertSame([GarageType::Bicycle], VehicleType::Bicycle->allowedGarageTypes());
        $this->assertSame([GarageType::Bicycle], VehicleType::Motorcycle->allowedGarageTypes());
    }

    public function test_mobil_hanya_garasi_mobil(): void
    {
        $this->assertSame([GarageType::Car], VehicleType::Car->allowedGarageTypes());
    }

    public function test_mobil_listrik_boleh_garasi_mobil_dan_listrik(): void
    {
        $this->assertSame(
            [GarageType::Car, GarageType::ElectricCar],
            VehicleType::ElectricCar->allowedGarageTypes(),
        );
    }

    public function test_hanya_sepeda_tanpa_plat(): void
    {
        $this->assertFalse(VehicleType::Bicycle->requiresPlate());
        $this->assertTrue(VehicleType::Motorcycle->requiresPlate());
        $this->assertTrue(VehicleType::Car->requiresPlate());
        $this->assertTrue(VehicleType::ElectricCar->requiresPlate());
    }

    public function test_hanya_mobil_listrik_yang_listrik(): void
    {
        $this->assertFalse(VehicleType::Bicycle->isElectric());
        $this->assertFalse(VehicleType::Motorcycle->isElectric());
        $this->assertFalse(VehicleType::Car->isElectric());
        $this->assertTrue(VehicleType::ElectricCar->isElectric());
    }

    public function test_label_kendaraan(): void
    {
        $this->assertSame('Sepeda', VehicleType::Bicycle->label());
        $this->assertSame('Sepeda Motor', VehicleType::Motorcycle->label());
        $this->assertSame('Mobil', VehicleType::Car->label());
        $this->assertSame('Mobil Listrik', VehicleType::ElectricCar->label());
    }
}
