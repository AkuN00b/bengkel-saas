<x-app-layout :title="$order->number" :active-workshop="$activeWorkshop">
    <a href="{{ route('work-orders.index') }}" class="auth-link inline-flex min-h-11 items-center">Kembali ke perintah kerja</a>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-4"><h1 class="text-3xl font-bold">{{ $order->number }}</h1><span class="rounded-full bg-emerald-100 px-4 py-2 font-semibold">{{ $order->status->label() }}</span></div>
    <p class="mt-3 text-sm text-slate-600">Selesai pekerjaan belum berarti lunas.</p>
    @if ($errors->any()) <div role="alert" class="my-4 rounded-xl bg-red-50 p-4 text-red-800">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div> @endif
    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
            <h2 class="text-lg font-bold">{{ $order->opened_at ? 'Data penerimaan' : 'Data draft' }}</h2>
            <p class="break-words text-2xl font-semibold">{{ $order->plate_number_snapshot ?? $order->vehicle->plate_number }}</p>
            <p class="break-words">{{ $order->opened_at ? ($order->customer_name_snapshot ?? 'Tanpa pelanggan') : ($order->customer?->name ?? 'Tanpa pelanggan') }}<br><span class="text-sm text-slate-600">{{ $order->opened_at ? $order->customer_phone_snapshot : $order->customer?->phone }}</span></p>
            <p class="break-words text-sm">Mekanik: <strong>{{ $order->mechanic_name_snapshot ?? 'Belum ditugaskan' }}</strong></p>
            @foreach (['complaint' => 'Keluhan', 'notes' => 'Catatan', 'cancellation_reason' => 'Alasan pembatalan'] as $field => $label)
                @if ($order->$field)<div><h3 class="text-sm text-slate-500">{{ $label }}</h3><p class="mt-1 whitespace-pre-line break-words">{{ $order->$field }}</p></div>@endif
            @endforeach
            <dl class="space-y-2 text-sm">
                @foreach (['created_at' => 'Dibuat', 'opened_at' => 'Diterima', 'started_at' => 'Mulai', 'completed_at' => 'Selesai', 'cancelled_at' => 'Dibatalkan'] as $field => $label)
                    @if ($order->$field)<div class="flex flex-wrap justify-between gap-2"><dt class="text-slate-500">{{ $label }}</dt><dd>{{ $order->$field->copy()->timezone($activeWorkshop->timezone)->format('d/m/Y H:i') }}</dd></div>@endif
                @endforeach
            </dl><p class="text-xs text-slate-500">Zona waktu: {{ $activeWorkshop->timezone }}</p>
        </section>
        <section class="space-y-5 rounded-2xl border border-slate-200 bg-white p-5 sm:p-7">
            <h2 class="text-lg font-bold">Tindakan</h2>
            @can('update', [$order, $activeWorkshop])
                <a href="{{ route('work-orders.edit', $order) }}" class="auth-link inline-flex min-h-11 items-center">Ubah detail WO</a>
                @if ($order->status === \App\Enums\WorkOrderStatus::Draft)<form method="POST" action="{{ route('work-orders.transition', $order) }}">@include('work-orders._context')<button name="action" value="open" class="auth-button w-auto">Terima pekerjaan</button></form>@endif
            @endcan
            @can('process', [$order, $activeWorkshop])
                <form method="POST" action="{{ route('work-orders.transition', $order) }}">@include('work-orders._context')
                    <button name="action" value="{{ $order->status === \App\Enums\WorkOrderStatus::Open ? 'start' : 'complete' }}" class="auth-button w-auto">{{ $order->status === \App\Enums\WorkOrderStatus::Open ? 'Mulai pekerjaan' : 'Selesaikan pekerjaan' }}</button>
                </form>
            @endcan
            @can('assign', [$order, $activeWorkshop])
                <details><summary class="auth-link min-h-11 cursor-pointer py-3">Tugaskan / ganti mekanik</summary>
                    <form method="GET" class="my-3 flex flex-wrap gap-2"><label class="min-w-0 flex-1"><span class="text-sm">Cari nama mekanik</span><input name="q" value="{{ request('q') }}" maxlength="100" class="auth-input mt-1" type="search"></label><button class="auth-link min-h-11 self-end px-2">Cari</button></form>
                    @foreach ($mechanics as $membership)<form method="POST" action="{{ route('work-orders.assign', $order) }}" class="my-2">@method('PUT') @include('work-orders._context')<input type="hidden" name="assigned_mechanic_id" value="{{ $membership->user_id }}"><button class="min-h-11 w-full rounded-lg border border-slate-200 p-3 text-left text-sm">Tugaskan {{ $membership->user->name }}</button></form>@endforeach
                    @if ($mechanics->isEmpty())<p class="my-3 text-sm text-slate-600">Tidak ada mekanik aktif yang cocok dan sudah membuat sandi.</p>@endif
                    {{ $mechanics->links() }}
                    @if ($order->status !== \App\Enums\WorkOrderStatus::InProgress && $order->assigned_mechanic_id)<form method="POST" action="{{ route('work-orders.assign', $order) }}" class="mt-2">@method('PUT') @include('work-orders._context')<input type="hidden" name="assigned_mechanic_id" value=""><button class="auth-link min-h-11 text-sm">Lepas penugasan</button></form>@endif
                </details>
            @endcan
            @can('cancel', [$order, $activeWorkshop])
                <details><summary class="min-h-11 cursor-pointer py-3 font-medium text-red-700">Batalkan WO</summary><form method="POST" action="{{ route('work-orders.transition', $order) }}" class="mt-3 space-y-3">@include('work-orders._context')
                    <label for="cancellation_reason" class="text-sm">Alasan pembatalan (wajib)</label><textarea id="cancellation_reason" name="cancellation_reason" rows="3" maxlength="1000" required class="auth-input">{{ is_string(old('cancellation_reason')) ? old('cancellation_reason') : '' }}</textarea><button name="action" value="cancel" class="min-h-11 rounded-lg bg-red-700 px-4 py-2 text-sm font-semibold text-white">Batalkan pekerjaan</button>
                </form></details>
            @endcan
            @if (!$order->status->isActive())<p class="text-sm text-slate-600">WO ini sudah final. Gunakan WO baru untuk pekerjaan lanjutan.</p>@endif
        </section>
    </div>
    @include('work-orders._items')
    @if($invoices !== null)<section class="mt-7"><h2 class="text-xl font-bold">Invoice WO</h2>@foreach($invoices as $document)<a class="auth-link mt-2 flex min-h-11 items-center" href="{{ route('invoices.show',$document) }}">{{ $document->label() }} · {{ $document->statusLabel() }}</a>@endforeach<div class="mt-3">{{ $invoices->links() }}</div>@if(in_array($order->status->value,['open','in_progress','completed'],true))<a class="auth-link mt-3 inline-flex min-h-11 items-center" href="{{ route('invoices.create',['work_order_id'=>$order->id]) }}">Buka / buat invoice WO</a>@endif</section>@endif
    <section class="mt-8"><h2 class="text-xl font-bold">Riwayat pekerjaan</h2><ol class="mt-4 space-y-3">
        @foreach ($events as $event)<li class="rounded-xl border border-slate-200 bg-white p-4 text-sm">
            <p class="font-semibold">{{ match($event->type) { 'created' => 'WO dibuat', 'status_changed' => 'Status berubah', 'mechanic_assigned' => 'Penugasan mekanik', 'details_changed' => 'Detail diperbarui', 'service_changed'=>'Jasa diperbarui', 'part_changed'=>'Rencana/harga barang diperbarui', 'work_order_usage'=>'Pemakaian barang dicatat', 'work_order_return'=>'Retur barang dicatat', 'plans_cancelled'=>'Sisa rencana barang dibatalkan', default => 'Perubahan WO' } }}</p>
            @if (isset($event->details['name']))<p class="mt-1 break-words">{{ $event->details['name'] }}</p>@endif
            @if (isset($event->details['before']['unit_price'], $event->details['after']['unit_price']) && $event->details['before']['unit_price'] !== $event->details['after']['unit_price'])<p class="mt-1 text-sm">Harga Rp {{ \App\Support\Rupiah::format($event->details['before']['unit_price']) }} → Rp {{ \App\Support\Rupiah::format($event->details['after']['unit_price']) }}</p>@endif
            @if ($event->type === 'status_changed')<p class="mt-1">{{ \App\Enums\WorkOrderStatus::from($event->details['from'])->label() }} → {{ \App\Enums\WorkOrderStatus::from($event->details['to'])->label() }}</p>@endif
            @if ($event->type === 'mechanic_assigned')<p class="mt-1 break-words">{{ $event->details['name'] ?? 'Penugasan dilepas' }}</p>@endif
            <p class="mt-2 break-words text-slate-500">{{ $event->actor->name }} · {{ $event->created_at->timezone($activeWorkshop->timezone)->format('d/m/Y H:i') }}</p>
        </li>@endforeach
    </ol><div class="mt-4">{{ $events->links() }}</div></section>
</x-app-layout>
