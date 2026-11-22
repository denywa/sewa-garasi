{{-- Layout halaman tamu (daftar & login): kartu di tengah layar. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 text-gray-900 flex items-center justify-center p-4">
    <main class="w-full max-w-md bg-white rounded-lg shadow p-6">
        <h1 class="text-xl font-semibold mb-1">Sewa Garasi</h1>
        <h2 class="text-gray-600 mb-6">@yield('title')</h2>

        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
