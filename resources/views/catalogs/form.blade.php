<x-app-layout :title="($record->exists ? 'Ubah ' : 'Tambah ').strtolower($title)" :active-workshop="$activeWorkshop">
    <a class="auth-link inline-flex min-h-11 items-center" href="{{ route($prefix.'.index') }}">Kembali ke katalog</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $record->exists ? 'Ubah' : 'Tambah' }} {{ strtolower($title) }}</h1>
    @if ($errors->any())<div role="alert" class="mt-5 rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <form method="POST" action="{{ $record->exists ? route($prefix.'.update',$record) : route($prefix.'.store') }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-8">
        @csrf @if ($record->exists) @method('PUT')<input name="version" type="hidden" value="{{ $record->version }}">@endif
        <input name="workshop_context" type="hidden" value="{{ $activeWorkshop->id }}">
        <x-auth-field name="name" label="Nama" :value="$record->name" maxlength="255" required />
        @if ($service)
            <label class="block text-sm">Deskripsi (opsional)<textarea name="description" maxlength="5000" rows="4" class="auth-input mt-2">{{ is_string(old('description',$record->description)) ? old('description',$record->description) : '' }}</textarea></label>
            <x-auth-field name="default_price" label="Harga standar (Rupiah)" type="number" :value="(string)($record->default_price ?? 0)" min="0" step="1" required />
        @else
            <x-auth-field name="sku" label="SKU (opsional)" :value="$record->sku" maxlength="100" />
            <x-auth-field name="unit" label="Satuan (tetap setelah dibuat)" :value="$record->unit ?? 'pcs'" maxlength="20" :readonly="$record->exists" required />
            <x-auth-field name="selling_price" label="Harga jual (Rupiah)" type="number" :value="(string)($record->selling_price ?? 0)" min="0" step="1" required />
            <x-auth-field name="minimum_stock" label="Batas minimum stok (opsional)" type="number" :value="$record->minimum_stock === null ? null : (string)$record->minimum_stock" min="0" step="1" />
            <p class="text-sm text-slate-600">Stok awal nol. Gunakan Restock di detail barang untuk mencatat stok awal beserta riwayatnya.</p>
        @endif
        <button class="auth-button w-auto">Simpan {{ strtolower($title) }}</button>
    </form>
</x-app-layout>
