@php($money=fn($value)=>'Rp '.\App\Support\Rupiah::format((string)$value))
@php($time=fn($value)=>$value ? \Carbon\CarbonImmutable::parse($value,'UTC')->timezone($f['timezone'])->format('d/m/Y H:i:s') : '—')
<x-app-layout title="Penelusuran invoice" :active-workshop="$activeWorkshop">
    <h1 class="text-3xl font-bold">Penelusuran invoice</h1>
    @include('reports._metadata')
    <div class="report-actions mb-6 flex flex-wrap gap-4"><a class="auth-link min-h-11" href="{{ route('reports.receivables.index',$f['params']) }}">Kembali ke piutang pada waktu ini</a><a class="auth-link min-h-11" href="{{ route('reports.receivables.trace.print',['invoice'=>$invoiceId]+$f['params']+['page'=>request('page',1)]) }}">Cetak A4 / Simpan PDF</a></div>
    @if(!$position)<p class="rounded-xl bg-white p-6">Invoice belum diterbitkan pada batas waktu pilihan. Belum ada piutang atau kronologi finansial pada waktu ini.</p>
    @else
        <section class="mb-6 rounded-xl bg-white p-5">
            <h2 class="text-xl font-semibold">{{ $position->number }}</h2>
            <p class="mt-2">{{ $position->work_order_number }} · {{ $position->plate }} · {{ $position->customer ?? 'Pelanggan umum' }}</p>
            <p class="mt-2">Status pada waktu pilihan: <strong>{{ $position->document_state === 'void' ? 'Dibatalkan' : 'Terbit' }}</strong>. Total {{ $money($position->total) }} · Pembayaran valid {{ $money($position->paid) }} · <strong>Sisa efektif {{ $money($position->remaining) }}</strong>.</p>
            <a href="{{ route('invoices.show',$invoiceId) }}" class="report-actions auth-link mt-3 inline-flex min-h-11 items-center">Buka dokumen saat ini</a>
        </section>
        <h2 class="mb-3 text-xl font-semibold">Kronologi sampai batas waktu · halaman {{ $events->currentPage() }} dari {{ $events->lastPage() }}</h2>
        <ol class="space-y-3">@foreach($events as $event)<li class="rounded-xl border border-slate-200 bg-white p-4"><p class="font-semibold">{{ $time($event->occurred_at) }} · {{ match($event->kind) {'issued'=>'Invoice terbit','payment'=>'Pembayaran masuk','reversal'=>'Koreksi pencatatan','void'=>'Invoice dibatalkan'} }}</p><p class="mt-2">{{ $money($event->amount) }}</p>@if($event->note)<p class="mt-2 whitespace-pre-line break-words text-sm">{{ $event->note }}</p>@endif</li>@endforeach</ol>
        <div class="report-actions mt-5">{{ $events->links() }}</div>
    @endif
</x-app-layout>
