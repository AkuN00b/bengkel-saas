@php($time = fn($value) => $value ? \Carbon\CarbonImmutable::parse($value,'UTC')->timezone($f['timezone'])->format('d/m/Y H:i:s') : '—')
<div class="my-4 rounded-xl border border-slate-200 bg-white p-4 text-sm leading-relaxed">
    <p><strong>{{ $activeWorkshop->name }}</strong> · {{ $f['timezone'] }}</p>
    <p>Periode: {{ $f['all'] ? 'Semua waktu' : $f['from'].' sampai '.$f['to'] }}. Batas waktu: <strong>{{ $f['localCutoff']->format('d/m/Y H:i:s') }}</strong>.</p>
    <p>Dibuat: {{ $time($f['generated']) }}. Data dibaca dari satu snapshot; perbarui halaman untuk data terbaru.</p>
</div>
@if($printing)
    <p class="my-4 text-sm">Cetak mencakup ringkasan seluruh hasil filter dan rincian halaman yang sedang dibuka. Gunakan CSV untuk seluruh rincian.</p>
    <button type="button" class="report-actions auth-button mb-5 w-auto" onclick="window.print()">Cetak A4 / Simpan PDF</button>
@endif
<style>
    @page { size: A4; margin: 14mm; }
    @media print {
        body { background: white !important; font-size: 10pt; }
        body > aside, body > dialog, header, .report-actions, nav[role="navigation"], a[href="#main-content"] { display: none !important; }
        body > div { padding-left: 0 !important; }
        main { max-width: none !important; padding: 0 !important; }
        .report-table { overflow: visible !important; }
        table { width: 100%; font-size: 9pt; }
        tr { break-inside: avoid; }
        a { color: inherit; text-decoration: none; }
    }
</style>
