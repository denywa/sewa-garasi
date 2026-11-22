<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Enums\RentalType;
use App\Enums\VehicleType;
use App\Models\Vehicle;
use App\Services\RentalCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UC-04 langkah 1: data kendaraan, tipe sewa, dan tipe garasi yang dicari.
 */
class RentalSearchRequest extends FormRequest
{
    /**
     * Akses sudah dibatasi middleware role:tenant.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Rapikan input sebelum divalidasi: plat dinormalisasi ("b 1234-xy" → "B1234XY") agar format bisa dicek,
     * dan desimal boleh ditulis dengan koma (60,5 kWh → 60.5).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('plate_number'))) {
            $this->merge(['plate_number' => Vehicle::normalizePlate($this->input('plate_number'))]);
        }
        foreach (['battery_capacity_kwh', 'battery_remaining_kwh'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => str_replace(',', '.', $this->input($field))]);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $vehicleType = $this->enum('vehicle_type', VehicleType::class);
        $maxDays = app(RentalCalculator::class)->maxDailyDays(today());

        return [
            'vehicle_type' => ['required', Rule::enum(VehicleType::class)],
            // Wajib selain sepeda. Format plat Indonesia (sudah dinormalisasi): 1–2 huruf wilayah, 1–4 angka,
            // 0–3 huruf akhir. Contoh: B1234XY, AB1C, D12.
            'plate_number' => ['nullable', 'required_unless:vehicle_type,'.VehicleType::Bicycle->value, 'string', 'max:15', 'regex:/^[A-Z]{1,2}[0-9]{1,4}[A-Z]{0,3}$/'],
            'brand_model' => ['required', 'string', 'max:100'],
            // Kolom DECIMAL(6,2): maks. 9999.99 kWh.
            'battery_capacity_kwh' => ['nullable', 'required_if:vehicle_type,'.VehicleType::ElectricCar->value, 'numeric', 'gt:0', 'max:9999'],
            // Sisa baterai hanya dipakai untuk biaya isi penuh di garasi Mobil Listrik; di garasi lain diabaikan.
            'battery_remaining_kwh' => ['exclude_unless:garage_type,'.GarageType::ElectricCar->value, 'required', 'numeric', 'min:0', 'lte:battery_capacity_kwh'],
            'rental_type' => ['required', Rule::enum(RentalType::class)],
            // Sewa bulanan selalu N hari: kolom hari diabaikan.
            'days' => ['exclude_unless:rental_type,'.RentalType::Daily->value, 'required', 'integer', 'between:1,'.$maxDays],
            // Hanya tipe garasi yang cocok dengan kendaraan (mobil listrik → Mobil / Mobil Listrik).
            'garage_type' => ['required', Rule::enum(GarageType::class),
                Rule::in(array_map(fn (GarageType $type) => $type->value, $vehicleType?->allowedGarageTypes() ?? GarageType::cases()))],
        ];
    }

    /**
     * Data siap pakai untuk RentalService (enum & angka sudah dikonversi). Kapasitas hanya untuk mobil
     * listrik; sisa baterai hanya untuk sewa di garasi Mobil Listrik.
     *
     * @return array<string, mixed>
     */
    public function rentalData(): array
    {
        $vehicleType = $this->enum('vehicle_type', VehicleType::class);
        $rentalType = $this->enum('rental_type', RentalType::class);

        return [
            'vehicle_type' => $vehicleType,
            'plate_number' => $vehicleType->requiresPlate() ? $this->input('plate_number') : null,
            'brand_model' => $this->input('brand_model'),
            'battery_capacity_kwh' => $vehicleType->isElectric() ? $this->float('battery_capacity_kwh') : null,
            'battery_remaining_kwh' => $this->input('garage_type') === GarageType::ElectricCar->value
                ? $this->float('battery_remaining_kwh') : null,
            'rental_type' => $rentalType,
            'days' => $rentalType === RentalType::Daily ? $this->integer('days') : null,
        ];
    }
}
