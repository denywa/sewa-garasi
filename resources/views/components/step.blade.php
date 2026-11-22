{{-- Penanda langkah di form bertahap. Contoh: <x-step :current="1" :total="2" /> → 1/2 --}}
@props(['current', 'total'])

<span class="shrink-0 rounded-full border border-indigo-600 bg-white px-3 py-1 text-sm font-semibold text-indigo-700"
    title="Langkah {{ $current }} dari {{ $total }}">{{ $current }}/{{ $total }}</span>
