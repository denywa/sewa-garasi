<?php

namespace Tests\Feature\Admin;

use App\Enums\GarageType;
use App\Enums\PaymentType;
use App\Enums\ReportType;
use App\Models\Garage;
use App\Models\Payment;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * UC-09 Laporan Sewa Garasi: jumlah transaksi & total pendapatan dari pembayaran, berdasarkan tanggal bayar.
 */
class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22');

        $a = Tenant::factory()->for(User::factory()->state(['name' => 'Andi']))->create();
        $b = Tenant::factory()->for(User::factory()->state(['name' => 'Budi']))->create();

        $this->pay($a, GarageType::Car, 100_000, '2026-03-05 10:00:00');
        $this->pay($b, GarageType::ElectricCar, 50_000, '2026-03-20 15:00:00');
        $this->pay($a, GarageType::Car, 25_000, '2026-11-02 09:00:00', PaymentType::Extension);
    }

    /**
     * Satu pembayaran pada sewa milik $tenant di garasi bertipe $garageType.
     */
    private function pay(Tenant $tenant, GarageType $garageType, int $amount, string $paidAt, PaymentType $type = PaymentType::Initial): void
    {
        $rental = Rental::factory()
            ->for(Vehicle::factory()->for($tenant))
            ->for(Garage::factory()->state([
                'type' => $garageType,
                // CHECK constraint: harga per kWh wajib untuk garasi Mobil Listrik, kosong untuk tipe lain.
                'price_per_kwh' => $garageType === GarageType::ElectricCar ? 2_000 : null,
            ]))
            ->create();

        Payment::factory()->for($rental)->create(['type' => $type, 'amount' => $amount, 'created_at' => $paidAt]);
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function report(array $query): TestResponse
    {
        return $this->actingAs(User::factory()->admin()->create())->get(route('admin.reports.index', $query));
    }

    /**
     * Baris laporan dari view: [label => [jumlah transaksi, total]].
     *
     * @return array<string, array{int, int}>
     */
    private function rows(TestResponse $response): array
    {
        return collect($response->assertOk()->viewData('rows'))
            ->mapWithKeys(fn (array $row) => [$row['label'] => [$row['count'], $row['total']]])
            ->all();
    }

    public function test_default_laporan_per_bulan_tahun_ini(): void
    {
        $this->report([])->assertOk()->assertViewHas('type', ReportType::Monthly)->assertViewHas('year', 2026)
            // Kolom tahun terisi tahun ini (bukan value kosong).
            ->assertDontSee('value=""', false);
    }

    public function test_laporan_per_bulan(): void
    {
        $rows = $this->rows($this->report(['type' => 'monthly', 'year' => 2026]));

        $this->assertCount(12, $rows);
        $this->assertSame([0, 0], $rows['Januari']);
        $this->assertSame([2, 150_000], $rows['Maret']);
        $this->assertSame([1, 25_000], $rows['November']);
    }

    public function test_laporan_per_hari(): void
    {
        $response = $this->report(['type' => 'daily', 'year' => 2026, 'month' => 3]);
        $rows = $this->rows($response);

        $this->assertCount(31, $rows);
        $this->assertSame([1, 100_000], $rows['05 Mar 2026']);
        $this->assertSame([1, 50_000], $rows['20 Mar 2026']);
        $this->assertSame([0, 0], $rows['06 Mar 2026']);
        // Baris total di bawah tabel (Rupiah memakai NBSP setelah "Rp").
        $response->assertSeeInOrder(['Total', '2', "Rp\u{00A0}150.000"]);
    }

    public function test_laporan_per_tipe_garasi(): void
    {
        $rows = $this->rows($this->report(['type' => 'garage_type', 'year' => 2026, 'month' => 3]));

        $this->assertSame(['Sepeda' => [0, 0], 'Mobil' => [1, 100_000], 'Mobil Listrik' => [1, 50_000]], $rows);
    }

    public function test_laporan_per_penyewa(): void
    {
        $rows = $this->rows($this->report(['type' => 'tenant', 'year' => 2026, 'month' => 11]));

        $this->assertCount(1, $rows);
        $this->assertSame([1, 25_000], array_values($rows)[0]);
        $this->assertStringStartsWith('Andi', array_key_first($rows));
    }

    public function test_periode_tanpa_transaksi_bernilai_nol(): void
    {
        $this->report(['type' => 'tenant', 'year' => 2025, 'month' => 1])->assertOk()->assertSee('Tidak ada transaksi');
    }

    public function test_bulan_tidak_valid_ditolak(): void
    {
        $this->report(['type' => 'daily', 'year' => 2026, 'month' => 13])->assertSessionHasErrors('month');
    }

    public function test_penyewa_tidak_bisa_lihat_laporan(): void
    {
        $this->actingAs(Tenant::factory()->create()->user)->get(route('admin.reports.index'))->assertForbidden();
    }
}
