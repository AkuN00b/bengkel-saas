@csrf
@if ($vehicle->exists) @method('PUT') @endif
<input type="hidden" name="workshop_context" value="{{ $activeWorkshop->id }}">
@error('workshop_context') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
<x-auth-field name="plate_number" label="Nomor pelat" :value="$vehicle->plate_number" maxlength="20" placeholder="Contoh: B 1234 ABC" autocapitalize="characters" required autofocus />
<p class="text-xs text-slate-500">Gunakan huruf dan angka. Spasi dan huruf besar/kecil tidak membedakan kendaraan.</p>
<div x-data="customerPicker(@js(route('vehicles.customers')), {{ $activeWorkshop->id }}, @js($selectedCustomer?->toArray()))" class="rounded-xl border border-slate-200 p-4">
    <input type="hidden" name="customer_id" value="{{ $selectedCustomer?->id }}" :value="selected?.id ?? ''">
    <p class="text-sm font-medium">Pelanggan (opsional)</p>
    <p class="mt-2 break-words text-sm text-slate-600" x-text="selected ? selected.name + (selected.phone ? ' · ' + selected.phone : '') : 'Tanpa pelanggan'">{{ $selectedCustomer?->name ?? 'Tanpa pelanggan' }}</p>
    <button type="button" x-show="selected" x-cloak @click="selected = null" class="auth-link mt-2 min-h-11 text-sm">Lepas pelanggan</button>
    <details class="mt-3">
        <summary class="min-h-11 cursor-pointer py-3 text-sm font-medium text-emerald-800">Cari atau pilih pelanggan</summary>
        <label for="customer-search" class="block text-sm">Cari nama atau nomor telepon</label>
        <div class="mt-2 flex gap-2">
            <input id="customer-search" type="search" maxlength="100" x-model="query" @input.debounce.300ms="search()" @keydown.enter.prevent="search()" class="auth-input min-w-0 flex-1" autocomplete="off">
            <button type="button" @click="search()" class="auth-button w-auto">Cari</button>
        </div>
        <p x-show="loading" x-cloak role="status" class="mt-3 text-sm text-slate-500">Memuat pelanggan…</p>
        <p x-show="error" x-text="error" x-cloak role="alert" class="mt-3 text-sm text-red-700"></p>
        <ul class="mt-3 max-h-64 space-y-2 overflow-y-auto" aria-label="Pilihan pelanggan">
            <template x-for="option in options" :key="option.id">
                <li><button type="button" @click="selected = option" class="min-h-11 w-full rounded-lg border border-slate-200 p-3 text-left text-sm hover:bg-emerald-50"><span class="block break-words font-medium" x-text="option.name"></span><span class="block break-words text-slate-500" x-text="option.phone ?? 'Tanpa nomor telepon'"></span></button></li>
            </template>
        </ul>
        <p x-show="!loading && !error && options.length === 0" x-cloak class="mt-3 text-sm text-slate-500">Belum ada pelanggan yang cocok.</p>
        <button type="button" x-show="nextPage" x-cloak :disabled="loading" @click="load(nextPage, true)" class="auth-link mt-2 min-h-11 text-sm">Muat lagi</button>
        <a href="{{ route('customers.create') }}" target="_blank" rel="noopener" class="auth-link mt-3 inline-flex min-h-11 items-center text-sm">Tambah pelanggan di tab baru</a>
    </details>
    <noscript><p class="mt-3 text-sm text-slate-500">Aktifkan JavaScript untuk mencari pelanggan. Kendaraan tetap dapat disimpan tanpa pelanggan atau dengan pelanggan yang sudah dipilih.</p></noscript>
    @error('customer_id') <p role="alert" class="mt-3 text-sm text-red-700">{{ $message }}</p> @enderror
</div>
<x-auth-field name="brand" label="Merek (opsional)" :value="$vehicle->brand" maxlength="100" placeholder="Contoh: Honda" />
<x-auth-field name="model" label="Model (opsional)" :value="$vehicle->model" maxlength="100" placeholder="Contoh: Vario 125" />
<x-auth-field name="year" label="Tahun (opsional)" type="number" :value="$vehicle->year === null ? null : (string) $vehicle->year" min="1900" :max="min(2100, now()->year + 1)" inputmode="numeric" />
<div>
    <label for="notes" class="block text-sm font-medium">Catatan (opsional)</label>
    @php($notes = old('notes', $vehicle->notes))
    <textarea id="notes" name="notes" rows="4" maxlength="5000" class="auth-input mt-2 resize-y" @error('notes') aria-invalid="true" aria-describedby="notes-error" @enderror>{{ is_string($notes) ? $notes : '' }}</textarea>
    @error('notes') <p id="notes-error" role="alert" class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
</div>
<div class="flex flex-col gap-3 sm:flex-row">
    <button type="submit" class="auth-button w-auto">{{ $vehicle->exists ? 'Simpan perubahan' : 'Simpan kendaraan' }}</button>
    <a href="{{ $vehicle->exists ? route('vehicles.show', $vehicle) : route('vehicles.index') }}" class="auth-link inline-flex min-h-11 items-center justify-center px-4">Batal</a>
</div>
