<x-app-layout :title="$service ? 'Jasa WO' : 'Spare part WO'" :active-workshop="$activeWorkshop">
    <a class="auth-link inline-flex min-h-11 items-center" href="{{ route('work-orders.show',$order) }}">Kembali ke {{ $order->number }}</a>
    <h1 class="mt-4 text-3xl font-bold">{{ $line ? 'Ubah' : 'Tambah' }} {{ $service ? 'jasa' : 'spare part' }}</h1>
    @if ($errors->any())<div role="alert" class="mt-4 rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    @if (!$line)
        <form method="GET" class="my-6 flex items-end gap-3"><label class="min-w-0 flex-1"><span class="text-sm">{{ $service ? 'Cari nama jasa' : 'Cari nama atau SKU' }}</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" class="auth-input mt-2"></label><button class="auth-button w-auto">Cari</button></form>
        <ul class="space-y-2">@foreach ($options as $option)<li><a class="auth-link flex min-h-11 flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4" href="{{ route($prefix.'.create',[$order,'catalog_id'=>$option->id]) }}"><span class="break-words">Pilih {{ $option->name }}</span><span class="text-sm">Rp {{ \App\Support\Rupiah::format($service ? $option->default_price : $option->selling_price) }}{{ $service ? '' : ' · Stok '.$option->current_stock.' '.$option->unit }}</span></a></li>@endforeach</ul><div class="mt-4">{{ $options->links() }}</div>
        @if ($options->isEmpty())<p class="mt-5 text-sm">Tidak ada katalog aktif yang cocok. Minta owner menambah atau mengaktifkan katalog.</p>@endif
    @endif
    @if ($line || $selected)
        <form method="POST" action="{{ $line ? route($prefix.'.update',[$order,$line]) : route($prefix.'.store',$order) }}" class="mt-6 max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-5">
            @include('work-orders._context') @if ($line) @method('PUT') @else <input type="hidden" name="catalog_id" value="{{ $selected->id }}"> @endif
            <p class="break-words font-semibold">{{ $line?->name_snapshot ?? $selected->name }}</p>
            @if (!$service && $line?->status === 'used')<p class="text-sm text-slate-600">Barang dan jumlah sudah dikunci. Harga masih bisa disesuaikan selama WO aktif.</p>@endif
            <x-auth-field name="quantity" label="Jumlah" type="number" :value="(string)($line?->quantity ?? 1)" min="1" max="2147483647" step="1" :readonly="!$service && $line?->status === 'used'" required />
            <x-auth-field name="unit_price" label="Harga satuan (Rupiah)" type="number" :value="(string)($line?->unit_price ?? ($service ? $selected->default_price : $selected->selling_price))" min="0" step="1" required />
            <p class="text-sm text-slate-600">Subtotal dihitung server. Harga ini tidak mengubah katalog. {{ $service ? '' : 'Penambahan rencana tidak mengurangi atau mencadangkan stok.' }}</p>
            <button class="auth-button w-auto">Simpan {{ $service ? 'jasa WO' : 'spare part WO' }}</button>
        </form>
    @endif
</x-app-layout>
