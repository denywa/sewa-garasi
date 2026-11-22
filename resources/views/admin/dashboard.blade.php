@extends('layouts.app')

@section('title', 'Beranda Admin')

@section('content')
    <p class="mb-6 text-gray-600">Selamat datang, {{ auth()->user()->name }}. Apa yang ingin dikerjakan?</p>

    <div class="grid gap-4 sm:grid-cols-2">
        <x-menu-card :href="route('admin.verifications.index')" title="Verifikasi Pendaftar">
            Cek KTP pendaftar baru, lalu terima atau tolak.
        </x-menu-card>
        <x-menu-card :href="route('admin.garages.create')" title="Buat Garasi">
            Tambah garasi Sepeda, Mobil, atau Mobil Listrik sekaligus.
        </x-menu-card>
        <x-menu-card :href="route('admin.extensions.index')" title="Perpanjang Sewa">
            Ubah tanggal selesai sewa yang aktif, hitung sisa biaya, dan unggah bukti bayar.
        </x-menu-card>
    </div>
@endsection
