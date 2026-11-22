<?php

namespace Tests\Feature\Models;

use App\Enums\GarageType;
use App\Models\Garage;
use App\Models\Rental;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GarageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22');
    }

    private function isAvailableToday(Garage $garage): bool
    {
        return Garage::query()->availableOn(Carbon::today())->whereKey($garage->id)->exists();
    }

    public function test_kode_garasi(): void
    {
        $garage = Garage::factory()->make(['type' => GarageType::Car, 'number' => 10]);

        $this->assertSame('C010', $garage->code());
    }

    public function test_garasi_disewa_hari_ini_tidak_tersedia(): void
    {
        $garage = Garage::factory()->create();
        Rental::factory()->for($garage)->create(['start_date' => '2026-11-21', 'end_date' => '2026-11-23']);

        $this->assertFalse($this->isAvailableToday($garage));
    }

    public function test_garasi_tersedia_setelah_sewa_selesai(): void
    {
        $garage = Garage::factory()->create();
        Rental::factory()->for($garage)->create(['start_date' => '2026-11-18', 'end_date' => '2026-11-21']);

        $this->assertTrue($this->isAvailableToday($garage));
    }
}
