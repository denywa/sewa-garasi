@extends('layouts.app')

@section('title', 'Sewa Baru')
@section('width', 'max-w-xl')

@use('App\Enums\RentalType')
@use('App\Enums\VehicleType')

@php
    // Pilihan dropdown: [nilai => teks].
    $options = fn (array $cases) => collect($cases)->mapWithKeys(fn ($case) => [$case->value => $case->label()])->all();
@endphp

@section('content')
    <div class="mb-6 flex items-start justify-between gap-4">
        <p class="text-gray-600">Isi data kendaraan dan lama sewa. Mulai hari ini,
            <span class="font-semibold text-gray-900">{{ today()->translatedFormat('d M Y') }}</span>.</p>
        <x-step :current="1" :total="2" />
    </div>

    {{-- GET: langkah 2 hanya menampilkan garasi, belum menyimpan apa pun. --}}
    <form method="GET" action="{{ route('tenant.rentals.choose') }}" class="card space-y-5">
        <h2 class="text-lg font-semibold">Kendaraan</h2>
        <x-select name="vehicle_type" label="Tipe kendaraan" :options="$options(VehicleType::cases())" required />
        <div id="plate-field">
            <x-input name="plate_number" label="Nomor plat" maxlength="15" hint="Contoh: B 1234 XY." />
        </div>
        <x-input name="brand_model" label="Merek & tipe" maxlength="100" required hint="Contoh: Toyota Avanza." />
        {{-- type="text" + inputmode="decimal": boleh menulis desimal dengan koma (60,5). --}}
        <div id="capacity-field">
            <x-input name="battery_capacity_kwh" label="Kapasitas baterai (kWh)" inputmode="decimal" hint="Kapasitas saat baterai penuh." />
        </div>

        <h2 class="border-t pt-5 text-lg font-semibold">Sewa</h2>
        <x-select name="rental_type" label="Tipe sewa" :options="$options(RentalType::cases())" required
            hint="Bulanan = {{ $monthDays }} hari (jumlah hari bulan ini), biaya 25 × harga harian." />
        <div id="days-field">
            <x-input name="days" label="Jumlah hari" type="number" min="1" :max="$maxDays" hint="1 – {{ $maxDays }} hari." />
        </div>
        <x-select name="garage_type" label="Tipe garasi" :options="$options($garageTypes)" required
            hint="Pilihan menyesuaikan tipe kendaraan. Mobil listrik: garasi Mobil (parkir saja) atau Mobil Listrik (baterai diisi penuh)." />
        <div id="remaining-field">
            <x-input name="battery_remaining_kwh" label="Sisa baterai saat ini (kWh)" inputmode="decimal"
                hint="Baterai diisi penuh: biaya listrik = (kapasitas − sisa) × harga per kWh." />
        </div>

        <button type="submit" class="btn-primary">Cari Garasi Tersedia</button>
    </form>

    <script>
        // Menampilkan kolom sesuai pilihan. Kolom tersembunyi di-disable agar tidak ikut terkirim;
        // server tetap memvalidasi ulang lewat RentalSearchRequest.
        const allowedGarages = @js(collect(VehicleType::cases())->mapWithKeys(fn ($t) => [
            $t->value => array_map(fn ($g) => $g->value, $t->allowedGarageTypes()),
        ]));
        const vehicleSelect = document.getElementById('vehicle_type');
        const rentalSelect = document.getElementById('rental_type');
        const garageSelect = document.getElementById('garage_type');

        function toggle(id, show) {
            const field = document.getElementById(id);
            field.hidden = !show;
            field.querySelectorAll('input').forEach((input) => {
                input.disabled = !show;
                input.required = show;
            });
        }

        function updateForm() {
            const vehicle = vehicleSelect.value;
            toggle('plate-field', vehicle !== @js(VehicleType::Bicycle->value));
            toggle('capacity-field', vehicle === @js(VehicleType::ElectricCar->value));
            toggle('days-field', rentalSelect.value === @js(RentalType::Daily->value));

            // Hanya tipe garasi yang cocok dengan kendaraan yang bisa dipilih.
            const allowed = allowedGarages[vehicle];
            [...garageSelect.options].forEach((option) => option.hidden = option.disabled = !allowed.includes(option.value));
            if (!allowed.includes(garageSelect.value)) garageSelect.value = allowed[0];

            // Sisa baterai hanya perlu untuk garasi Mobil Listrik (biaya isi penuh).
            toggle('remaining-field', garageSelect.value === @js(\App\Enums\GarageType::ElectricCar->value));
        }

        vehicleSelect.addEventListener('change', updateForm);
        rentalSelect.addEventListener('change', updateForm);
        garageSelect.addEventListener('change', updateForm);
        updateForm();
    </script>
@endsection
