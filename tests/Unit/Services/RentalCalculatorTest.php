<?php

namespace Tests\Unit\Services;

use App\Enums\RentalType;
use App\Services\RentalCalculator;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RentalCalculatorTest extends TestCase
{
    private RentalCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new RentalCalculator;
    }

    private function date(string $date): CarbonImmutable
    {
        return CarbonImmutable::parse($date);
    }

    // --- Jumlah hari & tanggal selesai ---

    public function test_tanggal_selesai_harian_inklusif(): void
    {
        $end = $this->calculator->endDate($this->date('2026-11-21'), 3);

        $this->assertSame('2026-11-23', $end->toDateString());
    }

    public function test_bulanan_februari_28_hari(): void
    {
        $start = $this->date('2027-02-10');
        $days = $this->calculator->rentalDays(RentalType::Monthly, $start, null);

        $this->assertSame(28, $days);
        $this->assertSame('2027-03-09', $this->calculator->endDate($start, $days)->toDateString());
    }

    public function test_bulanan_februari_kabisat_29_hari(): void
    {
        $days = $this->calculator->rentalDays(RentalType::Monthly, $this->date('2028-02-29'), null);

        $this->assertSame(29, $days);
    }

    public function test_bulanan_mulai_31_januari(): void
    {
        $start = $this->date('2027-01-31');
        $days = $this->calculator->rentalDays(RentalType::Monthly, $start, null);

        $this->assertSame(31, $days);
        $this->assertSame('2027-03-02', $this->calculator->endDate($start, $days)->toDateString());
    }

    public function test_harian_maks_27_hari_di_februari(): void
    {
        $days = $this->calculator->rentalDays(RentalType::Daily, $this->date('2027-02-10'), 27);

        $this->assertSame(27, $days);
    }

    public function test_harian_28_hari_di_februari_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->rentalDays(RentalType::Daily, $this->date('2027-02-10'), 28);
    }

    public function test_harian_0_hari_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->rentalDays(RentalType::Daily, $this->date('2026-11-22'), 0);
    }

    public function test_harian_maks_29_hari_di_november(): void
    {
        $days = $this->calculator->rentalDays(RentalType::Daily, $this->date('2026-11-30'), 29);

        $this->assertSame(29, $days);
    }

    // --- Biaya sewa ---

    public function test_biaya_harian(): void
    {
        $this->assertSame(30_000, $this->calculator->rentCost(10_000, RentalType::Daily, 3));
    }

    public function test_biaya_bulanan_25_kali_harian(): void
    {
        $this->assertSame(250_000, $this->calculator->rentCost(10_000, RentalType::Monthly, 31));
    }

    // --- Biaya isi listrik ---

    public function test_biaya_listrik(): void
    {
        $this->assertSame(80_000, $this->calculator->chargingCost(60, 20, 2_000));
    }

    public function test_biaya_listrik_dibulatkan(): void
    {
        // (60.5 − 20.25) × 1.333 = 53.653,25 → dibulatkan 53.653
        $this->assertSame(53_653, $this->calculator->chargingCost(60.5, 20.25, 1_333));
    }

    public function test_biaya_listrik_nol_bila_penuh(): void
    {
        $this->assertSame(0, $this->calculator->chargingCost(60, 60, 2_000));
    }

    public function test_sisa_melebihi_kapasitas_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->chargingCost(60, 61, 2_000);
    }

    public function test_sisa_negatif_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->chargingCost(60, -1, 2_000);
    }

    // --- Biaya perpanjangan ---

    public function test_biaya_perpanjangan(): void
    {
        $cost = $this->calculator->extensionCost($this->date('2026-11-23'), $this->date('2026-11-26'), 10_000);

        $this->assertSame(30_000, $cost);
    }

    public function test_perpanjangan_tanggal_mundur_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->extensionCost($this->date('2026-11-23'), $this->date('2026-11-23'), 10_000);
    }
}
