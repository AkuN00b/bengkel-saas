<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-50 p-6 font-sans text-slate-900">
        <main class="w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <p class="text-sm font-semibold text-emerald-700">BengkelOS</p>
            <h1 class="mt-3 text-2xl font-bold">Fondasi aplikasi siap dikembangkan</h1>
            <p class="mt-3 leading-relaxed text-slate-600">Kerangka awal aplikasi telah terpasang. Fitur bengkel akan dikembangkan secara bertahap.</p>
            <div class="mt-6" x-data="{ open: false }">
                <button type="button" class="min-h-11 rounded-lg bg-emerald-700 px-4 py-2 font-medium text-white hover:bg-emerald-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-700" @click="open = !open" :aria-expanded="open" aria-controls="project-info">Tentang BengkelOS</button>
                <p id="project-info" x-cloak x-show="open" class="mt-4 leading-relaxed text-slate-600">Aplikasi pengelolaan bengkel motor yang sederhana dan nyaman digunakan dari HP.</p>
            </div>
        </main>
    </body>
</html>