<?php

namespace App\Http\Requests;

/**
 * UC-04 langkah 2 + UC-08: data langkah 1 (dikirim ulang lewat hidden field), garasi terpilih, dan bukti bayar.
 */
class StoreRentalRequest extends RentalSearchRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules() + [
            'garage_id' => ['required', 'integer', 'exists:garages,id'],
            'proof' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }
}
