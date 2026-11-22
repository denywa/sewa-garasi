# Sewa Garasi

Aplikasi web persewaan garasi untuk sepeda, sepeda motor, mobil, dan mobil listrik. Dibuat untuk uji kompetensi
LSP skema **Pemrogram** (IMT.01.15/SSK/LSP/IX/2021).

- Tipe garasi: **Sepeda** (`S001`), **Mobil** (`C001`), **Mobil Listrik** (`E001`); maksimal 999 per tipe.
- Sepeda & sepeda motor → garasi Sepeda; mobil → garasi Mobil; mobil listrik → garasi Mobil **atau** Mobil Listrik
  (di garasi Mobil Listrik baterai diisi penuh: biaya = (kapasitas − sisa) × harga per kWh).
- Sewa **harian** 1 … N−1 hari atau **bulanan** N hari (biaya 25 × harga harian), N = jumlah hari di bulan tanggal
  mulai. Tanggal mulai selalu hari ini.

## Fitur

| Role | Fitur |
|---|---|
| Penyewa | Daftar akun (menunggu verifikasi), login/logout, **Sewa Baru** (data kendaraan → pilih garasi tersedia hari ini beserta rincian biaya → unggah bukti bayar), **Sewa Saya** (daftar sewa & riwayat pembayaran) |
| Admin | **Verifikasi Pendaftar** (terima/tolak), **Buat Garasi** sekaligus (kode otomatis melanjutkan nomor terakhir), **Perpanjang Sewa** (tanggal selesai baru → sisa biaya → unggah bukti bayar), **Laporan** per bulan, per hari, per tipe garasi, per penyewa |

## Teknologi & library pre-existing

| Library / komponen | Dipakai untuk |
|---|---|
| Laravel 13 (PHP 8.3+) | Framework MVC: routing, controller, middleware, Form Request (validasi), session & autentikasi |
| Eloquent ORM | Model, relasi, scope, transaksi `DB::transaction` + `lockForUpdate` (mencegah sewa ganda) |
| MySQL 8 | Basis data relasional (6 tabel, 3NF) dengan foreign key & CHECK constraint |
| Blade | Template tampilan & komponen (`x-input`, `x-select`, `x-rupiah`, `x-step`, `x-menu-card`) |
| Tailwind CSS 3 + `@tailwindcss/forms`, Vite | Gaya tampilan & build aset |
| Carbon | Perhitungan tanggal (jumlah hari di bulan, tanggal selesai, selisih hari) |
| `Illuminate\Support\Number` (intl) | Format Rupiah |
| PHPUnit 12 | Unit test & feature test |
| Laravel Pint | Merapikan gaya kode (PSR-12 / preset Laravel) |

## Struktur kode

```
app/Enums/             Aturan per tipe (GarageType, VehicleType, RentalType, PaymentType, TenantStatus, UserRole, ReportType)
app/Services/          RentalCalculator & GarageCodeGenerator (logika murni, tanpa DB)
                       RentalService (simpan sewa & perpanjangan dalam transaksi)
app/Http/Controllers/  Auth/, Admin/, Tenant/ — controller tipis
app/Http/Requests/     Validasi input (Form Request)
app/Http/Middleware/   EnsureRole (role:admin / role:tenant)
database/migrations/   Skema + CHECK constraint
lang/id/validation.php Pesan validasi Bahasa Indonesia
tests/Unit, tests/Feature
```

## Instalasi

Kebutuhan: PHP 8.3+ (ekstensi `pdo_mysql`, `intl`, `gd`), Composer, Node.js, MySQL 8.

```bash
git clone https://github.com/denywa/sewa-garasi.git
cd sewa-garasi
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat dua database MySQL: `sewa_garasi` (aplikasi) dan `sewa_garasi_test` (khusus test), lalu sesuaikan
`DB_USERNAME` / `DB_PASSWORD` di `.env` bila perlu.

```bash
php artisan migrate --seed     # skema + akun admin + data demo
php artisan storage:link       # agar bukti bayar bisa dibuka di browser
composer run dev               # server, queue, dan Vite → http://127.0.0.1:8000
```

Mengulang data dari awal: `php artisan migrate:fresh --seed` (**menghapus semua data**).

## Akun demo

Semua password: `password`.

| Email | Role | Keterangan |
|---|---|---|
| `admin@garasi.test` | Admin | |
| `budi@garasi.test` | Penyewa (disetujui) | 1 sewa aktif yang sudah diperpanjang, 2 sewa selesai |
| `siti@garasi.test` | Penyewa (disetujui) | Sewa aktif sepeda motor & mobil listrik (dengan biaya isi daya) |
| `rina@garasi.test` | Penyewa (menunggu) | Muncul di Verifikasi Pendaftar; belum bisa login |
| `joko@garasi.test` | Penyewa (ditolak) | Tidak bisa login |

Data demo dibuat relatif dari tanggal hari ini, sehingga selalu ada sewa aktif dan transaksi di beberapa bulan
untuk laporan. Tips: buka admin di `http://127.0.0.1:8000` dan penyewa di `http://localhost:8000` agar keduanya bisa
login bersamaan (cookie terpisah).

## Menjalankan test

Test memakai database MySQL `sewa_garasi_test` (lihat `phpunit.xml`), bukan SQLite, karena laporan memakai fungsi
tanggal MySQL dan skema memakai CHECK constraint.

```bash
php artisan test                       # semua test
php artisan test --testsuite=Unit      # unit test logika murni (RentalCalculator, GarageCodeGenerator, enum)
php artisan test --filter=ReportTest   # satu kelas test
vendor/bin/pint --test                 # cek gaya kode
```
