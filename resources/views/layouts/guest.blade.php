{{-- Layout halaman tamu (daftar & login): kartu di tengah layar. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-gray-50 p-4 text-gray-900">
    <main class="card w-full max-w-md">
        <p class="text-lg font-bold text-indigo-600">Sewa Garasi</p>
        <h1 class="mb-6 text-2xl font-bold">@yield('title')</h1>

        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>
</body>
</html>
