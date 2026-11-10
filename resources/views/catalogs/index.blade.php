<x-app-layout :title="$title" :active-workshop="$activeWorkshop">
    <div class="flex flex-wrap items-center justify-between gap-4"><h1 class="text-3xl font-bold">{{ $title }}</h1>
        @can('create', [$service ? \App\Models\Service::class : \App\Models\SparePart::class, $activeWorkshop])<a href="{{ route($prefix.'.create') }}" class="auth-button w-auto">Tambah {{ strtolower($title) }}</a>@endcan
    </div>
    <form method="GET" class="my-6 flex flex-wrap items-end gap-3">
        <label class="min-w-0 flex-1"><span class="text-sm">{{ $service ? 'Cari nama jasa' : 'Cari nama atau SKU' }}</span><input type="search" name="q" value="{{ request('q') }}" maxlength="100" class="auth-input mt-2"></label>
        <label><span class="text-sm">Status</span><select name="status" class="auth-input mt-2">@foreach (['active'=>'Aktif','inactive'=>'Nonaktif','all'=>'Semua'] as $value=>$label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></label>
        @if (!$service)<label class="flex min-h-11 items-center gap-2 text-sm"><input name="low" type="checkbox" value="1" @checked(request('low') === '1')>Stok menipis</label>@endif
        <button class="auth-button w-auto">Cari</button>
    </form>
    <div class="space-y-3">@forelse ($records as $record)
        <a href="{{ route($prefix.'.show',$record) }}" class="block rounded-xl border border-slate-200 bg-white p-5 hover:border-emerald-500">
            <h2 class="break-words font-semibold text-emerald-800">{{ $record->name }}</h2>
            <p class="mt-2 text-sm">Rp {{ \App\Support\Rupiah::format($service ? $record->default_price : $record->selling_price) }} · {{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</p>
            @if (!$service)<p class="mt-2 break-words text-sm">{{ $record->sku ?? 'Tanpa SKU' }} · Stok {{ $record->current_stock }} {{ $record->unit }}
                @if ($record->minimum_stock !== null && $record->current_stock <= $record->minimum_stock)<span class="font-semibold text-amber-800"> · Stok menipis</span>@endif
            </p>@endif
        </a>
    @empty <p class="rounded-xl bg-white p-6">Tidak ada katalog yang cocok.</p>@endforelse</div>
    <div class="mt-6">{{ $records->links() }}</div>
</x-app-layout>
