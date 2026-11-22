<?php

namespace Tests\Feature\Services;

use App\Enums\GarageType;
use App\Enums\PaymentType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\Vehicle;
use App\Services\RentalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * UC-04 Sewa Baru & UC-08 Upload Bukti Bayar di level service: hitung biaya dan simpan sewa.
 */
class RentalServiceCreateTest extends TestCase
{
    use RefreshDatabase;

    private RentalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-11-22');
        Storage::fake('public');
        $this->service = app(RentalService::class);
    }

    /**
     * Data form sewa: default mobil, sewa harian 3 hari.
     *
     * @param  array<string, mixed>  $override
     * @return array<string, mixed>
     */
    private function data(Garage $garage, array $override = []): array
    {
        return $override + [
            'vehicle_type' => VehicleType::Car,
            'plate_number' => 'b 1234 xy',
            'brand_model' => 'Toyota Avanza',
            'battery_capacity_kwh' => null,
            'battery_remaining_kwh' => null,
            'rental_type' => RentalType::Daily,
            'days' => 3,
            'garage_id' => $garage->id,
        ];
    }

    /**
     * Simpan sewa dengan bukti bayar palsu (gambar).
     *
     * @param  array<string, mixed>  $override
     */
    private function rent(Tenant $tenant, Garage $garage, array $override = []): Rental
    {
        return $this->service->create($tenant, $this->data($garage, $override), UploadedFile::fake()->image('bukti.jpg'));
    }

    /**
     * Jalankan $callback dan pastikan ditolak dengan pesan error pada $field.
     */
    private function assertRejected(string $field, string $message, callable $callback): void
    {
        try {
            $callback();
            $this->fail('Seharusnya ditolak dengan ValidationException.');
        } catch (ValidationException $e) {
            $this->assertSame([$message], $e->errors()[$field] ?? null);
        }
    }

    public function test_quote_harian_mobil(): void
    {
        $garage = Garage::factory()->create(['daily_price' => 50_000]);

        $this->assertSame(150_000, $this->service->quote($garage, RentalType::Daily, today(), 3, null, null));
    }

    public function test_quote_mobil_listrik_di_garasi_listrik(): void
    {
        $garage = Garage::factory()->create(['type' => GarageType::ElectricCar, 'daily_price' => 50_000, 'price_per_kwh' => 2_000]);

        // 50.000 × 2 hari + (60 − 20) kWh × 2.000
        $this->assertSame(180_000, $this->service->quote($garage, RentalType::Daily, today(), 2, 60, 20));
    }

    public function test_quote_mobil_listrik_di_garasi_mobil_tanpa_biaya_listrik(): void
    {
        $garage = Garage::factory()->create(['daily_price' => 50_000]);

        $this->assertSame(100_000, $this->service->quote($garage, RentalType::Daily, today(), 2, 60, 20));
    }

    public function test_create_menyimpan_kendaraan_sewa_dan_pembayaran(): void
    {
        $tenant = Tenant::factory()->create();
        $garage = Garage::factory()->create(['daily_price' => 50_000]);

        $rental = $this->rent($tenant, $garage);

        $vehicle = Vehicle::query()->sole();
        $this->assertSame('B1234XY', $vehicle->plate_number);
        $this->assertSame($tenant->user_id, $vehicle->tenant_id);

        $this->assertSame('2026-11-22', $rental->start_date->toDateString());
        $this->assertSame('2026-11-24', $rental->end_date->toDateString());
        $this->assertSame($vehicle->id, $rental->vehicle_id);

        $payment = $rental->payments()->sole();
        $this->assertSame(PaymentType::Initial, $payment->type);
        $this->assertSame(150_000, $payment->amount);
        $this->assertSame('2026-11-24', $payment->end_date->toDateString());
        Storage::disk('public')->assertExists($payment->proof_path);
    }

    public function test_mobil_listrik_menyimpan_kapasitas_dan_sisa_baterai(): void
    {
        $tenant = Tenant::factory()->create();
        $garage = Garage::factory()->create(['type' => GarageType::ElectricCar, 'daily_price' => 50_000, 'price_per_kwh' => 2_000]);

        $rental = $this->rent($tenant, $garage, [
            'vehicle_type' => VehicleType::ElectricCar,
            'battery_capacity_kwh' => 60,
            'battery_remaining_kwh' => 20,
            'days' => 2,
        ]);

        $this->assertSame(60.0, $rental->vehicle->battery_capacity_kwh);
        $this->assertSame(20.0, $rental->battery_remaining_kwh);
        $this->assertSame(180_000, $rental->payments()->sole()->amount);
    }

    public function test_garasi_yang_sudah_disewa_ditolak(): void
    {
        $garage = Garage::factory()->create();
        $this->rent(Tenant::factory()->create(), $garage);

        $this->assertRejected('garage_id', 'Garasi sudah tidak tersedia, pilih garasi lain.',
            fn () => $this->rent(Tenant::factory()->create(), $garage, ['plate_number' => 'D 1 AB']));

        $this->assertDatabaseCount('rentals', 1);
        $this->assertDatabaseCount('vehicles', 1);
    }

    public function test_tipe_garasi_tidak_sesuai_ditolak(): void
    {
        $garage = Garage::factory()->create(['type' => GarageType::Car]);

        $this->assertRejected('garage_id', 'Tipe garasi tidak sesuai kendaraan.',
            fn () => $this->rent(Tenant::factory()->create(), $garage, ['vehicle_type' => VehicleType::Motorcycle]));

        $this->assertDatabaseCount('rentals', 0);
    }

    public function test_plat_milik_penyewa_lain_ditolak(): void
    {
        Vehicle::factory()->create(['plate_number' => 'B1234XY']);

        $this->assertRejected('plate_number', 'Nomor plat sudah terdaftar.',
            fn () => $this->rent(Tenant::factory()->create(), Garage::factory()->create()));
    }

    public function test_kendaraan_dengan_sewa_aktif_ditolak(): void
    {
        $tenant = Tenant::factory()->create();
        $this->rent($tenant, Garage::factory()->create());

        $this->assertRejected('plate_number', 'Kendaraan masih menempati garasi lain.',
            fn () => $this->rent($tenant, Garage::factory()->create(), ['plate_number' => 'B-1234-XY']));

        $this->assertDatabaseCount('rentals', 1);
    }

    public function test_kendaraan_dipakai_ulang_setelah_sewa_selesai(): void
    {
        $tenant = Tenant::factory()->create();
        $vehicle = Vehicle::factory()->for($tenant)->create(['plate_number' => 'B1234XY', 'brand_model' => 'Lama']);
        Rental::factory()->for($vehicle)->create(['start_date' => '2026-11-18', 'end_date' => '2026-11-21']);

        $rental = $this->rent($tenant, Garage::factory()->create(), ['brand_model' => 'Toyota Avanza']);

        $this->assertDatabaseCount('vehicles', 1);
        $this->assertSame($vehicle->id, $rental->vehicle_id);
        $this->assertSame('Toyota Avanza', $vehicle->fresh()->brand_model);
    }

    public function test_kapasitas_berbeda_ditolak(): void
    {
        $tenant = Tenant::factory()->create();
        Vehicle::factory()->for($tenant)->create([
            'type' => VehicleType::ElectricCar, 'plate_number' => 'B1234XY', 'battery_capacity_kwh' => 60,
        ]);

        $this->assertRejected('plate_number', 'Data kendaraan tidak sesuai dengan data sebelumnya.',
            fn () => $this->rent($tenant, Garage::factory()->create(), [
                'vehicle_type' => VehicleType::ElectricCar, 'battery_capacity_kwh' => 70, 'battery_remaining_kwh' => 10,
            ]));
    }

    public function test_sepeda_selalu_kendaraan_baru(): void
    {
        $tenant = Tenant::factory()->create();
        $bicycle = ['vehicle_type' => VehicleType::Bicycle, 'plate_number' => null, 'brand_model' => 'Polygon'];

        $this->rent($tenant, Garage::factory()->create(['type' => GarageType::Bicycle]), $bicycle);
        $this->rent($tenant, Garage::factory()->create(['type' => GarageType::Bicycle]), $bicycle);

        $this->assertDatabaseCount('vehicles', 2);
    }
}
