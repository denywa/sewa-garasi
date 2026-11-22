<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Akun login (admin & penyewa). Data khusus penyewa ada di Tenant (relasi 1:1).
 */
// Fillable: kolom yang boleh diisi sekaligus lewat create()/update(); kolom lain diabaikan (aman dari input liar).
#[Fillable(['role', 'name', 'email', 'password'])]
// Hidden: kolom yang tidak ikut keluar saat model diubah ke array/JSON.
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    // HasFactory: mengaktifkan User::factory() untuk membuat data contoh di test.
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Cast: mengubah nilai kolom saat dibaca/disimpan, tipe kolom di DB tetap.
     * - role: string "admin" ↔ enum UserRole::Admin.
     * - password: otomatis di-hash (bcrypt) saat disimpan.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'password' => 'hashed',
        ];
    }

    /**
     * Relasi 1:1 — data penyewa (NIK, telepon, status) di tenants.user_id. Null untuk admin.
     *
     * @return HasOne<Tenant, $this>
     */
    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }
}
