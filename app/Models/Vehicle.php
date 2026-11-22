<?php

namespace App\Models;

use App\Enums\VehicleType;
use Carbon\CarbonInterface;
use Database\Factories\VehicleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kendaraan milik penyewa. Satu plat hanya milik satu penyewa; sepeda tanpa plat.
 */
#[Fillable(['tenant_id', 'type', 'plate_number', 'brand_model', 'battery_capacity_kwh'])]
class Vehicle extends Model
{
    /** @use HasFactory<VehicleFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => VehicleType::class,
            'battery_capacity_kwh' => 'float',
        ];
    }

    /**
     * Seragamkan penulisan plat: huruf besar, hanya A–Z dan 0–9 ("b 1234-xy" → "B1234XY").
     */
    public static function normalizePlate(string $plate): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($plate));
    }

    /**
     * Apakah kendaraan masih menempati garasi pada tanggal ini.
     */
    public function hasActiveRental(CarbonInterface $date): bool
    {
        return $this->rentals()->activeOn($date)->exists();
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * @return HasMany<Rental, $this>
     */
    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }
}
