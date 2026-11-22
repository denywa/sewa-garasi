<?php

namespace Tests\Feature\Tenant;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * UC-04 Sewa Baru (form → pilih garasi → upload bukti → berhasil) dan UC-08 Upload Bukti Bayar.
 */
class NewRentalTest extends TestCase
{
    use RefreshDatabase;

    /** Rupiah dari Number::currency memakai spasi tak terputus (NBSP) setelah "Rp". */
    private const RP_150_000 = "Rp\u{00A0}150.000";

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22'); // November = 30 hari → harian maks. 29 hari
        Storage::fake('public');
        $this->tenant = Tenant::factory()->create();
        $this->actingAs($this->tenant->user);
    }

    /**
     * Data langkah 1: mobil, sewa harian 3 hari, garasi Mobil.
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function search(array $override = []): array
    {
        return $override + [
            'vehicle_type' => 'car',
            'plate_number' => 'B 1234 XY',
            'brand_model' => 'Toyota Avanza',
            'battery_capacity_kwh' => '',
            'battery_remaining_kwh' => '',
            'rental_type' => 'daily',
            'days' => 3,
            'garage_type' => 'car',
        ];
    }

    private function choose(array $override = []): TestResponse
    {
        return $this->from(route('tenant.rentals.create'))->get(route('tenant.rentals.choose', $this->search($override)));
    }

    private function store(Garage $garage, ?UploadedFile $proof = null): TestResponse
    {
        return $this->post(route('tenant.rentals.store'), $this->search() + [
            'garage_id' => $garage->id,
            'proof' => $proof ?? UploadedFile::fake()->image('bukti.jpg'),
        ]);
    }

    public function test_form_sewa_baru_tampil(): void
    {
        $this->get(route('tenant.rentals.create'))->assertOk()->assertSee('Sewa Baru');
    }

    public function test_halaman_pilih_garasi_menampilkan_garasi_tersedia_dan_total(): void
    {
        $used = Garage::factory()->create(['number' => 1, 'daily_price' => 50_000]);
        Rental::factory()->for($used)->create(['start_date' => '2026-11-21', 'end_date' => '2026-11-23']);
        Garage::factory()->create(['number' => 2, 'daily_price' => 50_000]);

        $this->choose()->assertOk()
            ->assertSee('C002')
            ->assertDontSee('C001')
            ->assertSee(self::RP_150_000)
            ->assertSee('22 Nov 2026')
            ->assertSee('24 Nov 2026');
    }

    public function test_rincian_biaya_listrik_tampil(): void
    {
        Garage::factory()->create(['type' => 'electric_car', 'number' => 1, 'daily_price' => 50_000, 'price_per_kwh' => 2_000]);

        $this->choose([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => '60',
            'battery_remaining_kwh' => '20',
            'days' => 2,
            'garage_type' => 'electric_car',
        ])->assertOk()
            // Rupiah memakai spasi tak terputus (NBSP) setelah "Rp".
            ->assertSeeInOrder(['E001 · Sewa', "Rp\u{00A0}100.000", '+ Listrik', "Rp\u{00A0}80.000", '=', "Rp\u{00A0}180.000"])
            ->assertSeeInOrder(['Sewa = 2 hari × harga harian', 'Listrik = (60 − 20)', '= 40 kWh × harga isi listrik per kWh']);
    }

    public function test_tidak_ada_garasi_tersedia(): void
    {
        $this->choose()->assertOk()->assertSee('Tidak ada garasi tersedia untuk tipe ini.');
    }

    public function test_kwh_dengan_koma_diterima(): void
    {
        $this->choose([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => '60,5',
            'battery_remaining_kwh' => '20,25',
            'garage_type' => 'electric_car',
        ])->assertOk()->assertSessionHasNoErrors();
    }

    public function test_sisa_baterai_melebihi_kapasitas_ditolak(): void
    {
        $this->choose([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => '60',
            'battery_remaining_kwh' => '70',
            'garage_type' => 'electric_car',
        ])->assertRedirect(route('tenant.rentals.create'))
            ->assertSessionHasErrors(['battery_remaining_kwh' => 'Sisa baterai tidak boleh melebihi kapasitas baterai.']);
    }

    public function test_sisa_baterai_wajib_di_garasi_mobil_listrik(): void
    {
        $this->choose([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => '60',
            'garage_type' => 'electric_car',
        ])->assertSessionHasErrors(['battery_remaining_kwh' => 'Sisa baterai wajib diisi untuk garasi Mobil Listrik.']);
    }

    public function test_mobil_listrik_di_garasi_mobil_tanpa_sisa_baterai(): void
    {
        $garage = Garage::factory()->create(['daily_price' => 50_000]);

        $this->post(route('tenant.rentals.store'), $this->search([
            'vehicle_type' => 'electric_car',
            'battery_capacity_kwh' => '60',
            'battery_remaining_kwh' => '',
        ]) + ['garage_id' => $garage->id, 'proof' => UploadedFile::fake()->image('bukti.jpg')])
            ->assertSessionHasNoErrors();

        $rental = Rental::query()->sole();
        $this->assertNull($rental->battery_remaining_kwh);
        $this->assertSame(60.0, $rental->vehicle->battery_capacity_kwh);
        $this->assertSame(150_000, $rental->payments()->sole()->amount);
    }

    public function test_harian_melebihi_batas_bulan_ditolak(): void
    {
        $this->choose(['days' => 30])->assertSessionHasErrors(['days' => 'Jumlah hari harus antara 1 dan 29.']);
    }

    public function test_bulanan_tidak_perlu_jumlah_hari(): void
    {
        $this->choose(['rental_type' => 'monthly', 'days' => ''])->assertOk()->assertSee('21 Des 2026');
    }

    public function test_mobil_ke_garasi_sepeda_ditolak(): void
    {
        $this->choose(['garage_type' => 'bicycle'])->assertSessionHasErrors(['garage_type' => 'Tipe garasi tidak sesuai tipe kendaraan.']);
    }

    public function test_plat_wajib_selain_sepeda(): void
    {
        foreach (['', '---'] as $plate) { // '---' kosong setelah dinormalisasi
            $this->choose(['plate_number' => $plate])->assertSessionHasErrors(['plate_number' => 'Nomor plat wajib diisi (kecuali sepeda).']);
        }
        $this->choose(['vehicle_type' => 'bicycle', 'plate_number' => '', 'garage_type' => 'bicycle'])->assertSessionHasNoErrors();
    }

    public function test_format_plat_indonesia(): void
    {
        foreach (['B 1234 XY', 'b-1234-xy', 'AB 1 C', 'D 12'] as $plate) {
            $this->choose(['plate_number' => $plate])->assertSessionHasNoErrors();
        }

        foreach (['asd', '123', 'B 12345 XY', 'ABC 123'] as $plate) {
            $this->choose(['plate_number' => $plate])
                ->assertSessionHasErrors(['plate_number' => 'Format nomor plat tidak valid (contoh: B 1234 XY).']);
        }
    }

    public function test_simpan_sewa_berhasil(): void
    {
        $garage = Garage::factory()->create(['number' => 7, 'daily_price' => 50_000]);

        $response = $this->store($garage);

        $rental = Rental::query()->sole();
        $response->assertRedirect(route('tenant.rentals.success', $rental));
        $this->get(route('tenant.rentals.success', $rental))->assertOk()
            ->assertSee('C007')
            ->assertSee('24 Nov 2026')
            ->assertSee(self::RP_150_000);
    }

    public function test_bukti_bukan_gambar_ditolak(): void
    {
        $this->store(Garage::factory()->create(), UploadedFile::fake()->create('a.pdf', 100))
            ->assertSessionHasErrors(['proof' => 'Bukti pembayaran harus berupa gambar.']);
        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_bukti_lebih_dari_2mb_ditolak(): void
    {
        $this->store(Garage::factory()->create(), UploadedFile::fake()->image('b.jpg')->size(3000))
            ->assertSessionHasErrors(['proof' => 'Bukti pembayaran maksimal 2048 KB.']);
    }

    public function test_error_dari_service_tampil_di_form(): void
    {
        $garage = Garage::factory()->create();
        Rental::factory()->for($garage)->create();

        $this->store($garage)->assertSessionHasErrors(['garage_id' => 'Garasi sudah tidak tersedia, pilih garasi lain.']);
    }

    public function test_halaman_berhasil_milik_penyewa_lain_404(): void
    {
        $this->get(route('tenant.rentals.success', Rental::factory()->create()))->assertNotFound();
    }
}
