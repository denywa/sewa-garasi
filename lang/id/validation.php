<?php

/*
 * Pesan validasi Bahasa Indonesia (APP_LOCALE=id). Hanya aturan yang dipakai aplikasi;
 * aturan lain jatuh ke bahasa Inggris (APP_FALLBACK_LOCALE=en).
 */
return [
    'after' => ':Attribute harus setelah :date.',
    'between' => [
        'numeric' => ':Attribute harus antara :min dan :max.',
    ],
    'confirmed' => ':Attribute tidak sama dengan konfirmasinya.',
    'date_format' => ':Attribute harus berformat :format.',
    'digits' => ':Attribute harus :digits digit angka.',
    'email' => ':Attribute harus berupa alamat email yang valid.',
    'enum' => ':Attribute yang dipilih tidak valid.',
    'exists' => ':Attribute yang dipilih tidak valid.',
    'gt' => [
        'numeric' => ':Attribute harus lebih dari :value.',
    ],
    'image' => ':Attribute harus berupa gambar.',
    'integer' => ':Attribute harus berupa bilangan bulat.',
    'max' => [
        'file' => ':Attribute maksimal :max KB.',
        'numeric' => ':Attribute maksimal :max.',
        'string' => ':Attribute maksimal :max karakter.',
    ],
    'min' => [
        'numeric' => ':Attribute minimal :min.',
        'string' => ':Attribute minimal :min karakter.',
    ],
    'mimes' => ':Attribute harus berformat: :values.',
    'numeric' => ':Attribute harus berupa angka.',
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
        'plate_number' => [
            'required_unless' => 'Nomor plat wajib diisi (kecuali sepeda).',
            'regex' => 'Format nomor plat tidak valid (contoh: B 1234 XY).',
        ],
        'battery_capacity_kwh' => [
            'required_if' => 'Kapasitas baterai wajib diisi untuk mobil listrik.',
        ],
        'battery_remaining_kwh' => [
            'required' => 'Sisa baterai wajib diisi untuk garasi Mobil Listrik.',
            'lte' => 'Sisa baterai tidak boleh melebihi kapasitas baterai.',
        ],
        'garage_type' => [
            'in' => 'Tipe garasi tidak sesuai tipe kendaraan.',
        ],
        'phone' => [
            'mimes' => ':Attribute harus berformat: :values.',
            'numeric' => ':Attribute harus berupa angka.',
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
        'vehicle_type' => 'tipe kendaraan',
        'plate_number' => 'nomor plat',
        'brand_model' => 'merek & tipe',
        'battery_capacity_kwh' => 'kapasitas baterai',
        'battery_remaining_kwh' => 'sisa baterai',
        'rental_type' => 'tipe sewa',
        'days' => 'jumlah hari',
        'garage_type' => 'tipe garasi',
        'garage_id' => 'garasi',
        'proof' => 'bukti pembayaran',
        'new_end_date' => 'tanggal selesai baru',
    ],
];
