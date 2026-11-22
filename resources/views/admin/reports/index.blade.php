@extends('layouts.app')

@section('title', 'Laporan Sewa Garasi')

@section('content')
    @php
        // Periode yang sedang ditampilkan, mis. "Tahun 2026" atau "Maret 2026".
        $period = $type->needsMonth()
            ? \Illuminate\Support\Carbon::create($year, $month)->translatedFormat('F Y')
            : "Tahun {$year}";
    @endphp

    <p class="mb-6 text-gray-600">Jumlah transaksi dan total pendapatan dari pembayaran (sewa awal + perpanjangan),
        berdasarkan tanggal bayar.</p>

    {{-- Filter: GET agar laporan bisa dibuka ulang / dibagikan lewat URL. --}}
    <form method="GET" action="{{ route('admin.reports.index') }}" class="card mb-6 grid gap-4 sm:grid-cols-4 sm:items-end">
        <div>
            <label for="type" class="form-label">Jenis laporan</label>
            <select id="type" name="type" class="form-control">
                @foreach ($types as $case)
                    <option value="{{ $case->value }}" @selected($case === $type)>{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div id="month-field">
            <label for="month" class="form-label">Bulan</label>
            <select id="month" name="month" class="form-control">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected($m === $month)>{{ \Illuminate\Support\Carbon::create($year, $m)->translatedFormat('F') }}</option>
                @endforeach
            </select>
            @error('month')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>
        <x-input name="year" label="Tahun" type="number" min="2000" max="2100" :value="$year" />
        {{-- Selalu di kolom ke-4 (kanan), juga saat kolom bulan disembunyikan. --}}
        <button type="submit" class="btn-primary sm:col-start-4">Tampilkan</button>
    </form>

    <div class="card overflow-x-auto p-0">
        <h2 class="border-b px-4 py-3 font-semibold">{{ $type->label() }} · {{ $period }}</h2>
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">{{ $type->rowLabel() }}</th>
                    <th class="px-4 py-3 text-right">Jumlah Transaksi</th>
                    <th class="px-4 py-3 text-right">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($rows as $row)
                    <tr @class(['text-gray-400' => $row['count'] === 0])>
                        <td class="px-4 py-2">{{ $row['label'] }}</td>
                        <td class="px-4 py-2 text-right">{{ $row['count'] }}</td>
                        <td class="px-4 py-2 text-right"><x-rupiah :amount="$row['total']" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-gray-500">Tidak ada transaksi pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="border-t-2 bg-gray-50 font-semibold">
                <tr>
                    <td class="px-4 py-3">Total</td>
                    <td class="px-4 py-3 text-right">{{ array_sum(array_column($rows, 'count')) }}</td>
                    <td class="px-4 py-3 text-right"><x-rupiah :amount="array_sum(array_column($rows, 'total'))" /></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <script>
        // Laporan per bulan hanya butuh tahun: kolom bulan disembunyikan (dan tidak dikirim).
        const typeSelect = document.getElementById('type');
        const monthField = document.getElementById('month-field');

        function toggleMonth() {
            const show = typeSelect.value !== @js(\App\Enums\ReportType::Monthly->value);
            monthField.hidden = !show;
            document.getElementById('month').disabled = !show;
        }

        typeSelect.addEventListener('change', toggleMonth);
        toggleMonth();
    </script>
@endsection
