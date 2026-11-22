{{-- Layout halaman setelah login: menu sesuai role, tombol logout, pesan status. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-50 text-gray-900">
    @php
        // Menu navigasi per role: [nama route => teks]. Menu halaman yang sedang dibuka ditebalkan.
        $menu = auth()->user()->isAdmin()
            ? ['admin.dashboard' => 'Beranda', 'admin.verifications.index' => 'Verifikasi Pendaftar', 'admin.garages.create' => 'Buat Garasi', 'admin.extensions.index' => 'Perpanjang Sewa', 'admin.reports.index' => 'Laporan']
            : ['tenant.dashboard' => 'Beranda', 'tenant.rentals.create' => 'Sewa Baru'];
    @endphp

    <nav class="border-b bg-white">
        {{-- 3 kolom: logo kiri, menu tengah, nama & logout kanan. Kolom kiri & kanan sama lebar (1fr)
             sehingga menu selalu tepat di tengah. --}}
        <div class="grid h-16 grid-cols-[1fr_auto_1fr] items-center gap-6 px-6">
            <a href="{{ route('home') }}" class="justify-self-start text-lg font-bold text-indigo-600">Sewa Garasi</a>

            <div class="flex gap-6">
                @foreach ($menu as $route => $text)
                    <a href="{{ route($route) }}" @class([
                        'border-b-2 py-5 text-sm font-medium',
                        'border-indigo-600 text-indigo-600' => request()->routeIs($route),
                        'border-transparent text-gray-600 hover:text-gray-900' => ! request()->routeIs($route),
                    ])>{{ $text }}</a>
                @endforeach
            </div>

            <div class="flex items-center justify-self-end gap-4">
                <span class="text-sm text-gray-600">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-100">Logout</button>
                </form>
            </div>
        </div>
    </nav>

    {{-- Halaman form memakai @section('width', 'max-w-xl') agar judul & kartu sempit dan di tengah. --}}
    <main class="mx-auto @yield('width', 'max-w-5xl') px-4 py-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">{{ session('status') }}</div>
        @endif

        <h1 class="mb-2 text-2xl font-bold">@yield('title')</h1>
        @yield('content')
    </main>
</body>
</html>
