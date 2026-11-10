@props(['activeWorkshop' => null])
<nav {{ $attributes->class(['space-y-2']) }} aria-label="Navigasi utama">
    <a href="{{ route('dashboard') }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif
       @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-emerald-400 text-slate-950' => request()->routeIs('dashboard'), 'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('dashboard')])>
        <svg aria-hidden="true" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z"/></svg>
        Ringkasan
    </a>
    @if ($activeWorkshop)
        @can('viewAny', [\App\Models\Service::class, $activeWorkshop])
            @foreach (['services'=>'Jasa','spare-parts'=>'Spare Part'] as $catalogRoute=>$catalogLabel)<a href="{{ route($catalogRoute.'.index') }}" @if(request()->routeIs($catalogRoute.'.*')) aria-current="page" @endif class="flex min-h-12 items-center rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs($catalogRoute.'.*') ? 'bg-emerald-400 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">{{ $catalogLabel }}</a>@endforeach
        @endcan
        <a href="{{ route('work-orders.index') }}" @if (request()->routeIs('work-orders.*')) aria-current="page" @endif class="flex min-h-12 items-center rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('work-orders.*') ? 'bg-emerald-400 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Perintah Kerja</a>
        @can('viewAny', [\App\Models\WorkshopMembership::class, $activeWorkshop])
            <a href="{{ route('mechanics.index') }}" @if (request()->routeIs('mechanics.*')) aria-current="page" @endif class="flex min-h-12 items-center rounded-xl px-4 py-3 text-sm font-medium {{ request()->routeIs('mechanics.*') ? 'bg-emerald-400 text-slate-950' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">Mekanik</a>
        @endcan
        <a href="{{ route('customers.index') }}" @if (request()->routeIs('customers.*')) aria-current="page" @endif
           @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-emerald-400 text-slate-950' => request()->routeIs('customers.*'), 'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('customers.*')])>
            <svg aria-hidden="true" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 22v-2a8 8 0 0 1 16 0v2"/></svg>
            Pelanggan
        </a>
        <a href="{{ route('vehicles.index') }}" @if (request()->routeIs('vehicles.*')) aria-current="page" @endif
           @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-emerald-400 text-slate-950' => request()->routeIs('vehicles.*'), 'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('vehicles.*')])>
            <svg aria-hidden="true" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 16h18l-2-8H5l-2 8zM5 8l2-3h10l2 3M5 16v3M19 16v3M7 12h10"/></svg>
            Kendaraan
        </a>
    @endif
    <a href="{{ route('workshops.select') }}" @if (request()->routeIs('workshops.select')) aria-current="page" @endif
       @class(['flex min-h-12 items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition', 'bg-emerald-400 text-slate-950' => request()->routeIs('workshops.select'), 'text-slate-300 hover:bg-white/10 hover:text-white' => ! request()->routeIs('workshops.select')])>
        <svg aria-hidden="true" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 9 9-6 9 6v12H3zM9 21v-8h6v8"/></svg>
        Pilih bengkel lain
    </a>
</nav>
