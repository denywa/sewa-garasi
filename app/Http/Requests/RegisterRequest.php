<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form daftar penyewa (UC-01). NIK & email unik, termasuk milik pendaftar yang ditolak.
 */
class RegisterRequest extends FormRequest
{
    /**
     * Semua tamu boleh mendaftar; pembatasan "belum login" dilakukan middleware guest.
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
            'name' => ['required', 'string', 'max:255'],
            'nik' => ['required', 'digits:16', 'unique:tenants,nik'],
            // Angka saja, diawali 0 atau +62: 081234567890 / +6281234567890.
            'phone' => ['required', 'regex:/^(\+62|0)[0-9]{8,13}$/'],
            // email:filter = filter_var PHP; menolak domain tanpa titik (budi@gmailcom).
            'email' => ['required', 'email:filter', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
