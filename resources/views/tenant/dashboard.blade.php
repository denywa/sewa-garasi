@extends('layouts.app')

@section('title', 'Beranda Penyewa')

@section('content')
    <p class="mb-6 text-gray-600">Selamat datang, {{ auth()->user()->name }}. Apa yang ingin dikerjakan?</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-menu-card :href="route('tenant.rentals.create')" title="Sewa Baru">
            Sewa garasi untuk sepeda, motor, mobil, atau mobil listrik mulai hari ini.
        </x-menu-card>
        <x-menu-card :href="route('tenant.rentals.index')" title="Sewa Saya">
            Lihat garasi yang sedang dan pernah Anda sewa beserta riwayat pembayarannya.
        </x-menu-card>
    </div>
@endsection
