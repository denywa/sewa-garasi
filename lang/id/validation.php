<?php

/*
 * Pesan validasi Bahasa Indonesia (APP_LOCALE=id). Hanya aturan yang dipakai aplikasi;
 * aturan lain jatuh ke bahasa Inggris (APP_FALLBACK_LOCALE=en).
 */
return [
    'between' => [
        'numeric' => ':Attribute harus antara :min dan :max.',
    ],
    'confirmed' => ':Attribute tidak sama dengan konfirmasinya.',
    'digits' => ':Attribute harus :digits digit angka.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'min' => [
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':Attribute wajib diisi.',
    'string' => ':Attribute harus berupa teks.',
    'unique' => ':Attribute sudah terdaftar.',

    // Nama kolom yang tampil di pesan.
    // Pesan khusus per kolom + aturan.
    'custom' => [
        'price_per_kwh' => [
            'required_if' => 'Harga isi listrik per kWh wajib diisi untuk garasi Mobil Listrik.',
            'prohibited_unless' => 'Harga isi listrik per kWh hanya untuk garasi Mobil Listrik; kosongkan untuk tipe lain.',
        ],
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
        'type' => 'tipe garasi',
        'count' => 'jumlah garasi',
        'daily_price' => 'harga sewa harian',
        'price_per_kwh' => 'harga isi listrik per kWh',
    ],
];
