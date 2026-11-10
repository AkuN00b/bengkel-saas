<x-app-layout :title="$order->exists ? 'Ubah WO' : 'Buat WO'" :active-workshop="$activeWorkshop">
    <a class="auth-link inline-flex min-h-11 items-center" href="{{ $order->exists ? route('work-orders.show', $order) : route('work-orders.index') }}">Kembali ke WO</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $order->exists ? 'Ubah '.$order->number : 'Buat WO' }}</h1>
    @if ($errors->any()) <div role="alert" class="my-4 rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
    @if ($vehicles !== null)
        <details class="mt-6 rounded-2xl border border-slate-200 bg-white p-5" @if (!$vehicle) open @endif>
            <summary class="auth-link min-h-11 cursor-pointer py-2">Cari / ganti kendaraan</summary>
            <form method="GET" class="mt-3 flex gap-3"><label class="min-w-0 flex-1"><span class="text-sm">Cari nomor pelat</span><input name="q" type="search" value="{{ request('q') }}" maxlength="100" class="auth-input mt-2"></label><button class="auth-button mt-7 w-auto self-start">Cari</button></form>
            <ul class="mt-4 space-y-2">
                @foreach ($vehicles as $option)<li><a class="auth-link inline-flex min-h-11 items-center break-words" href="{{ ($order->exists ? route('work-orders.edit', $order) : route('work-orders.create')).'?vehicle_id='.$option->id }}">Pilih {{ $option->plate_number }}</a></li>@endforeach
            </ul>
            @if ($vehicles->isEmpty())<p class="my-3 text-sm">Kendaraan belum ditemukan. <a class="auth-link" href="{{ route('vehicles.create') }}">Tambah kendaraan</a></p>@endif
            {{ $vehicles->links() }}
        </details>
    @endif
    @if ($vehicle)
        <form method="POST" action="{{ $order->exists ? route('work-orders.update', $order) : route('work-orders.store') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-8">
            @csrf @if ($order->exists) @method('PUT') <input type="hidden" name="version" value="{{ $order->version }}"> @endif
            <input type="hidden" name="workshop_context" value="{{ $activeWorkshop->id }}"><input type="hidden" name="vehicle_id" value="{{ $vehicle->id }}">
            <p class="break-words font-semibold">Kendaraan: {{ $vehicle->plate_number }}</p>
            @if (!$order->exists || $order->status === \App\Enums\WorkOrderStatus::Draft)
                <div x-data="customerPicker(@js(route('vehicles.customers')), {{ $activeWorkshop->id }}, @js($selectedCustomer?->toArray()))" class="rounded-xl border border-slate-200 p-4">
                    <input type="hidden" name="customer_id" value="{{ $selectedCustomer?->id }}" :value="selected?.id ?? ''">
                    <p class="text-sm font-medium">Pelanggan penerimaan (opsional)</p>
                    <p class="mt-2 break-words text-sm" x-text="selected ? selected.name + (selected.phone ? ' · ' + selected.phone : '') : 'Tanpa pelanggan'">{{ $selectedCustomer?->name ?? 'Tanpa pelanggan' }}</p>
                    <button type="button" x-show="selected" x-cloak @click="selected = null" class="auth-link min-h-11 text-sm">Lepas pelanggan</button>
                    <details class="mt-2"><summary class="auth-link min-h-11 cursor-pointer py-3 text-sm">Cari / ganti pelanggan</summary>
                        <label for="customer-search" class="text-sm">Nama atau telepon</label>
                        <input id="customer-search" x-model="query" @input.debounce.300ms="search()" @keydown.enter.prevent="search()" type="search" maxlength="100" class="auth-input mt-2">
                        <button type="button" @click="search()" class="auth-link min-h-11 text-sm">Cari pelanggan</button>
                        <p x-show="loading" x-cloak role="status" class="text-sm">Memuat pelanggan…</p>
                        <p x-show="error" x-cloak x-text="error" role="alert" class="text-sm text-red-700"></p>
                        <ul class="mt-3 max-h-64 space-y-2 overflow-y-auto" aria-label="Pilihan pelanggan"><template x-for="option in options" :key="option.id"><li><button type="button" @click="selected = option" class="min-h-11 w-full rounded-lg border border-slate-200 p-3 text-left text-sm"><span class="block break-words" x-text="option.name"></span><span class="text-slate-500" x-text="option.phone ?? ''"></span></button></li></template></ul>
                        <p x-show="!loading && !error && options.length === 0" x-cloak class="text-sm">Tidak ada pelanggan yang cocok.</p>
                        <button type="button" x-show="nextPage" x-cloak :disabled="loading" @click="load(nextPage, true)" class="auth-link min-h-11 text-sm">Muat lagi</button>
                    </details>
                    <noscript><p class="mt-2 text-sm">Aktifkan JavaScript untuk mengganti pelanggan, atau gunakan pelanggan awal kendaraan.</p></noscript>
                </div>
            @else <p class="text-sm text-slate-600">Kendaraan dan pelanggan penerimaan sudah dikunci sejak Antre.</p> @endif
            @foreach (['complaint' => 'Keluhan (opsional)', 'notes' => 'Catatan (opsional)'] as $field => $label)
                <div><label for="{{ $field }}" class="text-sm font-medium">{{ $label }}</label><textarea id="{{ $field }}" name="{{ $field }}" rows="4" maxlength="5000" class="auth-input mt-2">{{ is_string(old($field, $order->$field)) ? old($field, $order->$field) : '' }}</textarea></div>
            @endforeach
            <div class="flex flex-wrap gap-4"><button class="auth-button w-auto" name="intent" value="open">{{ $order->exists ? 'Simpan perubahan' : 'Terima pekerjaan' }}</button>
                @if (!$order->exists)<button name="intent" value="draft" class="auth-link min-h-11 px-2">Simpan draft</button>@endif
            </div>
        </form>
    @endif
</x-app-layout>
