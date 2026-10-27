@props(['title', 'description' => null])
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="referrer" content="no-referrer">
        <title>{{ $title }} — BengkelOS</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-50 p-4 font-sans text-slate-900 sm:p-6">
        <main class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <a href="{{ route('home') }}" class="font-semibold text-emerald-700">BengkelOS</a>
            <h1 class="mt-5 text-2xl font-bold">{{ $title }}</h1>
            @if ($description)
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $description }}</p>
            @endif
            @if (session('status'))
                <p role="status" class="mt-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{{ session('status') }}</p>
            @endif
            {{ $slot }}
        </main>
    </body>
</html>
