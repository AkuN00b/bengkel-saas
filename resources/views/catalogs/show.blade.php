<x-app-layout :title="$record->name" :active-workshop="$activeWorkshop">
    <a class="auth-link inline-flex min-h-11 items-center" href="{{ route($prefix.'.index') }}">Kembali ke {{ strtolower($title) }}</a>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-4"><h1 class="min-w-0 break-words text-3xl font-bold">{{ $record->name }}</h1>
        @can('update',[$record,$activeWorkshop])<a href="{{ route($prefix.'.edit',$record) }}" class="auth-link min-h-11 py-3">Ubah katalog</a>@endcan
    </div>
    @if ($errors->any())<div role="alert" class="mt-4 rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
    <section class="mt-6 space-y-3 rounded-2xl border border-slate-200 bg-white p-5">
        <p>Rp {{ \App\Support\Rupiah::format($service ? $record->default_price : $record->selling_price) }} · {{ $record->is_active ? 'Aktif' : 'Nonaktif' }}</p>
        @if ($service)<p class="whitespace-pre-line break-words text-sm">{{ $record->description }}</p>
        @else <p class="break-words text-sm">SKU: {{ $record->sku ?? 'Belum diisi' }} · Satuan: {{ $record->unit }}</p><p class="text-xl font-bold">Stok: {{ $record->current_stock }} {{ $record->unit }}</p><p class="text-sm">Batas minimum: {{ $record->minimum_stock ?? 'Tidak diatur' }}</p>@endif
        @can('update',[$record,$activeWorkshop])<form method="POST" action="{{ route($prefix.'.activation',$record) }}">@csrf @method('PATCH')<input name="workshop_context" type="hidden" value="{{ $activeWorkshop->id }}"><input name="version" type="hidden" value="{{ $record->version }}"><input name="is_active" type="hidden" value="{{ $record->is_active ? '0' : '1' }}"><button class="auth-link min-h-11 text-sm">{{ $record->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ strtolower($title) }}</button></form>@endcan
    </section>
    @if (!$service)
        <div class="mt-6 grid gap-5 lg:grid-cols-2">
            @if ($record->is_active)<details class="rounded-xl bg-white p-5"><summary class="auth-link min-h-11 cursor-pointer py-3">Restock</summary>@include('catalogs._stock-form',['type'=>'restock','label'=>'Catat restock'])</details>@endif
            @can('adjust',[$record,$activeWorkshop])<details class="rounded-xl bg-white p-5"><summary class="auth-link min-h-11 cursor-pointer py-3">Koreksi stok</summary><p class="mt-3 text-sm">Catat selisih fisik dengan alasan. Riwayat lama tidak diubah.</p>@include('catalogs._stock-form',['type'=>'adjustment_in','label'=>'Catat koreksi masuk'])@include('catalogs._stock-form',['type'=>'adjustment_out','label'=>'Catat koreksi keluar'])</details>@endcan
        </div>
        <h2 class="mt-8 text-xl font-bold">Riwayat stok</h2><p class="mt-2 text-sm text-slate-600">Waktu dalam {{ $activeWorkshop->timezone }}. Retur hanya mengubah stok, bukan harga/subtotal WO atau pengembalian uang.</p>
        <ol class="mt-4 space-y-4">@forelse ($movements as $movement)
            <li class="rounded-xl border border-slate-200 bg-white p-5">
                <p class="font-semibold">{{ match($movement->type) {'restock'=>'Restock','adjustment_in'=>'Koreksi masuk','adjustment_out'=>'Koreksi keluar','work_order_usage'=>'Dipakai pada WO','work_order_return'=>'Retur dari WO'} }} · {{ $movement->quantity }} {{ $movement->unit_snapshot }}</p>
                <p class="mt-2 break-words text-sm">{{ $movement->name_snapshot }} · Saldo {{ $movement->balance_before }} → {{ $movement->balance_after }}</p>
                @if ($movement->order)<a class="auth-link inline-flex min-h-11 items-center text-sm" href="{{ route('work-orders.show',$movement->order) }}">{{ $movement->order->number }}</a>@endif
                @if ($movement->reason)<p class="mt-2 whitespace-pre-line break-words text-sm">{{ $movement->reason }}</p>@endif
                <p class="mt-2 break-words text-xs text-slate-500">{{ $movement->actor->name }} · {{ $movement->created_at->timezone($activeWorkshop->timezone)->format('d/m/Y H:i') }}</p>
                @if ($movement->type === 'work_order_usage')<p class="mt-2 text-sm">Sudah diretur: {{ (int)$movement->returned_quantity }} dari {{ $movement->quantity }}</p>
                    @if (!$movement->order->status->isActive() && $movement->quantity > (int)$movement->returned_quantity)
                        @can('adjust',[$record,$activeWorkshop])<details class="mt-3"><summary class="auth-link min-h-11 cursor-pointer py-3 text-sm">Retur WO final</summary>
                            <form method="POST" action="{{ route('spare-parts.returns',[$record,$movement]) }}" class="mt-3 space-y-3">@csrf<input name="workshop_context" type="hidden" value="{{ $activeWorkshop->id }}"><input name="version" type="hidden" value="{{ $record->version }}"><input name="operation_key" type="hidden" value="{{ \Illuminate\Support\Str::uuid() }}">
                                <p class="text-sm">Pastikan barang sudah kembali dan layak masuk stok. WO final tetap terkunci.</p>
                                <label class="block text-sm">Jumlah retur<input type="number" name="quantity" min="1" max="{{ $movement->quantity - (int)$movement->returned_quantity }}" step="1" required class="auth-input mt-2"></label>
                                <label class="block text-sm">Alasan retur<textarea name="reason" required maxlength="1000" rows="2" class="auth-input mt-2"></textarea></label><button class="auth-button w-auto">Catat retur WO final</button>
                            </form>
                        </details>@endcan
                    @endif
                @endif
            </li>
        @empty <li class="rounded-xl bg-white p-5">Belum ada pergerakan stok.</li>@endforelse</ol><div class="mt-5">{{ $movements->links() }}</div>
    @endif
</x-app-layout>
