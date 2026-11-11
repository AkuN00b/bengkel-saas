@php($money=fn($value)=>'Rp '.\App\Support\Rupiah::format((string)$value))
<section class="mb-8" aria-labelledby="operations-title">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4"><div><h2 id="operations-title" class="text-2xl font-bold">{{ $d['mechanic'] ? 'Tugas Anda' : 'Operasional bengkel' }}</h2><p class="mt-2 text-xs text-slate-500">{{ $d['generated']->format('d/m/Y H:i:s') }} · {{ $displayTimezone }}</p></div><a href="{{ route('dashboard') }}" class="auth-button w-auto">Perbarui</a></div>
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach(['draft'=>'Draft','open'=>'Antre','in_progress'=>'Dikerjakan'] as $status=>$label)<a class="min-w-0 rounded-xl border border-slate-200 bg-white p-5" href="{{ route('work-orders.index',['status'=>$status]) }}"><p class="text-sm text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-bold">{{ $d['counts'][$status] ?? 0 }}</p></a>@endforeach
        <a class="min-w-0 rounded-xl border border-slate-200 bg-white p-5" href="{{ route('service-history.index',['preset'=>'today','status'=>'completed','date_basis'=>'completed','workshop_context'=>$activeWorkshop->id]) }}"><p class="text-sm text-slate-500">Selesai hari ini</p><p class="mt-2 text-3xl font-bold">{{ $d['completed'] }}</p></a>
        @unless($d['mechanic'])
            <a href="{{ route('invoices.index',['payment'=>'outstanding','workshop_context'=>$activeWorkshop->id]) }}" class="min-w-0 rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Invoice belum lunas</p><p class="mt-2 text-3xl font-bold">{{ $d['outstandingCount'] }}</p></a>
            <a href="{{ route('spare-parts.index',['low'=>1,'status'=>'active']) }}" class="min-w-0 rounded-xl border border-slate-200 bg-white p-5"><p class="text-sm text-slate-500">Barang stok menipis</p><p class="mt-2 text-3xl font-bold">{{ $d['lowCount'] }}</p></a>
        @endunless
    </div>
    @if($d['owner'])
        <div class="my-5 grid grid-cols-2 gap-3 lg:grid-cols-3">
            @foreach(['payments'=>['received'=>'Pembayaran tercatat hari ini','correction'=>'Koreksi pencatatan hari ini','net'=>'Penerimaan tercatat bersih hari ini'],'invoiceValues'=>['received'=>'Nilai invoice terbit hari ini','correction'=>'Pembatalan invoice hari ini','net'=>'Nilai invoice bersih hari ini']] as $group=>$fields)
                @foreach($fields as $field=>$label)<a class="min-w-0 rounded-xl border border-emerald-200 bg-emerald-50 p-5" href="{{ route($group === 'payments' ? 'reports.payments.index' : 'reports.invoice-values.index',['preset'=>'today','workshop_context'=>$activeWorkshop->id]) }}"><p class="text-sm text-emerald-900">{{ $label }}</p><p class="mt-2 break-words text-xl font-bold">{{ $money($d[$group]->$field) }}</p></a>@endforeach
            @endforeach
            <a href="{{ route('reports.receivables.index') }}" class="min-w-0 rounded-xl border border-emerald-200 bg-emerald-50 p-5"><p class="text-sm text-emerald-900">Total sisa tagihan saat ini</p><p class="mt-2 break-words text-xl font-bold">{{ $money($d['receivables']->remaining) }}</p></a>
        </div>
        <h3 class="mb-3 mt-6 text-lg font-semibold">Penerimaan tercatat tujuh hari</h3>
        <p class="mb-3 text-xs text-slate-500">Koreksi adalah koreksi pencatatan, bukan refund. Nilai invoice bukan penerimaan uang atau laba.</p>
        <div class="mb-6 overflow-x-auto rounded-xl bg-white"><table class="w-full text-left text-sm"><thead><tr><th class="p-3">Tanggal</th><th class="p-3">Pembayaran</th><th class="p-3">Koreksi</th><th class="p-3">Bersih</th></tr></thead><tbody>@foreach($d['trend'] as $day)<tr class="border-t border-slate-100"><td class="p-3"><a class="auth-link" href="{{ route('reports.payments.index',['preset'=>'custom','from'=>$day->day,'to'=>$day->day,'workshop_context'=>$activeWorkshop->id]) }}">{{ $day->day }}</a></td><td class="whitespace-nowrap p-3">{{ $money($day->received) }}</td><td class="whitespace-nowrap p-3">{{ $money($day->correction) }}</td><td class="whitespace-nowrap p-3">{{ $money($day->net) }}</td></tr>@endforeach</tbody></table></div>
        <h3 class="mb-3 mt-6 text-lg font-semibold">Lima invoice belum lunas tertua</h3>
        <ul class="space-y-3">@forelse($d['oldest'] as $invoice)<li><a href="{{ route('reports.receivables.show',['invoice'=>$invoice->invoice_id]) }}" class="auth-link block break-words rounded-xl bg-white p-4">{{ $invoice->number }} · {{ $invoice->plate }} · Sisa {{ $money($invoice->remaining) }}</a></li>@empty<li class="rounded-xl bg-white p-4 text-sm">Tidak ada invoice belum lunas.</li>@endforelse</ul>
    @endif
    <h3 class="mb-3 mt-6 text-lg font-semibold">Lima WO aktif terbaru</h3>
    <ul class="space-y-3">@forelse($d['recent'] as $order)<li><a class="auth-link block break-words rounded-xl bg-white p-4" href="{{ route('work-orders.show',$order) }}">{{ $order->number }} · {{ $order->plate_number_snapshot ?? $order->vehicle?->plate_number }} · {{ $order->status->label() }}</a></li>@empty<li class="rounded-xl bg-white p-4 text-sm">{{ $d['mechanic'] ? 'Belum ada tugas aktif untuk Anda.' : 'Belum ada WO aktif. Cari kendaraan untuk menerima pekerjaan.' }}</li>@endforelse</ul>
    @unless($d['mechanic'])
        <h3 class="mb-3 mt-6 text-lg font-semibold">Lima barang stok menipis</h3>
        <ul class="space-y-3">@forelse($d['low'] as $part)<li><a class="auth-link block break-words rounded-xl bg-white p-4" href="{{ route('spare-parts.show',$part) }}">{{ $part->name }} · {{ $part->current_stock }} {{ $part->unit }} / minimum {{ $part->minimum_stock }}</a></li>@empty<li class="rounded-xl bg-white p-4 text-sm">Tidak ada barang aktif yang mencapai batas minimum.</li>@endforelse</ul>
    @endunless
    <div class="my-6 flex flex-wrap gap-4"><a href="{{ route('work-orders.index') }}" class="auth-link min-h-11">{{ $d['mechanic'] ? 'Semua tugas Anda' : 'Daftar perintah kerja' }}</a><a href="{{ route('service-history.index') }}" class="auth-link min-h-11">Riwayat servis</a>@unless($d['mechanic'])<a href="{{ route('work-orders.create') }}" class="auth-link min-h-11">Terima pekerjaan</a>@endunless</div>
</section>
