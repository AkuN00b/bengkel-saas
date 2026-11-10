<section class="mt-8" aria-label="Item pekerjaan">
    <h2 class="text-xl font-bold">Jasa dan spare part</h2><p class="mt-2 text-sm text-slate-600">Subtotal baris belum merupakan tagihan. Retur hanya mengubah stok; harga dan subtotal baris tidak otomatis dikurangi.</p>
    @can('update',[$order,$activeWorkshop])<div class="my-4 flex flex-wrap gap-4"><a class="auth-link inline-flex min-h-11 items-center" href="{{ route('work-orders.services.create',$order) }}">Tambah jasa WO</a><a class="auth-link inline-flex min-h-11 items-center" href="{{ route('work-orders.parts.create',$order) }}">Tambah spare part WO</a></div>@endcan
    <h3 class="mt-5 font-bold">Jasa</h3>
    <div class="mt-3 space-y-3">@forelse ($serviceLines as $line)
        <article class="rounded-xl border border-slate-200 bg-white p-5"><p class="break-words font-semibold">{{ $line->name_snapshot }} · {{ $line->status === 'cancelled' ? 'Dibatalkan' : 'Aktif' }}</p><p class="mt-2 whitespace-pre-line break-words text-sm">{{ $line->description_snapshot }}</p><p class="mt-2 text-sm">{{ $line->quantity }} × Rp {{ \App\Support\Rupiah::format($line->unit_price) }} = Rp {{ \App\Support\Rupiah::format($line->subtotal) }}</p>
            @if ($line->status === 'active') @can('update',[$order,$activeWorkshop])<div class="mt-3 flex flex-wrap gap-4"><a class="auth-link inline-flex min-h-11 items-center text-sm" href="{{ route('work-orders.services.edit',[$order,$line]) }}">Ubah jasa</a><form method="POST" action="{{ route('work-orders.services.cancel',[$order,$line]) }}">@include('work-orders._context')<button class="min-h-11 text-sm text-red-700">Batalkan jasa</button></form></div>@endcan @endif
        </article>
    @empty <p class="rounded-xl bg-white p-5 text-sm">Belum ada jasa.</p>@endforelse</div><div class="mt-4">{{ $serviceLines->links() }}</div>
    <h3 class="mt-6 font-bold">Spare part</h3>
    <div class="mt-3 space-y-3">@forelse ($partLines as $line)
        <article class="rounded-xl border border-slate-200 bg-white p-5">
            <p class="break-words font-semibold">{{ $line->name_snapshot }} · {{ match($line->status) {'planned'=>'Rencana','cancelled'=>'Dibatalkan','used'=>((int)$line->returned_quantity === $line->quantity ? 'Diretur seluruhnya' : ((int)$line->returned_quantity > 0 ? 'Diretur sebagian' : 'Dipakai'))} }}</p>
            <p class="mt-2 break-words text-sm">{{ $line->sku_snapshot ?? 'Tanpa SKU' }} · {{ $line->quantity }} {{ $line->unit_snapshot }} × Rp {{ \App\Support\Rupiah::format($line->unit_price) }} = Rp {{ \App\Support\Rupiah::format($line->subtotal) }}</p>
            @if ($line->status === 'used')<p class="mt-2 text-sm">Dipakai {{ $line->quantity }} · Diretur {{ (int)$line->returned_quantity }} {{ $line->unit_snapshot }}</p>@endif
            @if ($line->status !== 'cancelled') @can('update',[$order,$activeWorkshop])
                <a class="auth-link mt-2 inline-flex min-h-11 items-center text-sm" href="{{ route('work-orders.parts.edit',[$order,$line]) }}">{{ $line->status === 'used' ? 'Ubah harga barang' : 'Ubah rencana barang' }}</a>
                @if ($line->status === 'planned')
                    <form method="POST" action="{{ route('work-orders.parts.cancel',[$order,$line]) }}">@include('work-orders._context')<button class="min-h-11 text-sm text-red-700">Batalkan rencana barang</button></form>
                    @if ($order->status === \App\Enums\WorkOrderStatus::InProgress && $line->catalog->is_active)
                        <form method="POST" action="{{ route('work-orders.parts.use',[$order,$line]) }}" class="mt-3">@include('work-orders._context')<input name="stock_version" type="hidden" value="{{ $line->catalog->version }}"><input name="operation_key" type="hidden" value="{{ \Illuminate\Support\Str::uuid() }}"><button class="auth-button w-auto">Konfirmasi dipakai</button></form>
                    @endif
                @elseif ($line->quantity > (int)$line->returned_quantity)
                    <details class="mt-3"><summary class="auth-link min-h-11 cursor-pointer py-3 text-sm">Retur barang</summary>
                        <form method="POST" action="{{ route('work-orders.parts.return',[$order,$line]) }}" class="mt-3 space-y-3">@include('work-orders._context')<input name="stock_version" type="hidden" value="{{ $line->catalog->version }}"><input name="operation_key" type="hidden" value="{{ \Illuminate\Support\Str::uuid() }}">
                            <p class="text-sm">Pastikan barang kembali secara fisik dan layak masuk stok.</p><label class="block text-sm">Jumlah retur<input name="quantity" type="number" min="1" max="{{ $line->quantity - (int)$line->returned_quantity }}" step="1" required class="auth-input mt-2"></label><label class="block text-sm">Alasan retur<textarea name="reason" maxlength="1000" required rows="2" class="auth-input mt-2"></textarea></label><button class="auth-button w-auto">Catat retur barang</button>
                        </form>
                    </details>
                @endif
            @endcan @endif
            @if (!$order->status->isActive() && $line->status === 'used' && $line->quantity > (int)$line->returned_quantity) @can('adjust',[$line->catalog,$activeWorkshop])<a class="auth-link mt-3 inline-flex min-h-11 items-center text-sm" href="{{ route('spare-parts.show',$line->catalog) }}">Catat retur terpisah di riwayat stok</a>@endcan @endif
        </article>
    @empty <p class="rounded-xl bg-white p-5 text-sm">Belum ada spare part.</p>@endforelse</div><div class="mt-4">{{ $partLines->links() }}</div>
</section>
