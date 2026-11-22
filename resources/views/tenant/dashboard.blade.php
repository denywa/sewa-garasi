@extends('layouts.app')

@section('title', 'Beranda Penyewa')

@section('content')
    <p class="mb-6 text-gray-600">Selamat datang, {{ auth()->user()->name }}. Apa yang ingin dikerjakan?</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-menu-card :href="route('tenant.rentals.create')" title="Sewa Baru">
            Sewa garasi untuk sepeda, motor, mobil, atau mobil listrik mulai hari ini.
        </x-menu-card>
    </div>
@endsection
