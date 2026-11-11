<x-app-layout title="Detail kendaraan" :active-workshop="$activeWorkshop">
    <a class="auth-link block min-h-11" href="{{ route('service-history.index',['vehicle_id'=>$vehicle->id,'preset'=>'all','workshop_context'=>$activeWorkshop->id]) }}">Riwayat servis kendaraan — semua waktu</a>
    <a href="{{ route('vehicles.index') }}" class="auth-link inline-flex min-h-11 items-center text-sm">← Kembali ke kendaraan</a>
    <div class="mt-4 flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
        <h1 class="break-words text-3xl font-bold">{{ $vehicle->plate_number }}</h1>
        @can('update', [$vehicle, $activeWorkshop]) <a href="{{ route('vehicles.edit', $vehicle) }}" class="auth-button w-auto shrink-0">Ubah kendaraan</a> @endcan
    </div>
    @if ($activeOrder)
        @can('view', [$activeOrder, $activeWorkshop])<a href="{{ route('work-orders.show', $activeOrder) }}" class="auth-link mt-4 inline-flex min-h-11 items-center">Buka WO aktif</a>@endcan
    @else
        @can('create', [\App\Models\WorkOrder::class, $activeWorkshop])<a href="{{ route('work-orders.create', ['vehicle_id' => $vehicle->id]) }}" class="auth-link mt-4 inline-flex min-h-11 items-center">Buat WO</a>@endcan
    @endif
    <dl class="mt-8 space-y-6 rounded-2xl border border-slate-200 bg-white p-6 sm:p-8">
        <div><dt class="text-sm text-slate-500">Pelanggan saat ini</dt><dd class="mt-2 break-words font-medium">
            @if ($vehicle->customer) <a href="{{ route('customers.show', $vehicle->customer) }}" class="auth-link">{{ $vehicle->customer->name }}</a> @else Tanpa pelanggan @endif
        </dd></div>
        <div><dt class="text-sm text-slate-500">Merek / model</dt><dd class="mt-2 break-words">{{ trim(($vehicle->brand ?? '').' '.($vehicle->model ?? '')) ?: 'Belum diisi' }}</dd></div>
        <div><dt class="text-sm text-slate-500">Tahun</dt><dd class="mt-2">{{ $vehicle->year ?? 'Belum diisi' }}</dd></div>
        <div><dt class="text-sm text-slate-500">Catatan</dt><dd class="mt-2 whitespace-pre-line break-words text-sm">{{ $vehicle->notes ?? 'Belum ada catatan.' }}</dd></div>
    </dl>
</x-app-layout>
