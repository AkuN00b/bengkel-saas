@php
    $title=match($kind) { 'payments'=>'Pembayaran dan koreksi','invoice-values'=>'Nilai invoice jasa dan spare part','receivables'=>'Posisi piutang','history'=>'Riwayat servis' };
    $money=fn($value)=>'Rp '.\App\Support\Rupiah::format((string)$value);
    $time=fn($value)=>$value ? \Carbon\CarbonImmutable::parse($value,'UTC')->timezone($f['timezone'])->format('d/m/Y H:i:s') : '—';
    $prefix=$kind === 'history' ? 'service-history' : 'reports.'.$kind;
    $owner=auth()->user()->hasWorkshopRole($activeWorkshop,\App\Enums\WorkshopRole::Owner);
@endphp
<x-app-layout :title="$title" :active-workshop="$activeWorkshop">
    <h1 class="break-words text-3xl font-bold">{{ $title }}</h1>
    @if($owner)<div class="report-actions my-4 flex flex-wrap gap-4">@foreach(['payments'=>'Pembayaran','invoice-values'=>'Nilai invoice','receivables'=>'Piutang'] as $link=>$label)<a class="auth-link min-h-11" href="{{ route('reports.'.$link.'.index') }}">{{ $label }}</a>@endforeach<a class="auth-link" href="{{ route('service-history.index') }}">Riwayat servis</a></div>@endif
    @include('reports._metadata')
    @unless($printing)@include('reports._filters')@endunless
    @if($owner)<div class="report-actions mb-6 flex flex-wrap gap-5"><a class="auth-link min-h-11" href="{{ route($prefix.'.export',$f['params']) }}">Unduh CSV (seluruh hasil)</a><a class="auth-link min-h-11" href="{{ route($prefix.'.print',$f['params'] + ['page'=>$rows->currentPage()]) }}">Cetak A4 / Simpan PDF</a></div>@endif
    @if($kind === 'payments' || $kind === 'invoice-values')
        <p class="mb-4 text-sm leading-relaxed text-slate-600">{{ $kind === 'payments' ? 'Koreksi adalah koreksi pencatatan pembayaran, bukan refund atau uang keluar. Nilai bersih dapat negatif.' : 'Nilai invoice memakai snapshot saat diterbitkan, bukan penerimaan uang atau laba. Pembatalan mengurangi nilai pada tanggal pembatalan; draft tidak dihitung.' }}</p>
        <dl class="mb-6 grid gap-3 sm:grid-cols-3">@foreach(['received'=>$kind === 'payments' ? 'Pembayaran masuk' : 'Nilai invoice terbit','correction'=>$kind === 'payments' ? 'Koreksi pencatatan' : 'Nilai invoice batal','net'=>'Nilai bersih'] as $field=>$label)<div class="min-w-0 rounded-xl bg-white p-5"><dt class="text-sm text-slate-500">{{ $label }}</dt><dd class="mt-2 break-words text-xl font-bold">{{ $money($totals->$field) }}</dd></div>@endforeach</dl>
        @if($kind === 'invoice-values')<dl class="mb-6 grid gap-3 sm:grid-cols-2">@foreach(['service_gross'=>'Jasa terbit','service_correction'=>'Jasa batal','service_net'=>'Nilai invoice jasa bersih','part_gross'=>'Spare part terbit','part_correction'=>'Spare part batal','part_net'=>'Nilai invoice spare part bersih'] as $field=>$label)<div class="rounded-xl bg-white p-4"><dt>{{ $label }}</dt><dd class="break-words font-semibold">{{ $money($totals->$field) }}</dd></div>@endforeach</dl>@endif
        <h2 class="mb-3 text-xl font-semibold">Ringkasan harian{{ $kind === 'payments' ? ' per metode' : '' }}</h2>
        <div class="report-table mb-8 overflow-x-auto rounded-xl bg-white"><table class="w-full text-left text-sm"><thead><tr><th class="p-3">Tanggal / metode</th><th class="p-3">Penambahan</th><th class="p-3">Koreksi</th><th class="p-3">Bersih</th></tr></thead><tbody>@forelse($daily as $day)<tr class="border-t border-slate-100"><td class="p-3">{{ $day->day }} {{ match($day->method) {'cash'=>'Tunai','bank_transfer'=>'Transfer bank','other'=>'Lainnya',default=>''} }}</td><td class="whitespace-nowrap p-3">{{ $money($day->received) }}</td><td class="whitespace-nowrap p-3">{{ $money($day->correction) }}</td><td class="whitespace-nowrap p-3">{{ $money($day->net) }}</td></tr>@empty<tr><td colspan="4" class="p-4">Belum ada catatan pada periode ini.</td></tr>@endforelse</tbody></table></div>
    @elseif($kind === 'receivables')
        <p class="mb-5 text-sm">{{ $totals->count }} invoice belum lunas pada batas waktu pilihan. Total piutang: <strong class="break-words">{{ $money($totals->remaining) }}</strong>. Umur dihitung sejak tanggal terbit pada waktu tersebut.</p>
    @else
        <p class="mb-5 text-sm">{{ $rows->total() }} WO sesuai filter. {{ request('status','final') === 'final' ? 'Menampilkan Selesai / Dibatalkan.' : 'Status mengikuti filter pilihan.' }} Riwayat mengikuti pelanggan pada WO dan identitas kendaraan, meskipun pemilik atau pelat berubah.</p>
    @endif
    <h2 class="mb-3 text-xl font-semibold">Rincian · halaman {{ $rows->currentPage() }} dari {{ $rows->lastPage() }}</h2>
    <div class="space-y-3">
    @forelse($rows as $row)
        <article class="min-w-0 break-words rounded-xl border border-slate-200 bg-white p-5">
        @if($kind === 'history')
            <div class="flex flex-wrap justify-between gap-3"><a class="auth-link" href="{{ route('work-orders.show',$row) }}">{{ $row->number }}</a><strong>{{ $row->status->label() }}</strong></div>
            <p class="mt-2">{{ $row->plate_number_snapshot ?? $row->vehicle?->plate_number }} · {{ $row->customer_name_snapshot ?? $row->customer?->name ?? 'Pelanggan umum' }}</p>
            <p class="mt-2 text-sm">Mekanik: {{ $row->mechanic_name_snapshot ?? 'Belum ditugaskan' }}. Dibuat {{ $time($row->created_at) }} · Selesai {{ $time($row->completed_at) }}.</p>
            <p class="mt-2 whitespace-pre-line text-sm">{{ $row->complaint ?? 'Tanpa keluhan.' }}</p>
            <p class="mt-2 text-xs text-slate-500">Buka WO untuk rincian jasa, pemakaian, retur, dan riwayat perubahan.</p>
        @elseif($kind === 'receivables')
            <a class="auth-link" href="{{ route('reports.receivables.show',['invoice'=>$row->invoice_id]+$f['params']) }}">{{ $row->number }} · Telusuri pada waktu pilihan</a>
            <p class="mt-2">{{ $row->work_order_number }} · {{ $row->plate }} · {{ $row->customer ?? 'Pelanggan umum' }}</p>
            <p class="mt-2 text-sm">Terbit {{ $time($row->issued_at) }} · Umur {{ $row->age_days }} hari.</p>
            <p class="mt-2">Total {{ $money($row->total) }} · Pembayaran valid {{ $money($row->paid) }} · <strong>Sisa {{ $money($row->remaining) }}</strong></p>
        @else
            <div class="flex flex-wrap justify-between gap-3"><a class="auth-link" href="{{ route('reports.receivables.show',['invoice'=>$row->invoice_id]+$f['params']) }}">{{ $row->number }}</a><span class="text-sm">{{ $time($row->occurred_at) }}</span></div>
            <p class="mt-2 font-semibold">{{ match($row->kind) {'payment'=>'Pembayaran masuk','reversal'=>'Koreksi pencatatan','issued'=>'Invoice terbit','void'=>'Invoice dibatalkan'} }} · {{ $money($row->net) }}</p>
            <p class="mt-2 text-sm">{{ $row->plate }} · {{ $row->customer ?? 'Pelanggan umum' }} · {{ match($row->method) {'cash'=>'Tunai','bank_transfer'=>'Transfer bank','other'=>'Lainnya',default=>''} }}</p>
            @if($kind === 'invoice-values')<p class="mt-2 text-sm">Jasa terbit {{ $money($row->service_gross) }} / batal {{ $money($row->service_correction) }} · Part terbit {{ $money($row->part_gross) }} / batal {{ $money($row->part_correction) }}</p>@endif
            <p class="mt-2 text-xs">Pelaku: {{ $row->actor }} @if($row->reference) · Referensi: {{ $row->reference }} @endif</p>
            @if($row->note)<p class="mt-2 whitespace-pre-line text-sm">{{ $row->note }}</p>@endif
        @endif
        </article>
    @empty<p class="rounded-xl bg-white p-6">Tidak ada hasil pada filter ini. Pilih periode atau status lain.</p>@endforelse
    </div>
    <div class="report-actions mt-5">{{ $rows->links() }}</div>
</x-app-layout>
