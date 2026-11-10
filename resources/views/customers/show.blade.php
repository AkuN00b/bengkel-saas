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
    <section class="mt-8" aria-labelledby="customer-vehicles-heading">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 id="customer-vehicles-heading" class="text-xl font-bold">Kendaraan pelanggan</h2>
            @can('create', [\App\Models\Vehicle::class, $activeWorkshop])
                <a href="{{ route('vehicles.create', ['customer_id' => $customer->id]) }}" class="auth-button w-auto">Tambah kendaraan pelanggan</a>
            @endcan
        </div>
        <ul class="mt-4 space-y-3" aria-label="Kendaraan pelanggan">
            @forelse ($vehicles as $vehicle)
                <li><a href="{{ route('vehicles.show', $vehicle) }}" class="auth-link block min-h-11 break-words rounded-xl border border-slate-200 bg-white p-4">{{ $vehicle->plate_number }}</a></li>
            @empty
                <li class="text-sm text-slate-500">Belum ada kendaraan yang terhubung.</li>
            @endforelse
        </ul>
        <div class="mt-4">{{ $vehicles->links() }}</div>
    </section>
</x-app-layout>
