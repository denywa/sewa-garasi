<?php

namespace Tests\Feature\Tenant;

use App\Enums\PaymentType;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * UC-10 Lihat Sewa Saya (fitur pelengkap): daftar sewa milik penyewa & riwayat pembayarannya.
 */
class MyRentalsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22');
        $this->tenant = Tenant::factory()->create();
        $this->actingAs($this->tenant->user);
    }

    /**
     * Sewa milik $tenant di garasi Mobil bernomor $number, dengan satu pembayaran awal.
     */
    private function rental(Tenant $tenant, int $number, string $start, string $end, int $amount = 150_000): Rental
    {
        $rental = Rental::factory()
            ->for(Vehicle::factory()->for($tenant))
            ->for(Garage::factory()->state(['number' => $number]))
            ->create(['start_date' => $start, 'end_date' => $end]);
        Payment::factory()->for($rental)->create(['amount' => $amount, 'end_date' => $end, 'created_at' => $start]);

        return $rental;
    }

    public function test_belum_ada_sewa(): void
    {
        $this->get(route('tenant.rentals.index'))->assertOk()->assertSee('Belum ada sewa.');
    }

    public function test_hanya_menampilkan_sewa_sendiri(): void
    {
        $this->rental($this->tenant, 1, '2026-11-20', '2026-11-24');
        $this->rental(Tenant::factory()->create(), 2, '2026-11-20', '2026-11-24');

        $this->get(route('tenant.rentals.index'))->assertOk()
            ->assertSee('C001')
            ->assertDontSee('C002');
    }

    public function test_status_aktif_dan_selesai_terbaru_di_atas(): void
    {
        $this->rental($this->tenant, 1, '2026-11-01', '2026-11-21'); // selesai kemarin
        $this->rental($this->tenant, 2, '2026-11-20', '2026-11-22'); // berakhir hari ini → masih aktif

        $this->get(route('tenant.rentals.index'))->assertOk()
            ->assertSeeInOrder(['C002', 'Aktif', 'C001', 'Selesai']);
    }

    public function test_total_dibayar_termasuk_perpanjangan(): void
    {
        $rental = $this->rental($this->tenant, 1, '2026-11-20', '2026-11-24');
        Payment::factory()->for($rental)->create(['type' => PaymentType::Extension, 'amount' => 50_000, 'end_date' => '2026-11-25']);

        // Rupiah memakai spasi tak terputus (NBSP) setelah "Rp".
        $this->get(route('tenant.rentals.index'))->assertSee("Rp\u{00A0}200.000");
    }

    public function test_detail_menampilkan_riwayat_pembayaran(): void
    {
        $rental = $this->rental($this->tenant, 1, '2026-11-20', '2026-11-24');
        Payment::factory()->for($rental)->create([
            'type' => PaymentType::Extension, 'amount' => 50_000, 'end_date' => '2026-11-25', 'created_at' => '2026-11-22',
        ]);

        $this->get(route('tenant.rentals.show', $rental))->assertOk()
            ->assertSeeInOrder([
                '20 Nov 2026', 'Sewa awal', "Rp\u{00A0}150.000", '24 Nov 2026',
                '22 Nov 2026', 'Perpanjangan', "Rp\u{00A0}50.000", '25 Nov 2026',
            ]);
    }

    public function test_tidak_bisa_buka_sewa_penyewa_lain(): void
    {
        $other = $this->rental(Tenant::factory()->create(), 2, '2026-11-20', '2026-11-24');

        $this->get(route('tenant.rentals.show', $other))->assertNotFound();
    }
}
