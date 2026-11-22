<?php

namespace Database\Seeders;

use App\Enums\GarageType;
use App\Enums\PaymentType;
use App\Enums\RentalType;
use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RentalCalculator;
use App\Services\RentalService;
use Carbon\CarbonInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Data demo untuk presentasi. Tanggal dihitung relatif dari hari ini, sehingga selalu ada sewa aktif,
 * sewa selesai, dan pembayaran di beberapa bulan (untuk laporan). Semua password: "password".
 */
class DemoSeeder extends Seeder
{
    /** Satu gambar bukti bayar contoh (PNG 1×1) yang dipakai semua pembayaran demo. */
    private const PROOF_PATH = 'payment-proofs/contoh-bukti.png';

    public function __construct(
        private readonly RentalService $service,
        private readonly RentalCalculator $calculator,
    ) {}

    public function run(): void
    {
        Storage::disk('public')->put(self::PROOF_PATH, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ));

        // Penyewa dengan semua status (UC-06: Rina menunggu verifikasi).
        $budi = $this->tenant('Budi Santoso', 'budi@garasi.test', '3171010101900001', '081234567801', TenantStatus::Approved);
        $siti = $this->tenant('Siti Aminah', 'siti@garasi.test', '3273020202920002', '+6281234567802', TenantStatus::Approved);
        $this->tenant('Rina Kartika', 'rina@garasi.test', '3578030303950003', '081234567803', TenantStatus::Pending);
        $this->tenant('Joko Prasetyo', 'joko@garasi.test', '3374040404880004', '081234567804', TenantStatus::Rejected);

        // Garasi S001–S005, C001–C005, E001–E003 (UC-05).
        $bicycle = $this->garages(GarageType::Bicycle, 5, 5_000);
        $car = $this->garages(GarageType::Car, 5, 50_000);
        $electric = $this->garages(GarageType::ElectricCar, 3, 75_000, 2_500);

        // Kendaraan.
        $budiCar = $budi->vehicles()->create(['type' => VehicleType::Car, 'plate_number' => 'B1234XY', 'brand_model' => 'Toyota Avanza']);
        $sitiMotor = $siti->vehicles()->create(['type' => VehicleType::Motorcycle, 'plate_number' => 'D4567AB', 'brand_model' => 'Honda Beat']);
        $sitiEv = $siti->vehicles()->create([
            'type' => VehicleType::ElectricCar, 'plate_number' => 'B88EV', 'brand_model' => 'Hyundai Ioniq 5', 'battery_capacity_kwh' => 60,
        ]);

        $today = today();

        // Sewa yang sudah selesai (riwayat & laporan bulan-bulan sebelumnya).
        $this->rent($budiCar, $car[0], RentalType::Daily, $today->copy()->subDays(75), 3);
        $this->rent($budiCar, $car[1], RentalType::Monthly, $today->copy()->subDays(45));
        $this->rent($sitiMotor, $bicycle[0], RentalType::Daily, $today->copy()->subDays(20), 2);

        // Sewa aktif hari ini (UC-07: bisa diperpanjang).
        $budiActive = $this->rent($budiCar, $car[0], RentalType::Daily, $today->copy()->subDay(), 3);
        $this->rent($sitiMotor, $bicycle[1], RentalType::Monthly, $today->copy()->subDays(10));
        $this->rent($sitiEv, $electric[0], RentalType::Daily, $today, 3, 20); // isi daya 60 − 20 = 40 kWh

        // Perpanjangan oleh admin hari ini: +2 hari.
        $this->extend($budiActive, $budiActive->end_date->copy()->addDays(2), $today);
    }

    private function tenant(string $name, string $email, string $nik, string $phone, TenantStatus $status): Tenant
    {
        $user = User::create(['role' => UserRole::Tenant, 'name' => $name, 'email' => $email, 'password' => 'password']);

        return $user->tenant()->create(['nik' => $nik, 'phone' => $phone, 'status' => $status]);
    }

    /**
     * @return list<Garage>
     */
    private function garages(GarageType $type, int $count, int $dailyPrice, ?int $pricePerKwh = null): array
    {
        return array_map(fn (int $number) => Garage::create([
            'type' => $type, 'number' => $number, 'daily_price' => $dailyPrice, 'price_per_kwh' => $pricePerKwh,
        ]), range(1, $count));
    }

    /**
     * Sewa + pembayaran awal pada tanggal mulai. Biaya & tanggal selesai memakai logika aplikasi yang sama.
     */
    private function rent(Vehicle $vehicle, Garage $garage, RentalType $type, CarbonInterface $start, ?int $days = null, ?float $remainingKwh = null): Rental
    {
        $end = $this->calculator->endDate($start, $this->calculator->rentalDays($type, $start, $days));
        $amount = $this->service->quote($garage, $type, $start, $days, $vehicle->battery_capacity_kwh, $remainingKwh);

        $rental = Rental::create([
            'vehicle_id' => $vehicle->id, 'garage_id' => $garage->id, 'rental_type' => $type,
            'start_date' => $start, 'end_date' => $end, 'battery_remaining_kwh' => $remainingKwh,
        ]);
        $this->pay($rental, PaymentType::Initial, $amount, $end, $start);

        return $rental;
    }

    /**
     * Perpanjangan seperti UC-07: tanggal selesai baru + pembayaran sisa biaya.
     */
    private function extend(Rental $rental, CarbonInterface $newEnd, CarbonInterface $paidAt): void
    {
        $amount = $this->calculator->extensionCost($rental->end_date, $newEnd, $rental->garage->daily_price);
        $rental->update(['end_date' => $newEnd]);
        $this->pay($rental, PaymentType::Extension, $amount, $newEnd, $paidAt);
    }

    private function pay(Rental $rental, PaymentType $type, int $amount, CarbonInterface $end, CarbonInterface $paidAt): void
    {
        // created_at = tanggal bayar (dasar laporan); diisi manual agar data tersebar di beberapa bulan.
        $rental->payments()->forceCreate([
            'type' => $type, 'amount' => $amount, 'proof_path' => self::PROOF_PATH,
            'end_date' => $end, 'created_at' => $paidAt, 'updated_at' => $paidAt,
        ]);
    }
}
