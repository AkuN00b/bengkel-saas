<x-app-layout title="Pelanggan" :active-workshop="$activeWorkshop">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <h1 class="text-3xl font-bold tracking-tight">Pelanggan</h1>
            <p class="mt-3 break-words text-sm text-slate-500">Data pelanggan untuk {{ $activeWorkshop->name }}.</p>
        </div>
        @can('create', [\App\Models\Customer::class, $activeWorkshop])
            <a href="{{ route('customers.create') }}" class="auth-button w-auto shrink-0">Tambah pelanggan</a>
        @endcan
    </div>
    <form method="GET" action="{{ route('customers.index') }}" class="mt-8 rounded-2xl border border-slate-200 bg-white p-5">
        <label for="q" class="block text-sm font-medium">Cari pelanggan</label>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
            <input type="search" id="q" name="q" value="{{ $search }}" maxlength="100" placeholder="Nama atau nomor telepon" class="auth-input min-w-0 flex-1" @error('q') aria-invalid="true" aria-describedby="q-error" @enderror>
            <button type="submit" class="auth-button w-auto">Cari</button>
            @if ($search !== '')
                <a href="{{ route('customers.index') }}" class="auth-link inline-flex min-h-11 items-center justify-center px-3">Reset pencarian</a>
            @endif
        </div>
        @error('q') <p id="q-error" role="alert" class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('page') <p role="alert" class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
    </form>
    <p class="mt-6 text-sm text-slate-500">{{ $customers->total() }} pelanggan{{ $search !== '' ? ' ditemukan' : '' }}</p>
    <ul class="mt-4 space-y-3" aria-label="Daftar pelanggan">
        @forelse ($customers as $customer)
            <li class="rounded-2xl border border-slate-200 bg-white">
                <a href="{{ route('customers.show', $customer) }}" class="flex min-h-20 items-center justify-between gap-4 rounded-2xl p-5 transition hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-emerald-700">
                    <div class="min-w-0">
                        <p class="break-words font-semibold">{{ $customer->name }}</p>
                        <p class="mt-2 break-words text-sm text-slate-500">{{ $customer->phone ?? 'Tanpa nomor telepon' }}</p>
                    </div>
                    <span aria-hidden="true" class="shrink-0 text-emerald-700">→</span>
                </a>
            </li>
        @empty
            <li class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-600">{{ $search !== '' ? 'Pelanggan tidak ditemukan. Coba nama atau nomor telepon lain.' : 'Belum ada pelanggan di bengkel ini.' }}</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $customers->links() }}</div>
</x-app-layout>
