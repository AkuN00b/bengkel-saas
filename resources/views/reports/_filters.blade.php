<form method="GET" class="report-actions my-6 grid min-w-0 gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:grid-cols-2 lg:grid-cols-3">
    <input type="hidden" name="workshop_context" value="{{ $activeWorkshop->id }}">
    <label class="min-w-0 text-sm">Nomor WO/invoice, pelat, pelanggan<input name="q" type="search" maxlength="100" value="{{ request('q') }}" class="auth-input"></label>
    @if($kind !== 'receivables')
        <label class="min-w-0 text-sm">Periode<select name="preset" class="auth-input">@foreach(['today'=>'Hari ini','week'=>'Tujuh hari terakhir','month'=>'Bulan berjalan','custom'=>'Rentang khusus'] + ($kind === 'history' && (request('vehicle_id') || request('customer_id')) ? ['all'=>'Semua waktu'] : []) as $value=>$label)<option value="{{ $value }}" @selected(request('preset','month') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="min-w-0 text-sm">Dari (rentang khusus)<input class="auth-input" name="from" type="date" value="{{ $f['from'] }}"></label>
        <label class="min-w-0 text-sm">Sampai (maksimal 366 hari)<input class="auth-input" name="to" type="date" value="{{ $f['to'] }}"></label>
    @endif
    @if($kind === 'receivables')
        <label class="min-w-0 text-sm">Posisi piutang<select name="mode" class="auth-input">@foreach(['now'=>'Sekarang','end_day'=>'Akhir hari','at'=>'Tanggal dan waktu'] as $value=>$label)<option value="{{ $value }}" @selected(request('mode','now') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="min-w-0 text-sm">Tanggal akhir hari<input class="auth-input" name="cutoff_date" type="date" value="{{ request('cutoff_date') }}"></label>
        <label class="min-w-0 text-sm">Waktu sampai detik ({{ $f['timezone'] }})<input class="auth-input" name="as_of" type="datetime-local" step="1" value="{{ $f['localCutoff']->format('Y-m-d\TH:i:s') }}"></label>
    @endif
    @if($kind === 'payments')
        <label class="min-w-0 text-sm">Metode<select class="auth-input" name="method">@foreach(['all'=>'Semua','cash'=>'Tunai','bank_transfer'=>'Transfer bank','other'=>'Lainnya'] as $value=>$label)<option value="{{ $value }}" @selected(request('method','all') === $value)>{{ $label }}</option>@endforeach</select></label>
    @endif
    @if($kind === 'history')
        <label class="min-w-0 text-sm">Status<select class="auth-input" name="status">@foreach(['final'=>'Selesai / Dibatalkan','all'=>'Semua status','draft'=>'Draft','open'=>'Antre','in_progress'=>'Dikerjakan','completed'=>'Selesai','cancelled'=>'Dibatalkan'] as $value=>$label)<option value="{{ $value }}" @selected(request('status','final') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="min-w-0 text-sm">Dasar tanggal<select class="auth-input" name="date_basis"><option value="created" @selected(request('date_basis','created') === 'created')>Dibuat</option><option value="completed" @selected(request('date_basis') === 'completed')>Selesai</option></select></label>
        @foreach(['vehicle_id'=>'ID kendaraan','customer_id'=>'ID pelanggan pada WO','mechanic_id'=>'ID akun mekanik'] as $field=>$label)<label class="min-w-0 text-sm">{{ $label }}<input type="number" min="1" name="{{ $field }}" value="{{ request($field) }}" class="auth-input"></label>@endforeach
        <p class="text-xs text-slate-500 sm:col-span-2">Riwayat per kendaraan/pelanggan juga dapat dibuka langsung dari halaman detailnya. Data Draft belum mempunyai snapshot penerimaan.</p>
    @endif
    <div class="flex flex-wrap items-end gap-3"><button class="auth-button w-auto">Tampilkan</button><a class="auth-link inline-flex min-h-11 items-center" href="{{ route($kind === 'history' ? 'service-history.index' : 'reports.'.$kind.'.index') }}">Perbarui / reset</a></div>
</form>
@if($errors->any())<ul role="alert" class="my-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
