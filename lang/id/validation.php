<?php

/*
 * Pesan validasi Bahasa Indonesia (APP_LOCALE=id). Hanya aturan yang dipakai aplikasi;
 * aturan lain jatuh ke bahasa Inggris (APP_FALLBACK_LOCALE=en).
 */
return [
    'confirmed' => ':Attribute tidak sama dengan konfirmasinya.',
    'digits' => ':Attribute harus :digits digit angka.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'max' => [
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'min' => [
        'string' => ':Attribute minimal :min karakter.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah terdaftar.',

    // Nama kolom yang tampil di pesan.
    // Pesan khusus per kolom + aturan.
    'custom' => [
        'phone' => [
            'regex' => 'Nomor telepon harus angka 9–14 digit diawali 0, atau diawali +62 (contoh: 081234567890 / +6281234567890).',
        ],
    ],

    'attributes' => [
        'name' => 'nama lengkap',
        'nik' => 'NIK',
        'phone' => 'nomor telepon',
        'email' => 'email',
        'password' => 'password',
    ],
];
