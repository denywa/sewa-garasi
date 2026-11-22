{{-- Kartu menu di beranda: seluruh kartu bisa diklik.
     Contoh: <x-menu-card :href="route('admin.garages.create')" title="Buat Garasi">Tambah garasi baru.</x-menu-card> --}}
@props(['href', 'title'])

<a href="{{ $href }}" class="card group block transition hover:border-indigo-400 hover:shadow-md">
    <h2 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-600">{{ $title }} &rarr;</h2>
    <p class="mt-1 text-sm text-gray-600">{{ $slot }}</p>
</a>
