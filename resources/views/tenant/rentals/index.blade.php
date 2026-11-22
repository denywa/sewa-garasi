@extends('layouts.app')

@section('title', 'Sewa Saya')

@section('content')
    <p class="mb-6 text-gray-600">Garasi yang sedang dan pernah Anda sewa, terbaru di atas.</p>

    @if ($rentals->isEmpty())
        <p class="card text-center text-gray-600">Belum ada sewa.</p>
    @else
        <div class="card overflow-x-auto p-0">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Garasi</th>
                        <th class="px-4 py-3">Kendaraan</th>
                        <th class="px-4 py-3">Tipe Sewa</th>
                        <th class="px-4 py-3">Mulai</th>
                        <th class="px-4 py-3">Selesai</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Total Dibayar</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($rentals as $rental)
                        {{-- Aktif = hari ini belum melewati tanggal selesai (tanggal mulai selalu ≤ hari ini). --}}
                        @php($active = $rental->end_date->gte(today()))
                        <tr>
                            <td class="px-4 py-3 font-mono font-semibold">{{ $rental->garage->code() }}</td>
                            <td class="px-4 py-3">{{ $rental->vehicle->plate_number ?? $rental->vehicle->type->label() }}
                                <span class="block text-gray-500">{{ $rental->vehicle->brand_model }}</span></td>
                            <td class="px-4 py-3">{{ $rental->rental_type->label() }}</td>
                            <td class="px-4 py-3">{{ $rental->start_date->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">{{ $rental->end_date->translatedFormat('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <span @class([
                                    'rounded-full border px-2.5 py-0.5 text-xs font-semibold',
                                    'border-green-600 text-green-700' => $active,
                                    'border-gray-400 text-gray-500' => ! $active,
                                ])>{{ $active ? 'Aktif' : 'Selesai' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold"><x-rupiah :amount="$rental->payments_sum_amount" /></td>
                            <td class="px-4 py-3">
                                <a href="{{ route('tenant.rentals.show', $rental) }}" class="font-semibold text-indigo-600 hover:underline">Detail</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
