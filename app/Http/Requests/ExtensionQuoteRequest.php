<?php

namespace App\Http\Requests;

use App\Models\Rental;
use Illuminate\Foundation\Http\FormRequest;

/**
 * UC-07 langkah 3–4: tanggal selesai baru untuk menghitung sisa biaya (opsional; form awal tanpa tanggal).
 */
class ExtensionQuoteRequest extends FormRequest
{
    /**
     * Akses sudah dibatasi middleware role:admin.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['new_end_date' => ['sometimes', ...$this->newEndDateRules()]];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['new_end_date.after' => 'Tanggal selesai baru harus setelah '.$this->rental()->end_date->format('d-m-Y').'.'];
    }

    /**
     * Tanggal selesai baru harus setelah tanggal selesai lama (tanpa batas maksimal).
     *
     * @return list<string>
     */
    protected function newEndDateRules(): array
    {
        return ['required', 'date_format:Y-m-d', 'after:'.$this->rental()->end_date->toDateString()];
    }

    /**
     * Sewa dari parameter route {rental}.
     */
    protected function rental(): Rental
    {
        return $this->route('rental');
    }
}
