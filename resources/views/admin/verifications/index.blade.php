@extends('layouts.app')

@section('title', 'Verifikasi Pendaftar')

@section('content')
    <p class="mb-6 text-gray-600">Cek validitas KTP berdasarkan NIK, lalu terima atau tolak. Penolakan bersifat final.</p>

    @if ($tenants->isEmpty())
        <p class="card text-center text-gray-600">Tidak ada pendaftar baru.</p>
    @else
        <div class="card overflow-x-auto p-0">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">NIK</th>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Telepon</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Tanggal Daftar</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($tenants as $tenant)
                        <tr>
                            <td class="px-4 py-3 font-mono">{{ $tenant->nik }}</td>
                            <td class="px-4 py-3">{{ $tenant->user->name }}</td>
                            <td class="px-4 py-3">{{ $tenant->phone }}</td>
                            <td class="px-4 py-3">{{ $tenant->user->email }}</td>
                            <td class="px-4 py-3">{{ $tenant->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    {{-- Form HTML hanya kenal GET/POST; @method('PATCH') menambah input _method agar cocok dengan route PATCH. --}}
                                    <form method="POST" action="{{ route('admin.verifications.approve', $tenant) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 font-semibold text-white hover:bg-green-700">Terima</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.verifications.reject', $tenant) }}"
                                        onsubmit="return confirm('Tolak pendaftar ini? Penolakan bersifat final.')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Tolak</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
