{{-- Layout halaman setelah login: menu sesuai role, tombol logout, pesan status. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900">
    <nav class="bg-white border-b">
        <div class="max-w-5xl mx-auto px-4 h-14 flex items-center gap-6 text-sm">
            <a href="{{ route('home') }}" class="font-semibold text-base">Sewa Garasi</a>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}" class="hover:text-indigo-600">Beranda</a>
                <a href="{{ route('admin.verifications.index') }}" class="hover:text-indigo-600">Verifikasi Pendaftar</a>
                <a href="{{ route('admin.garages.create') }}" class="hover:text-indigo-600">Buat Garasi</a>
            @else
                <a href="{{ route('tenant.dashboard') }}" class="hover:text-indigo-600">Beranda</a>
            @endif

            <span class="ml-auto text-gray-600">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-red-600 hover:underline">Logout</button>
            </form>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        <h1 class="text-xl font-semibold mb-4">@yield('title')</h1>
        @yield('content')
    </main>
</body>
</html>
