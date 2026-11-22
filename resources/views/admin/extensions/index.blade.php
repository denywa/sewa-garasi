@extends('layouts.app')

@section('title', 'Perpanjang Sewa')

@section('content')
    <p class="mb-6 text-gray-600">Garasi yang sedang disewa hari ini. Pilih satu untuk memperpanjang tanggal selesainya.</p>

    @if ($rentals->isEmpty())
        <p class="card text-center text-gray-600">Tidak ada garasi yang sedang disewa hari ini.</p>
    @else
        <div class="card overflow-x-auto p-0">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Garasi</th>
                        <th class="px-4 py-3">Penyewa</th>
                        <th class="px-4 py-3">Kendaraan</th>
                        <th class="px-4 py-3">Tipe Sewa</th>
                        <th class="px-4 py-3">Mulai</th>
                        <th class="px-4 py-3">Selesai</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($rentals as $rental)
                        <tr>
                            <td class="px-4 py-3 font-mono font-semibold">{{ $rental->garage->code() }}</td>
                            <td class="px-4 py-3">{{ $rental->vehicle->tenant->user->name }}</td>
                            <td class="px-4 py-3">{{ $rental->vehicle->plate_number ?? $rental->vehicle->type->label() }}
                                <span class="block text-gray-500">{{ $rental->vehicle->brand_model }}</span></td>
                            <td class="px-4 py-3">{{ $rental->rental_type->label() }}</td>
                            <td class="px-4 py-3">{{ $rental->start_date->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $rental->end_date->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.extensions.edit', $rental) }}"
                                    class="rounded-lg bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-700">Perpanjang</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
