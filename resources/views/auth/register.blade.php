@extends('layouts.guest')

@section('title', 'Daftar Penyewa')

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf
        <x-input name="nik" label="NIK" inputmode="numeric" maxlength="16" required autofocus />
        <x-input name="name" label="Nama lengkap" required />
        <x-input name="phone" label="Nomor telepon" type="tel" required />
        <x-input name="email" label="Email" type="email" required />
        <x-input name="password" label="Password" type="password" required />
        <x-input name="password_confirmation" label="Konfirmasi password" type="password" required />

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Daftar</button>
    </form>

    <p class="mt-4 text-sm text-center">Sudah punya akun? <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Login</a></p>
@endsection
