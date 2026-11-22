<?php

namespace App\Http\Requests;

/**
 * UC-07 langkah 5–6 + UC-08: simpan perpanjangan dengan tanggal selesai baru (wajib) dan bukti bayar.
 */
class ExtendRentalRequest extends ExtensionQuoteRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'new_end_date' => $this->newEndDateRules(),
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
