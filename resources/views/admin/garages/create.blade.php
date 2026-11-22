@extends('layouts.app')

@section('title', 'Buat Garasi')

@section('content')
    <form method="POST" action="{{ route('admin.garages.store') }}" class="max-w-md space-y-4 rounded-md bg-white p-6 shadow">
        @csrf

        <div>
            <label for="type" class="block text-sm font-medium text-gray-700">Tipe garasi</label>
            <select id="type" name="type" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>
                        {{ $type->label() }} ({{ $type->prefix() }}###)
                    </option>
                @endforeach
            </select>
            @error('type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <x-input name="count" label="Jumlah garasi" type="number" min="1" max="999" required />
            <p id="count-hint" class="mt-1 text-sm text-gray-500"></p>
        </div>
        <x-input name="daily_price" label="Harga sewa harian (Rp)" type="number" min="1" required />
        <div id="kwh-field">
            <x-input name="price_per_kwh" label="Harga isi listrik per kWh (Rp)" type="number" min="1" />
        </div>

        <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">Buat Garasi</button>
    </form>

    <script>
        // Menyesuaikan form setiap kali tipe diganti. Server tetap memvalidasi ulang lewat StoreGarageRequest.
        const quota = @js($quota); // { car: { next: 'C010', remaining: 990 }, ... }
        const typeSelect = document.getElementById('type');
        const countInput = document.getElementById('count');
        const countHint = document.getElementById('count-hint');
        const kwhField = document.getElementById('kwh-field');
        const kwhInput = document.getElementById('price_per_kwh');

        function updateForm() {
            // Kode berikutnya & sisa kuota tampil langsung; browser menolak jumlah melebihi sisa.
            const { next, remaining } = quota[typeSelect.value];
            countInput.max = remaining;
            countHint.textContent = remaining > 0
                ? `Kode berikutnya ${next} · sisa kuota ${remaining}.`
                : 'Kuota kode tipe ini sudah habis.';

            // Kolom harga per kWh hanya untuk Mobil Listrik. Saat disembunyikan, input di-disable
            // sehingga tidak ikut terkirim.
            const isElectric = typeSelect.value === @js(\App\Enums\GarageType::ElectricCar->value);
            kwhField.hidden = !isElectric;
            kwhInput.disabled = !isElectric;
            kwhInput.required = isElectric;
        }

        typeSelect.addEventListener('change', updateForm);
        updateForm();
    </script>
@endsection
