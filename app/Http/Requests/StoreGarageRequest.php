<?php

namespace App\Http\Requests;

use App\Enums\GarageType;
use App\Services\GarageCodeGenerator;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi form Buat Garasi (UC-05). Kuota 999 per tipe dicek di controller (butuh nomor terakhir dari DB).
 */
class StoreGarageRequest extends FormRequest
{
    /**
     * Hak akses admin sudah dijaga middleware role:admin.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(GarageType::class)],
            'count' => ['required', 'integer', 'between:1,'.GarageCodeGenerator::MAX_NUMBER],
            'daily_price' => ['required', 'integer', 'min:1'],
            // Wajib untuk Mobil Listrik, harus kosong untuk tipe lain.
            'price_per_kwh' => [
                'nullable',
                'required_if:type,'.GarageType::ElectricCar->value,
                'prohibited_unless:type,'.GarageType::ElectricCar->value,
                'integer', 'min:1',
            ],
        ];
    }
}
