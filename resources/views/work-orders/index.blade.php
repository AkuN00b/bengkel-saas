<x-app-layout title="Perintah Kerja" :active-workshop="$activeWorkshop">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div><h1 class="text-3xl font-bold">Perintah Kerja</h1><p class="mt-2 text-sm text-slate-600">Pantau pekerjaan bengkel. Selesai pekerjaan belum berarti lunas.</p></div>
        @can('create', [\App\Models\WorkOrder::class, $activeWorkshop])<a href="{{ route('work-orders.create') }}" class="auth-button w-auto">Buat WO</a>@endcan
    </div>
    <form method="GET" class="my-6 flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1"><label for="q" class="text-sm">Nomor WO atau pelat</label><input id="q" name="q" value="{{ $search }}" maxlength="100" class="auth-input mt-2" type="search"></div>
        <div><label for="status" class="text-sm">Status</label><select name="status" id="status" class="auth-input mt-2">
            <option value="active" @selected($status === 'active')>Pekerjaan aktif</option><option value="all" @selected($status === 'all')>Semua status</option>
            @foreach (\App\Enums\WorkOrderStatus::cases() as $state)<option value="{{ $state->value }}" @selected($status === $state->value)>{{ $state->label() }}</option>@endforeach
        </select></div><button class="auth-button w-auto">Cari</button>
    </form>
    <div class="space-y-4">
        @forelse ($orders as $order)
            <a href="{{ route('work-orders.show', $order) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 hover:border-emerald-500">
                <div class="flex flex-wrap justify-between gap-3"><span class="font-semibold text-emerald-800">{{ $order->number }}</span><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold">{{ $order->status->label() }}</span></div>
                <p class="mt-3 break-words text-xl font-bold">{{ $order->plate_number_snapshot ?? $order->vehicle->plate_number }}</p>
                <p class="mt-2 break-words text-sm text-slate-600">{{ $order->status === \App\Enums\WorkOrderStatus::Draft ? ($order->customer?->name ?? 'Tanpa pelanggan') : ($order->customer_name_snapshot ?? 'Tanpa pelanggan') }}</p>
                <p class="mt-2 break-words text-sm">Mekanik: {{ $order->mechanic_name_snapshot ?? 'Belum ditugaskan' }}</p>
            </a>
        @empty <p class="rounded-xl bg-white p-6 text-slate-600">Tidak ada WO yang cocok.</p> @endforelse
    </div><div class="mt-6">{{ $orders->links() }}</div>
</x-app-layout>
