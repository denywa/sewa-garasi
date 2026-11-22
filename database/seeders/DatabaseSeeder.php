<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal: satu akun admin (tanpa baris tenants).
     */
    public function run(): void
    {
        User::create([
            'role' => UserRole::Admin,
            'name' => 'Admin',
            'email' => 'admin@garasi.test',
            'password' => 'password',
        ]);
    }
}
