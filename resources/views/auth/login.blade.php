@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf
        <x-input name="email" label="Email" type="email" required autofocus />
        <x-input name="password" label="Password" type="password" required />

        <button type="submit" class="btn-primary">Login</button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:underline">Daftar sebagai penyewa</a></p>
@endsection
