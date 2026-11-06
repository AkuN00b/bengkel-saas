<nav {{ $attributes->class(['space-y-2']) }} aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif
       @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-emerald-400 text-slate-950' => request()->routeIs('dashboard'), 'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('dashboard')])>
        <svg aria-hidden="true" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
        Ringkasan
    </a>
    <a href="{{ route('workshops.select') }}" @if (request()->routeIs('workshops.select')) aria-current="page" @endif
       @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-emerald-400 text-slate-950' => request()->routeIs('workshops.select'), 'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('workshops.select')])>
        <svg aria-hidden="true" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 9 9-6 9 6v12H3zM9 21v-8h6v8"/></svg>
        Pilih bengkel lain
    </a>
</nav>
