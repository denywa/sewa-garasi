<?php

namespace Tests\Unit\Models;

use App\Models\Vehicle;
use PHPUnit\Framework\TestCase;

class VehicleTest extends TestCase
{
    public function test_normalisasi_plat(): void
    {
        $this->assertSame('B1234XY', Vehicle::normalizePlate('b 1234 xy'));
        $this->assertSame('B1234XY', Vehicle::normalizePlate('B-1234-XY'));
        $this->assertSame('B1234XY', Vehicle::normalizePlate('B1234XY'));
    }
}
