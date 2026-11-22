<?php

namespace Tests\Feature\Models;

use App\Models\Rental;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class RentalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22');
    }

    public function test_sewa_aktif_termasuk_hari_terakhir(): void
    {
        $endsToday = Rental::factory()->create(['start_date' => '2026-11-20', 'end_date' => '2026-11-22']);
        Rental::factory()->create(['start_date' => '2026-11-18', 'end_date' => '2026-11-21']);

        $active = Rental::query()->activeOn(Carbon::today())->pluck('id')->all();

        $this->assertSame([$endsToday->id], $active);
    }

    public function test_kendaraan_punya_sewa_aktif(): void
    {
        $parked = Vehicle::factory()->create();
        Rental::factory()->for($parked)->create(['start_date' => '2026-11-21', 'end_date' => '2026-11-23']);
        $done = Vehicle::factory()->create();
        Rental::factory()->for($done)->create(['start_date' => '2026-11-18', 'end_date' => '2026-11-21']);

        $this->assertTrue($parked->hasActiveRental(Carbon::today()));
        $this->assertFalse($done->hasActiveRental(Carbon::today()));
    }
}
