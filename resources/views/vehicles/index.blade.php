<x-app-layout title="Kendaraan" :active-workshop="$activeWorkshop">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0"><h1 class="text-3xl font-bold">Kendaraan</h1><p class="mt-3 break-words text-sm text-slate-500">Kendaraan di {{ $activeWorkshop->name }}.</p></div>
        @can('create', [\App\Models\Vehicle::class, $activeWorkshop])
            <a href="{{ route('vehicles.create') }}" class="auth-button w-auto shrink-0">Tambah kendaraan</a>
        @endcan
    </div>
    <div class="mt-6">@include('vehicles._lookup')</div>
    <form method="GET" action="{{ route('vehicles.index') }}" class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
        <label for="q" class="block text-sm font-medium">Filter daftar berdasarkan sebagian pelat</label>
        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
            <input id="q" type="search" name="q" value="{{ $search }}" maxlength="20" class="auth-input min-w-0 flex-1" placeholder="Contoh: 1234" @error('q') aria-invalid="true" aria-describedby="q-error" @enderror>
            <button type="submit" class="auth-button w-auto">Filter</button>
            @if ($search !== '') <a href="{{ route('vehicles.index') }}" class="auth-link inline-flex min-h-11 items-center">Reset filter</a> @endif
        </div>
        @error('q') <p id="q-error" role="alert" class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
        @error('page') <p role="alert" class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
    </form>
    <p class="mt-6 text-sm text-slate-500">{{ $vehicles->total() }} kendaraan{{ $search !== '' ? ' ditemukan' : '' }}</p>
    <ul class="mt-4 space-y-3" aria-label="Daftar kendaraan">
        @forelse ($vehicles as $vehicle)
            <li class="rounded-2xl border border-slate-200 bg-white">
                <a href="{{ route('vehicles.show', $vehicle) }}" class="block min-h-20 rounded-2xl p-5 hover:bg-emerald-50 focus-visible:outline-2 focus-visible:outline-emerald-700">
                    <p class="break-words font-semibold">{{ $vehicle->plate_number }}</p>
                    <p class="mt-2 break-words text-sm text-slate-500">{{ $vehicle->customer?->name ?? 'Tanpa pelanggan' }}</p>
                    @if ($vehicle->brand || $vehicle->model) <p class="mt-1 break-words text-sm text-slate-500">{{ $vehicle->brand }} {{ $vehicle->model }}</p> @endif
                </a>
            </li>
        @empty
            <li class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-slate-500">{{ $search !== '' ? 'Tidak ada kendaraan yang cocok.' : 'Belum ada kendaraan di bengkel ini.' }}</li>
        @endforelse
    </ul>
    <div class="mt-6">{{ $vehicles->links() }}</div>
</x-app-layout>
