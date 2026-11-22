<?php

namespace App\Services;

use App\Enums\GarageType;
use App\Enums\PaymentType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Garage;
use App\Models\Rental;
use App\Models\Tenant;
use App\Models\Vehicle;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * Alur sewa yang memakai database: hitung total biaya dan simpan sewa baru (UC-04, UC-08).
 * Perhitungan angka didelegasikan ke RentalCalculator (murni, tanpa database).
 */
final class RentalService
{
    public function __construct(private readonly RentalCalculator $calculator) {}

    /**
     * Rincian biaya sewa awal: biaya sewa garasi dan biaya isi listrik (0 bila bukan garasi Mobil Listrik).
     *
     * @return array{rent: int, charging: int}
     *
     * @throws InvalidArgumentException bila jumlah hari atau sisa baterai tidak valid
     */
    public function costBreakdown(Garage $garage, RentalType $type, CarbonInterface $start, ?int $days, ?float $capacityKwh, ?float $remainingKwh): array
    {
        $rentalDays = $this->calculator->rentalDays($type, $start, $days);

        return [
            'rent' => $this->calculator->rentCost($garage->daily_price, $type, $rentalDays),
            // Garasi Mobil Listrik hanya untuk mobil listrik; kapasitas & sisa sudah wajib diisi (request / create).
            'charging' => $garage->type === GarageType::ElectricCar
                ? $this->calculator->chargingCost((float) $capacityKwh, (float) $remainingKwh, $garage->price_per_kwh)
                : 0,
        ];
    }

    /**
     * Total biaya sewa awal = biaya sewa + biaya isi listrik.
     *
     * @throws InvalidArgumentException bila jumlah hari atau sisa baterai tidak valid
     */
    public function quote(Garage $garage, RentalType $type, CarbonInterface $start, ?int $days, ?float $capacityKwh, ?float $remainingKwh): int
    {
        return array_sum($this->costBreakdown($garage, $type, $start, $days, $capacityKwh, $remainingKwh));
    }

    /**
     * Simpan sewa baru mulai hari ini: kendaraan (baru/dipakai ulang), sewa, dan pembayaran awal + bukti bayar.
     * Semua dalam satu transaksi; pelanggaran aturan → ValidationException dengan key = nama field form.
     *
     * @param  array{vehicle_type: VehicleType, plate_number: ?string, brand_model: string,
     *     battery_capacity_kwh: ?float, battery_remaining_kwh: ?float, rental_type: RentalType,
     *     days: ?int, garage_id: int}  $data
     *
     * @throws ValidationException
     */
    public function create(Tenant $tenant, array $data, UploadedFile $proof): Rental
    {
        $start = today();
        /** @var VehicleType $vehicleType */
        $vehicleType = $data['vehicle_type'];

        return DB::transaction(function () use ($tenant, $data, $proof, $start, $vehicleType) {
            // 1. Kunci baris garasi sampai transaksi selesai: penyewa lain yang memilih garasi yang sama
            //    harus menunggu, lalu melihat sewa ini (cegah double booking).
            $garage = Garage::query()->lockForUpdate()->findOrFail($data['garage_id']);

            if (! in_array($garage->type, $vehicleType->allowedGarageTypes(), true)) {
                throw ValidationException::withMessages(['garage_id' => 'Tipe garasi tidak sesuai kendaraan.']);
            }
            if ($garage->rentals()->activeOn($start)->exists()) {
                throw ValidationException::withMessages(['garage_id' => 'Garasi sudah tidak tersedia, pilih garasi lain.']);
            }
            // Sisa baterai hanya dibutuhkan untuk biaya isi penuh di garasi Mobil Listrik.
            $electricGarage = $garage->type === GarageType::ElectricCar;
            if ($electricGarage && $data['battery_remaining_kwh'] === null) {
                throw ValidationException::withMessages(['battery_remaining_kwh' => 'Sisa baterai wajib diisi untuk garasi Mobil Listrik.']);
            }

            // 2. Kendaraan baru atau milik sendiri yang dipakai ulang.
            $vehicle = $this->findOrCreateVehicle($tenant, $data, $vehicleType, $start);

            // 3. Hitung tanggal & biaya.
            try {
                $days = $this->calculator->rentalDays($data['rental_type'], $start, $data['days']);
            } catch (InvalidArgumentException $e) {
                throw ValidationException::withMessages(['days' => $e->getMessage()]);
            }
            try {
                $amount = $this->quote($garage, $data['rental_type'], $start, $data['days'],
                    $data['battery_capacity_kwh'], $data['battery_remaining_kwh']);
            } catch (InvalidArgumentException $e) {
                throw ValidationException::withMessages(['battery_remaining_kwh' => $e->getMessage()]);
            }
            $end = $this->calculator->endDate($start, $days);

            // 4. Simpan sewa & pembayaran awal. File bukti disimpan paling akhir, setelah semua pengecekan lolos.
            $rental = Rental::create([
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rental_type' => $data['rental_type'],
                'start_date' => $start,
                'end_date' => $end,
                'battery_remaining_kwh' => $electricGarage ? $data['battery_remaining_kwh'] : null,
            ]);

            $rental->payments()->create([
                'type' => PaymentType::Initial,
                'amount' => $amount,
                'proof_path' => $proof->store('payment-proofs', 'public'),
                'end_date' => $end,
            ]);

            return $rental;
        });
    }

    /**
     * Sepeda selalu data baru. Kendaraan berplat dicari lewat plat yang dinormalisasi:
     * milik penyewa lain / data berbeda / masih parkir → ditolak; milik sendiri & cocok → dipakai ulang.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function findOrCreateVehicle(Tenant $tenant, array $data, VehicleType $type, CarbonInterface $today): Vehicle
    {
        $capacity = $type->isElectric() ? round((float) $data['battery_capacity_kwh'], 2) : null;

        if (! $type->requiresPlate()) {
            return $tenant->vehicles()->create([
                'type' => $type,
                'plate_number' => null,
                'brand_model' => $data['brand_model'],
                'battery_capacity_kwh' => null,
            ]);
        }

        $plate = Vehicle::normalizePlate((string) $data['plate_number']);
        // Kunci juga baris kendaraan: kendaraan yang sama tidak bisa disewakan dua kali bersamaan.
        $vehicle = Vehicle::query()->where('plate_number', $plate)->lockForUpdate()->first();

        if ($vehicle === null) {
            return $tenant->vehicles()->create([
                'type' => $type,
                'plate_number' => $plate,
                'brand_model' => $data['brand_model'],
                'battery_capacity_kwh' => $capacity,
            ]);
        }

        if ($vehicle->tenant_id !== $tenant->user_id) {
            throw ValidationException::withMessages(['plate_number' => 'Nomor plat sudah terdaftar.']);
        }
        if ($vehicle->type !== $type || $vehicle->battery_capacity_kwh !== $capacity) {
            throw ValidationException::withMessages(['plate_number' => 'Data kendaraan tidak sesuai dengan data sebelumnya.']);
        }
        if ($vehicle->hasActiveRental($today)) {
            throw ValidationException::withMessages(['plate_number' => 'Kendaraan masih menempati garasi lain.']);
        }

        // Merek & tipe boleh berubah (mis. salah ketik sebelumnya).
        $vehicle->update(['brand_model' => $data['brand_model']]);

        return $vehicle;
    }
}
