@extends('layouts.app')

@section('title', 'Pilih Garasi')
@section('width', 'max-w-xl')

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <p class="text-gray-600">Pilih garasi, lalu unggah bukti pembayaran sesuai total biaya.</p>
        <span class="shrink-0 rounded-full bg-indigo-100 px-3 py-1 text-sm font-semibold text-indigo-700" title="Langkah 2 dari 2">2/2</span>
    </div>

    {{-- Error dari langkah 1 / service (mis. plat milik penyewa lain) tidak punya kolom di halaman ini. --}}
    @if ($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-700">
            <ul class="list-inside list-disc">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-6">
        <dl class="grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
            <dt class="text-gray-500">Kendaraan</dt>
            <dd class="font-medium">{{ $data['vehicle_type']->label() }} · {{ $data['brand_model'] }}
                @if ($data['plate_number']) ({{ $data['plate_number'] }}) @endif</dd>
            <dt class="text-gray-500">Tipe sewa</dt>
            <dd class="font-medium">{{ $data['rental_type']->label() }} · {{ $start->diffInDays($end) + 1 }} hari</dd>
            <dt class="text-gray-500">Tanggal mulai</dt>
            <dd class="font-medium">{{ $start->translatedFormat('d M Y') }}</dd>
            <dt class="text-gray-500">Tanggal selesai</dt>
            <dd class="font-medium">{{ $end->translatedFormat('d M Y') }}</dd>
        </dl>
        <a href="{{ route('tenant.rentals.create') }}" class="mt-4 inline-block text-sm font-semibold text-indigo-600 hover:underline">&larr; Ubah data</a>
    </div>

    @if ($garages->isEmpty())
        <p class="card text-center text-gray-600">Tidak ada garasi tersedia untuk tipe ini.</p>
    @else
        <form method="POST" action="{{ route('tenant.rentals.store') }}" enctype="multipart/form-data" class="card space-y-5">
            @csrf
            {{-- Data langkah 1 dikirim ulang dan divalidasi ulang oleh StoreRentalRequest. --}}
            @foreach ($search as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach

            {{-- Dropdown, bukan daftar kartu: garasi tersedia bisa sampai ratusan. --}}
            <div>
                <label for="garage_id" class="form-label">Garasi tersedia hari ini ({{ $garages->count() }})</label>
                <select id="garage_id" name="garage_id" required @class(['form-control', 'border-red-500' => $errors->has('garage_id')])>
                    @foreach ($garages as $garage)
                        @php($cost = $costs[$garage->id])
                        <option value="{{ $garage->id }}" @selected((int) old('garage_id') === $garage->id)>
                            @if ($cost['charging'] > 0)
                                {{ $garage->code() }} · Sewa <x-rupiah :amount="$cost['rent']" /> + Listrik <x-rupiah :amount="$cost['charging']" />
                                = <x-rupiah :amount="$cost['rent'] + $cost['charging']" />
                            @else
                                {{ $garage->code() }} · Sewa <x-rupiah :amount="$cost['rent']" />
                            @endif
                        </option>
                    @endforeach
                </select>
                {{-- Cara menghitung biaya di setiap pilihan garasi. --}}
                <ul class="form-hint list-inside list-disc">
                    <li>Sewa = {{ $data['rental_type'] === \App\Enums\RentalType::Monthly ? '25' : $data['days'].' hari' }} × harga harian</li>
                    @if ($data['battery_remaining_kwh'] !== null)
                        @php($kwh = fn (float $value) => \Illuminate\Support\Number::format($value, locale: 'id'))
                        <li>Listrik = ({{ $kwh($data['battery_capacity_kwh']) }} − {{ $kwh($data['battery_remaining_kwh']) }})
                            = {{ $kwh($data['battery_capacity_kwh'] - $data['battery_remaining_kwh']) }} kWh × harga isi listrik per kWh</li>
                    @endif
                </ul>
                @error('garage_id')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <x-input name="proof" label="Bukti pembayaran" type="file" accept=".jpg,.jpeg,.png" required
                hint="Gambar JPG/PNG, maksimal 2 MB." />

            <button type="submit" class="btn-primary">Sewa Sekarang</button>
        </form>
    @endif
@endsection
