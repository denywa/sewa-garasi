<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * UC-02 Login dan UC-03 Logout.
 */
class LoginController extends Controller
{
    /**
     * Tampilkan form login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Cocokkan email & password, lalu tolak penyewa yang belum disetujui admin.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        if (! Auth::attempt($request->validated())) {
            return $this->failed('Email atau password salah.');
        }

        /** @var User $user */
        $user = Auth::user();
        // Admin tidak punya baris tenants → null → boleh login.
        $blocked = $user->tenant?->status->loginMessage();

        if ($blocked !== null) {
            Auth::logout();

            return $this->failed($blocked);
        }

        // Ganti ID session setelah login (mencegah session fixation).
        $request->session()->regenerate();

        // Selalu ke beranda sesuai role. Bukan intended(): URL sebelum login bisa milik role lain
        // (mis. halaman penyewa di browser yang sama) sehingga admin akan mendapat 403.
        return redirect()->route('home');
    }

    /**
     * Akhiri session dan kembali ke halaman login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Kembali ke form login dengan pesan di bawah kolom email; email tetap terisi.
     */
    private function failed(string $message): RedirectResponse
    {
        return back()->withErrors(['email' => $message])->onlyInput('email');
    }
}
