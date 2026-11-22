<?php

namespace Tests\Unit\Services;

use App\Enums\GarageType;
use App\Services\GarageCodeGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class GarageCodeGeneratorTest extends TestCase
{
    private GarageCodeGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new GarageCodeGenerator;
    }

    public function test_garasi_pertama(): void
    {
        $this->assertSame([1], $this->generator->nextNumbers(0, 1));
        $this->assertSame('S001', $this->generator->format(GarageType::Bicycle, 1));
    }

    public function test_melanjutkan_nomor_terakhir(): void
    {
        $this->assertSame([10, 11], $this->generator->nextNumbers(9, 2));
        $this->assertSame('C010', $this->generator->format(GarageType::Car, 10));
    }

    public function test_tepat_sampai_999(): void
    {
        $numbers = $this->generator->nextNumbers(990, 9);

        $this->assertCount(9, $numbers);
        $this->assertSame(999, end($numbers));
        $this->assertSame('E999', $this->generator->format(GarageType::ElectricCar, 999));
    }

    public function test_melebihi_999_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Sisa kuota kode tipe ini: 9.');

        $this->generator->nextNumbers(990, 10);
    }

    public function test_jumlah_nol_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->generator->nextNumbers(0, 0);
    }
}
