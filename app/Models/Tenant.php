<?php

namespace App\Models;

use App\Enums\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Data khusus penyewa. Primary key = user_id (shared primary key dengan users).
 */
// Table: primary key bukan "id" melainkan user_id, dan nilainya tidak auto increment (diambil dari users.id).
#[Table(key: 'user_id', incrementing: false)]
// Fillable: kolom yang boleh diisi sekaligus lewat create()/update(); kolom lain diabaikan (aman dari input liar).
#[Fillable(['user_id', 'nik', 'phone', 'status'])]
class Tenant extends Model
{
    // HasFactory: mengaktifkan Tenant::factory() untuk membuat data contoh di test.
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * Cast: mengubah nilai kolom saat dibaca/disimpan, tipe kolom di DB tetap.
     * - status: string "pending" ↔ enum TenantStatus::Pending.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
        ];
    }

    /**
     * Relasi kebalikan 1:1 — akun login pemilik data ini (tenants.user_id → users.id).
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi 1:N — kendaraan milik penyewa (vehicles.tenant_id).
     *
     * @return HasMany<Vehicle, $this>
     */
    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'tenant_id');
    }
}
