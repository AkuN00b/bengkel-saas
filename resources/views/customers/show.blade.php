<x-app-layout title="Detail pelanggan" :active-workshop="$activeWorkshop">
    <a href="{{ route('customers.index') }}" class="auth-link inline-flex min-h-11 items-center text-sm">← Kembali ke pelanggan</a>
    <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <h1 class="min-w-0 break-words text-3xl font-bold tracking-tight">{{ $customer->name }}</h1>
        @can('update', [$customer, $activeWorkshop])
            <a href="{{ route('customers.edit', $customer) }}" class="auth-button w-auto shrink-0">Ubah pelanggan</a>
        @endcan
    </div>
    <dl class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
        <div>
            <dt class="text-sm text-slate-500">Nomor telepon</dt>
            <dd class="mt-2 break-words font-medium">{{ $customer->phone ?? 'Belum diisi' }}</dd>
        </div>
        <div>
            <dt class="text-sm text-slate-500">Catatan</dt>
            <dd class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed">{{ $customer->notes ?? 'Belum ada catatan.' }}</dd>
        </div>
    </dl>
</x-app-layout>
