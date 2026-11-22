@extends('layouts.app')

@section('title', 'Sewa Berhasil')
@section('width', 'max-w-xl')

@section('content')
    <p class="mb-6 text-gray-600">Sewa tersimpan. Garasi siap dipakai mulai hari ini.</p>

    <div class="card">
        <p class="text-sm text-gray-500">Garasi</p>
        <p class="mb-4 font-mono text-3xl font-bold text-indigo-600">{{ $rental->garage->code() }}</p>

        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <dt class="text-gray-500">Kendaraan</dt>
            <dd class="font-medium">{{ $rental->vehicle->type->label() }} · {{ $rental->vehicle->brand_model }}
                @if ($rental->vehicle->plate_number) ({{ $rental->vehicle->plate_number }}) @endif</dd>
            <dt class="text-gray-500">Tipe sewa</dt>
            <dd class="font-medium">{{ $rental->rental_type->label() }}</dd>
            <dt class="text-gray-500">Tanggal mulai</dt>
            <dd class="font-medium">{{ $rental->start_date->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Tanggal selesai</dt>
            <dd class="font-medium">{{ $rental->end_date->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Total dibayar</dt>
            <dd class="font-semibold"><x-rupiah :amount="$rental->payments->sum('amount')" /></dd>
        </dl>

        <a href="{{ route('tenant.dashboard') }}" class="btn-primary mt-6">Kembali ke Beranda</a>
    </div>
@endsection
