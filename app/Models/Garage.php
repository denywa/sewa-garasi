<?php

namespace App\Models;

use App\Enums\GarageType;
use App\Services\GarageCodeGenerator;
use Carbon\CarbonInterface;
use Database\Factories\GarageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Garasi yang disewakan. Status "used" tidak disimpan: garasi dipakai bila ada sewa aktif.
 */
// Fillable: kolom yang boleh diisi sekaligus lewat create()/update(); kolom lain diabaikan (aman dari input liar).
#[Fillable(['type', 'number', 'daily_price', 'price_per_kwh'])]
class Garage extends Model
{
    // HasFactory: mengaktifkan Garage::factory() untuk membuat data contoh di test.
    /** @use HasFactory<GarageFactory> */
    use HasFactory;

    /**
     * Cast: mengubah nilai kolom saat dibaca/disimpan, tipe kolom di DB tetap.
     * - type: string "car" ↔ enum GarageType::Car.
     * - angka: dipastikan int agar aman dipakai hitung biaya.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => GarageType::class,
            'number' => 'integer',
            'daily_price' => 'integer',
            'price_per_kwh' => 'integer',
        ];
    }

    /**
     * Kode garasi untuk ditampilkan, mis. "C010".
     */
    public function code(): string
    {
        return app(GarageCodeGenerator::class)->format($this->type, $this->number);
    }

    /**
     * Scope (potongan query yang bisa dipakai ulang): hanya garasi tanpa sewa aktif pada tanggal ini.
     * Dipanggil: Garage::query()->availableOn($tanggal)->get().
     *
     * @param  Builder<Garage>  $query
     */
    #[Scope]
    protected function availableOn(Builder $query, CarbonInterface $date): void
    {
        $query->whereDoesntHave('rentals', fn (Builder $rentals) => $rentals->activeOn($date));
    }

    /**
     * Relasi 1:N — riwayat sewa garasi ini (rentals.garage_id).
     *
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
