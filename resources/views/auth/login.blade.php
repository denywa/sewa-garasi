@extends('layouts.guest')

@section('title', 'Login')

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-input name="email" label="Email" type="email" required autofocus />
        <x-input name="password" label="Password" type="password" required />

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Login</button>
    </form>

    <p class="mt-4 text-sm text-center">Belum punya akun? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Daftar sebagai penyewa</a></p>
@endsection
