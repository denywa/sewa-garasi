@extends('layouts.app')

@section('title', 'Verifikasi Pendaftar')

@section('content')
    <p class="mb-4 text-sm text-gray-600">Cek validitas KTP berdasarkan NIK, lalu terima atau tolak. Penolakan bersifat final.</p>

    @if ($tenants->isEmpty())
        <p class="rounded-md bg-white p-4 shadow">Tidak ada pendaftar baru.</p>
    @else
        <div class="overflow-x-auto rounded-md bg-white shadow">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-2">NIK</th>
                        <th class="px-4 py-2">Nama</th>
                        <th class="px-4 py-2">Telepon</th>
                        <th class="px-4 py-2">Email</th>
                        <th class="px-4 py-2">Tanggal Daftar</th>
                        <th class="px-4 py-2">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach ($tenants as $tenant)
                        <tr>
                            <td class="px-4 py-2 font-mono">{{ $tenant->nik }}</td>
                            <td class="px-4 py-2">{{ $tenant->user->name }}</td>
                            <td class="px-4 py-2">{{ $tenant->phone }}</td>
                            <td class="px-4 py-2">{{ $tenant->user->email }}</td>
                            <td class="px-4 py-2">{{ $tenant->created_at->translatedFormat('d M Y H:i') }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    {{-- Form HTML hanya kenal GET/POST; @method('PATCH') menambah input _method agar cocok dengan route PATCH. --}}
                                    <form method="POST" action="{{ route('admin.verifications.approve', $tenant) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded bg-green-600 px-3 py-1 text-white hover:bg-green-700">Terima</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.verifications.reject', $tenant) }}"
                                        onsubmit="return confirm('Tolak pendaftar ini? Penolakan bersifat final.')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded bg-red-600 px-3 py-1 text-white hover:bg-red-700">Tolak</button>
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
