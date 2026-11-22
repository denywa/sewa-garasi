@extends('layouts.app')

@section('title', 'Detail Sewa '.$rental->garage->code())
@section('width', 'max-w-xl')

@section('content')
    <a href="{{ route('tenant.rentals.index') }}" class="mb-6 inline-block text-sm font-semibold text-indigo-600 hover:underline">&larr; Sewa Saya</a>

    <div class="card mb-6">
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
            <dd class="font-semibold">{{ $rental->end_date->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Total dibayar</dt>
            <dd class="font-semibold"><x-rupiah :amount="$rental->payments->sum('amount')" /></dd>
        </dl>
    </div>

    <div class="card overflow-x-auto p-0">
        <h2 class="border-b px-4 py-3 font-semibold">Riwayat Pembayaran</h2>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Tanggal Bayar</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3 text-right">Jumlah</th>
                    <th class="px-4 py-3">Sampai</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach ($rental->payments->sortBy('id') as $payment)
                    <tr>
                        <td class="px-4 py-3">{{ $payment->created_at->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $payment->type->label() }}</td>
                        <td class="px-4 py-3 text-right"><x-rupiah :amount="$payment->amount" /></td>
                        <td class="px-4 py-3">{{ $payment->end_date->translatedFormat('d M Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
