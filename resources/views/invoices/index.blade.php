<x-app-layout title="Invoice" :active-workshop="$activeWorkshop">
    <div class="flex flex-wrap items-center justify-between gap-4"><h1 class="text-3xl font-bold">Invoice</h1><a href="{{ route('invoices.create') }}" class="auth-button w-auto">Buat draft invoice</a></div>
    <p class="mt-3 text-sm text-slate-600">Invoice diterbitkan setelah WO Selesai. Pembayaran dicatat terpisah dari status pekerjaan.</p>
    <form method="GET" class="my-6 flex flex-wrap items-end gap-3">
        <label class="min-w-0 flex-1"><span class="text-sm">Nomor invoice / WO, pelat, atau pelanggan</span><input name="q" type="search" maxlength="100" value="{{ request('q') }}" class="auth-input mt-2"></label>
        <label><span class="text-sm">Dokumen</span><select name="status" class="auth-input mt-2">@foreach(['all'=>'Semua','draft'=>'Draft','issued'=>'Terbit','void'=>'Dibatalkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status','all') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label><span class="text-sm">Pembayaran</span><select name="payment" class="auth-input mt-2">@foreach(['all'=>'Semua','unpaid'=>'Belum dibayar','partial'=>'Dibayar sebagian','paid'=>'Lunas'] as $value=>$label)<option value="{{ $value }}" @selected(request('payment','all') === $value)>{{ $label }}</option>@endforeach</select></label>
        <button class="auth-button w-auto">Cari</button>
    </form>
    <div class="space-y-4">@forelse($invoices as $invoice)<a href="{{ route('invoices.show',$invoice) }}" class="block rounded-2xl border border-slate-200 bg-white p-5 hover:border-emerald-500">
        <div class="flex flex-wrap justify-between gap-3"><h2 class="font-semibold text-emerald-800">{{ $invoice->label() }}</h2><span class="text-sm">{{ $invoice->statusLabel() }} · {{ $invoice->paymentLabel((int)$invoice->paid_amount) }}</span></div>
        <p class="mt-2 break-words text-sm">{{ $invoice->work_order_number }} · {{ $invoice->plate_number_snapshot }} · {{ $invoice->customer_name_snapshot ?? 'Pelanggan umum' }}</p><p class="mt-3 font-semibold">Rp {{ \App\Support\Rupiah::format($invoice->total) }}</p>
    </a>@empty<p class="rounded-xl bg-white p-6">Tidak ada invoice yang cocok.</p>@endforelse</div><div class="mt-5">{{ $invoices->links() }}</div>
</x-app-layout>
