@extends('layouts.app')

@section('title', 'Buat Garasi')
@section('width', 'max-w-xl')

@section('content')
    <p class="mb-6 text-gray-600">Kode garasi dibuat otomatis, melanjutkan nomor terakhir tiap tipe.</p>

    <form method="POST" action="{{ route('admin.garages.store') }}" class="card space-y-5">
        @csrf

        <div>
            <label for="type" class="form-label">Tipe garasi</label>
            <select id="type" name="type" required @class(['form-control', 'border-red-500' => $errors->has('type')])>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>
                        {{ $type->label() }} ({{ $type->prefix() }}###)
                    </option>
                @endforeach
            </select>
            @error('type')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Keterangan diisi script di bawah: kode berikutnya & sisa kuota tipe terpilih. --}}
        <x-input name="count" label="Jumlah garasi" type="number" min="1" max="999" required hint="Maksimal 999 per tipe." />
        <x-input name="daily_price" label="Harga sewa harian (Rp)" type="number" min="1" required />
        <div id="kwh-field">
            <x-input name="price_per_kwh" label="Harga isi listrik per kWh (Rp)" type="number" min="1"
                hint="Wajib untuk garasi Mobil Listrik (biaya isi daya penuh)." />
        </div>

        <button type="submit" class="btn-primary">Buat Garasi</button>
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
