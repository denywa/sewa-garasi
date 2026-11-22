<?php

namespace Tests\Unit\Enums;

use App\Enums\GarageType;
use PHPUnit\Framework\TestCase;

class GarageTypeTest extends TestCase
{
    public function test_prefix_kode_garasi(): void
    {
        $this->assertSame('S', GarageType::Bicycle->prefix());
        $this->assertSame('C', GarageType::Car->prefix());
        $this->assertSame('E', GarageType::ElectricCar->prefix());
    }

    public function test_label_garasi(): void
    {
        $this->assertSame('Sepeda', GarageType::Bicycle->label());
        $this->assertSame('Mobil', GarageType::Car->label());
        $this->assertSame('Mobil Listrik', GarageType::ElectricCar->label());
    }
}
