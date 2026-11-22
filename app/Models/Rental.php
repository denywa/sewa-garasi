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
#[Fillable(['vehicle_id', 'garage_id', 'rental_type', 'start_date', 'end_date', 'battery_remaining_kwh'])]
class Rental extends Model
{
    /** @use HasFactory<RentalFactory> */
    use HasFactory;

    /**
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
     * Sewa yang sedang berjalan pada tanggal ini: start_date ≤ tanggal ≤ end_date.
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
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * @return BelongsTo<Garage, $this>
     */
    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class);
    }

    /**
     * Pembayaran sewa awal & perpanjangan.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}
