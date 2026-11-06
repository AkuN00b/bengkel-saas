@props(['title', 'activeWorkshop' => null])
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title }} — BengkelOS</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-stone-50 font-sans text-slate-900" x-data="{ mobileOpen: false }" @resize.window="if (window.innerWidth >= 1024) $refs.mobileNav.close()">
        <a href="#main-content" class="sr-only z-50 rounded-lg bg-white p-3 text-emerald-800 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Langsung ke konten</a>
        <aside class="fixed inset-y-0 left-0 hidden w-64 flex-col bg-slate-950 px-5 py-8 text-white lg:flex">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 text-xl font-bold tracking-tight"><span class="grid size-9 place-items-center rounded-xl bg-emerald-400 text-base text-slate-950" aria-hidden="true">B</span>BengkelOS</a>
            <p class="mb-8 mt-3 px-3 text-xs tracking-wide text-slate-400">Ruang kerja bengkel Anda</p>
            <x-app-navigation />
            <p class="mt-auto px-3 pt-8 text-xs leading-relaxed text-slate-500">Sederhana, terhubung,<br>siap untuk kegiatan bengkel.</p>
        </aside>
        <dialog x-ref="mobileNav" id="mobile-navigation" @close="mobileOpen = false" @click="if ($event.target === $refs.mobileNav) $refs.mobileNav.close()" class="fixed inset-y-0 left-0 m-0 h-full max-h-none w-72 max-w-[85vw] bg-slate-950 p-6 text-white shadow-xl backdrop:bg-slate-950/60" aria-label="Navigasi">
            <div class="mb-8 flex items-center justify-between gap-4">
                <span class="text-xl font-bold">BengkelOS</span>
                <button type="button" class="grid min-h-11 min-w-11 place-items-center rounded-lg hover:bg-white/10" @click="$refs.mobileNav.close()" aria-label="Tutup navigasi">✕</button>
            </div>
            <x-app-navigation />
        </dialog>
        <div class="min-w-0 lg:pl-64">
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-white/95 px-4 py-4 backdrop-blur sm:px-8">
                <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" class="grid min-h-11 min-w-11 shrink-0 place-items-center rounded-lg border border-slate-200 lg:hidden" @click="$refs.mobileNav.showModal(); mobileOpen = true" :aria-expanded="mobileOpen" aria-controls="mobile-navigation" aria-label="Buka navigasi">
                            <svg aria-hidden="true" class="size-5" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </button>
                        <div class="min-w-0">
                            <p class="text-xs text-slate-500">{{ $activeWorkshop ? 'Bengkel aktif' : 'Ruang kerja' }}</p>
                            <p class="truncate text-sm font-semibold">{{ $activeWorkshop?->name ?? 'Pemilihan bengkel' }}</p>
                        </div>
                    </div>
                    <details class="relative shrink-0" x-ref="userMenu" @click.outside="$refs.userMenu.removeAttribute('open')" @keydown.escape.stop="$refs.userMenu.removeAttribute('open'); $refs.userMenu.querySelector('summary').focus()">
                        <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-full border border-slate-200 px-3 text-sm font-medium hover:bg-stone-50" aria-label="Menu pengguna">
                            <span class="grid size-7 place-items-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-900" aria-hidden="true">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                            <span class="hidden max-w-40 truncate sm:inline">{{ auth()->user()->name }}</span>
                            <span aria-hidden="true">⌄</span>
                        </summary>
                        <div class="absolute right-0 mt-3 w-64 max-w-[85vw] rounded-2xl border border-slate-200 bg-white p-4 shadow-xl">
                            <p class="break-words text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="mt-1 break-all text-xs text-slate-500">{{ auth()->user()->email }}</p>
                            <form method="POST" action="{{ route('logout') }}" class="mt-4 border-t border-slate-100 pt-3">@csrf<button class="min-h-11 w-full rounded-lg text-left text-sm font-medium text-red-700 hover:bg-red-50" type="submit">Keluar</button></form>
                        </div>
                    </details>
                </div>
            </header>
            <main id="main-content" tabindex="-1" class="mx-auto max-w-6xl px-4 py-7 outline-none sm:px-8 sm:py-10">
                @if (session('status'))
                    <p role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">{{ session('status') }}</p>
                @endif
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
