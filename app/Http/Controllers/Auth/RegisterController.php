<?php

namespace App\Http\Controllers\Auth;

use App\Enums\TenantStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * UC-01 Mendaftar sebagai Penyewa.
 */
class RegisterController extends Controller
{
    /**
     * Tampilkan form daftar.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Simpan akun login (users) + data penyewa (tenants) dalam satu transaksi.
     * Tidak login otomatis: penyewa menunggu verifikasi admin.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $user = User::create([
                'role' => UserRole::Tenant,
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]);

            $user->tenant()->create([
                'nik' => $request->input('nik'),
                'phone' => $request->input('phone'),
                'status' => TenantStatus::Pending,
            ]);
        });

        return redirect()->route('login')->with('status', 'Pendaftaran berhasil, menunggu verifikasi admin.');
    }
}
