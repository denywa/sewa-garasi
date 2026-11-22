@extends('layouts.guest')

@section('title', 'Daftar Penyewa')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf
        <x-input name="nik" label="NIK" inputmode="numeric" maxlength="16" required autofocus hint="16 digit angka sesuai KTP." />
        <x-input name="name" label="Nama lengkap" required />
        <x-input name="phone" label="Nomor telepon" type="tel" required hint="Contoh: 081234567890 atau +6281234567890." />
        <x-input name="email" label="Email" type="email" required hint="Dipakai sebagai username saat login." />
        <x-input name="password" label="Password" type="password" required hint="Minimal 8 karakter." />
        <x-input name="password_confirmation" label="Konfirmasi password" type="password" required />

        <button type="submit" class="btn-primary">Daftar</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:underline">Login</a></p>
@endsection
