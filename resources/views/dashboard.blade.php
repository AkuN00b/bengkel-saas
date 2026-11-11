<x-app-layout title="Ringkasan" :active-workshop="$activeWorkshop">
    <div class="mb-8">
        <p class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Ruang kerja Anda</p>
        <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Selamat datang, <span class="break-words">{{ auth()->user()->name }}</span>.</h1>
        <p class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-500">Kondisi operasional bengkel Anda. Pekerjaan selesai belum berarti lunas.</p>
    </div>
    <div class="mb-6">@include('vehicles._lookup')</div>
    @include('reports._dashboard', ['d'=>$operations])
    <div class="grid gap-5 md:grid-cols-2">
        <section class="min-w-0 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="workshop-heading">
            <p class="text-xs font-semibold uppercase tracking-widest text-slate-500">Bengkel Anda</p>
            <h2 id="workshop-heading" class="mt-4 break-words text-2xl font-bold">{{ $activeWorkshop->name }}</h2>
            <p class="mt-3 text-sm text-slate-500">Akses Anda: <span class="font-medium text-slate-700">{{ match ($activeWorkshop->pivot->role) { \App\Enums\WorkshopRole::Owner => 'Pemilik', \App\Enums\WorkshopRole::Cashier => 'Kasir', \App\Enums\WorkshopRole::Mechanic => 'Mekanik' } }}</span></p>
            <a href="{{ route('workshops.select') }}" class="auth-link mt-7 inline-flex min-h-11 items-center text-sm">Ganti bengkel <span aria-hidden="true" class="ml-2">→</span></a>
        </section>
        <section class="min-w-0 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 sm:p-8" aria-labelledby="subscription-heading">
            <p class="text-xs font-semibold uppercase tracking-widest text-emerald-800">Paket bengkel</p>
            <h2 id="subscription-heading" class="mt-4 text-2xl font-bold text-emerald-950">{{ $statusLabel }}</h2>
            @if ($subscription)
                <p class="mt-3 break-words text-sm text-emerald-900">{{ $subscription->plan->name }}</p>
                @if ($trialDays !== null)
                    <p class="mt-5 text-emerald-950"><span class="text-4xl font-bold tabular-nums">{{ $trialDays }}</span> <span class="ml-1 text-sm">hari tersisa</span></p>
                @endif
                @if ($trialDeadline)
                    <p class="mt-3 text-xs leading-relaxed text-emerald-800">Batas trial: {{ $trialDeadline }} ({{ $displayTimezone }})</p>
                @endif
            @else
                <p class="mt-3 text-sm leading-relaxed text-emerald-900">Informasi paket belum tersedia untuk bengkel ini.</p>
            @endif
        </section>
    </div>
    <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8" aria-labelledby="setup-heading">
        <h2 id="setup-heading" class="text-lg font-semibold">Kesiapan akun</h2>
        <p class="mt-2 text-sm text-slate-500">Informasi awal untuk ruang kerja Anda.</p>
        <ul class="mt-6 divide-y divide-slate-100 text-sm">
            <li class="flex items-center justify-between gap-4 py-4"><span>Akun berhasil masuk</span><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">Selesai</span></li>
            <li class="flex items-center justify-between gap-4 py-4"><span>Bengkel aktif dipilih</span><span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800">Selesai</span></li>
            <li class="flex items-center justify-between gap-4 py-4"><span>Informasi paket</span><span class="text-xs text-slate-500">{{ $subscription ? 'Tersedia' : 'Belum tersedia' }}</span></li>
        </ul>
    </section>
</x-app-layout>
