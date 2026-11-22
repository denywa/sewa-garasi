<?php

namespace App\Models;

use App\Enums\RentalType;
use Carbon\CarbonInterface;
use Database\Factories\RentalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sewa satu garasi oleh satu kendaraan. Tanggal selesai inklusif.
 */
// Fillable: kolom yang boleh diisi sekaligus lewat create()/update(); kolom lain diabaikan (aman dari input liar).
#[Fillable(['vehicle_id', 'garage_id', 'rental_type', 'start_date', 'end_date', 'battery_remaining_kwh'])]
class Rental extends Model
{
    // HasFactory: mengaktifkan Rental::factory() untuk membuat data contoh di test.
    /** @use HasFactory<RentalFactory> */
    use HasFactory;

    /**
     * Cast: mengubah nilai kolom saat dibaca/disimpan, tipe kolom di DB tetap.
     * - rental_type: string "daily" ↔ enum RentalType::Daily.
     * - start_date/end_date: string "2026-11-22" ↔ objek Carbon (bisa ->addDays(), ->format()).
     * - battery_remaining_kwh: DECIMAL → float.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rental_type' => RentalType::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'battery_remaining_kwh' => 'float',
        ];
    }

    /**
     * Scope: sewa yang sedang berjalan pada tanggal ini: start_date ≤ tanggal ≤ end_date.
     * Dipakai bersama oleh Garage::availableOn dan Vehicle::hasActiveRental.
     *
     * @param  Builder<Rental>  $query
     */
    #[Scope]
    protected function activeOn(Builder $query, CarbonInterface $date): void
    {
        $query->where('start_date', '<=', $date->toDateString())
            ->where('end_date', '>=', $date->toDateString());
    }

    /**
     * Relasi N:1 — kendaraan yang disewakan (rentals.vehicle_id).
     *
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Relasi N:1 — garasi yang disewa (rentals.garage_id).
     *
     * @return BelongsTo<Garage, $this>
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Relasi 1:N — pembayaran sewa awal & perpanjangan (payments.rental_id).
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
