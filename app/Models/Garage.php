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
#[Fillable(['type', 'number', 'daily_price', 'price_per_kwh'])]
class Garage extends Model
{
    /** @use HasFactory<GarageFactory> */
    use HasFactory;

    /**
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
     * Hanya garasi yang tidak punya sewa aktif pada tanggal ini.
     *
     * @param  Builder<Garage>  $query
     */
    #[Scope]
    protected function availableOn(Builder $query, CarbonInterface $date): void
    {
        $query->whereDoesntHave('rentals', fn (Builder $rentals) => $rentals->activeOn($date));
    }

    /**
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
