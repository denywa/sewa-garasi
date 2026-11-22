@extends('layouts.app')

@section('title', 'Perpanjang Sewa '.$rental->garage->code())
@section('width', 'max-w-xl')

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <p class="text-gray-600">Isi tanggal selesai baru, hitung sisa biaya, lalu unggah bukti bayar dari penyewa.</p>
        <x-step :current="$cost === null ? 1 : 2" :total="2" />
    </div>

    {{-- Error yang tidak punya kolom di halaman ini (mis. sewa sudah tidak aktif). --}}
    @error('rental')
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">{{ $message }}</div>
    @enderror

    <div class="card mb-6">
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <dt class="text-gray-500">Penyewa</dt>
            <dd class="font-medium">{{ $rental->vehicle->tenant->user->name }}</dd>
            <dt class="text-gray-500">Kendaraan</dt>
            <dd class="font-medium">{{ $rental->vehicle->type->label() }} · {{ $rental->vehicle->brand_model }}
                @if ($rental->vehicle->plate_number) ({{ $rental->vehicle->plate_number }}) @endif</dd>
            <dt class="text-gray-500">Tipe sewa</dt>
            <dd class="font-medium">{{ $rental->rental_type->label() }}</dd>
            <dt class="text-gray-500">Tanggal mulai</dt>
            <dd class="font-medium">{{ $rental->start_date->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Tanggal selesai sekarang</dt>
            <dd class="font-semibold">{{ $rental->end_date->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Harga harian</dt>
            <dd class="font-medium"><x-rupiah :amount="$rental->garage->daily_price" /></dd>
        </dl>
        <a href="{{ route('admin.extensions.index') }}" class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:underline">&larr; Kembali ke daftar</a>
    </div>

    @if (! $active)
        <p class="card text-center text-gray-600">Sewa ini sudah tidak aktif, sehingga tidak bisa diperpanjang.</p>
    @else
        @if ($cost === null)
            {{-- Langkah 1: GET agar sisa biaya bisa dihitung tanpa menyimpan apa pun. --}}
            <form method="GET" action="{{ route('admin.extensions.edit', $rental) }}" class="card space-y-5">
                <x-input name="new_end_date" label="Tanggal selesai baru" type="date" required
                    :min="$rental->end_date->addDay()->toDateString()"
                    hint="Harus setelah {{ $rental->end_date->translatedFormat('d M Y') }}; tanpa batas maksimal." />
                <button type="submit" class="btn-primary">Hitung Sisa Biaya</button>
            </form>
        @else
            {{-- Langkah 2: tanggal sudah dikunci (ubah lewat "Ubah tanggal"), tinggal unggah bukti & simpan. --}}
            <form method="POST" action="{{ route('admin.extensions.update', $rental) }}" enctype="multipart/form-data" class="card space-y-5">
                @csrf
                @method('PUT')
                <input type="hidden" name="new_end_date" value="{{ $newEnd->toDateString() }}">

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Tanggal selesai baru</p>
                        <p class="font-semibold">{{ $newEnd->translatedFormat('d M Y') }}</p>
                    </div>
                    <a href="{{ route('admin.extensions.edit', $rental) }}" class="text-sm font-semibold text-indigo-600 hover:underline">&larr; Ubah tanggal</a>
                </div>
                @error('new_end_date')
                    <p class="form-error">{{ $message }}</p>
                @enderror

                <div>
                    <p class="text-sm text-gray-500">Sisa biaya</p>
                    <p class="text-sm text-gray-600">{{ $days }} hari × <x-rupiah :amount="$rental->garage->daily_price" /></p>
                    <p class="text-3xl font-bold text-indigo-600"><x-rupiah :amount="$cost" /></p>
                </div>

                <x-input name="proof" label="Bukti pembayaran" type="file" accept=".jpg,.jpeg,.png" required
                    hint="Gambar JPG/PNG, maksimal 2 MB." />

                <button type="submit" class="btn-primary">Simpan Perpanjangan</button>
            </form>
        @endif
    @endif
@endsection
