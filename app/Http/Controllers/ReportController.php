<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Models\Invoice;
use App\Queries\Reporting;
use App\Support\ReportSnapshot;
use Carbon\CarbonImmutable;

class ReportController extends Controller
{
    public function index(ReportRequest $request, Reporting $queries, ReportSnapshot $snapshot)
    {
        return $snapshot->run(function () use ($request, $queries) {
            $data = $this->data($request, $queries);

            return response()->view('reports.index', $data)->header('Cache-Control', 'no-store, private');
        });
    }

    private function data(ReportRequest $request, Reporting $queries): array
    {
        abort_unless($request->authorize(), 403);
        $f = $request->filters();
        $workshop = $request->attributes->get('activeWorkshop');
        $kind = $request->route('report') ?? ($request->routeIs('service-history.*') ? 'history' : 'receivables');
        $query = match ($kind) {
            'payments' => $queries->payments($workshop, $f),
            'invoice-values' => $queries->invoiceValues($workshop, $f),
            'receivables' => $queries->receivables($workshop, $f),
            'history' => $queries->history($workshop, $request->user(), $f),
        };
        $totals = match ($kind) {
            'payments', 'invoice-values' => $queries->totals($query),
            'receivables' => $queries->receivableTotal($query),
            default => null,
        };
        $daily = in_array($kind, ['payments', 'invoice-values'], true) ? $queries->daily($query, $f['timezone']) : collect();
        if ($kind === 'history') {
            $query->with(['vehicle:id,plate_number', 'customer:id,name']);
        }
        $ordered = match ($kind) {
            'history' => $query->orderByDesc('id'),
            'receivables' => $query->orderBy('issued_at')->orderBy('invoice_id'),
            default => $query->orderByDesc('occurred_at')->orderBy('kind')->orderByDesc('event_id'),
        };
        // CSV iterates this ordered query inside its own read snapshot, without loading all records.
        $rows = $request->routeIs('*.export') ? null : $ordered->paginate(20)->appends($f['params']);

        return ['activeWorkshop' => $workshop, 'f' => $f, 'kind' => $kind, 'totals' => $totals, 'daily' => $daily, 'rows' => $rows, 'query' => $ordered,
            'printing' => $request->routeIs('*.print')];
    }

    public function show(ReportRequest $request, string $invoice, Reporting $queries, ReportSnapshot $snapshot)
    {
        return $snapshot->run(function () use ($request, $invoice, $queries) {
            abort_unless($request->authorize(), 403);
            abort_if(filter_var($invoice, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);
            $workshop = $request->attributes->get('activeWorkshop');
            $f = $request->filters();
            $record = Invoice::where('workshop_id', $workshop->id)->whereKey($invoice)->whereNotNull('issued_at')->firstOrFail();
            unset($f['data']['q']);
            $position = $queries->receivables($workshop, $f, false)->where('invoice_id', $record->id)->first();
            $events = $position ? $queries->timeline($workshop, $record->id, $f)->orderBy('occurred_at')->orderBy('kind')->orderBy('event_id')->paginate(20)->appends($f['params']) : null;

            return response()->view('reports.trace', ['activeWorkshop' => $workshop, 'f' => $f, 'position' => $position, 'events' => $events, 'invoiceId' => $record->id,
                'printing' => $request->routeIs('*.print')])->header('Cache-Control', 'no-store, private');
        });
    }

    public function export(ReportRequest $request, Reporting $queries, ReportSnapshot $snapshot)
    {
        // Authorization/validation must fail before response headers are sent.
        $request->filters();

        return response()->streamDownload(function () use ($request, $queries, $snapshot): void {
            $snapshot->run(function () use ($request, $queries): void {
                $d = $this->data($request, $queries);
                $f = $d['f'];
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                $write = fn (array $values) => fputcsv($out, $values, ',', '"', '');
                $write(['BengkelOS', self::text($d['activeWorkshop']->name), match ($d['kind']) {
                    'payments' => 'Pembayaran dan koreksi','invoice-values' => 'Nilai invoice','receivables' => 'Posisi piutang','history' => 'Riwayat servis'
                }]);
                $write(['Periode', $f['all'] ? 'Semua waktu' : $f['from'].' - '.$f['to'], 'Timezone', $f['timezone']]);
                $write(['Batas waktu', $f['localCutoff']->format('Y-m-d H:i:s'), 'Dibuat', $f['generated']->timezone($f['timezone'])->format('Y-m-d H:i:s')]);
                if ($d['totals']) {
                    $labels = ['count' => 'Jumlah invoice belum lunas', 'remaining' => 'Total sisa tagihan', 'received' => $d['kind'] === 'payments' ? 'Pembayaran masuk' : 'Nilai invoice terbit', 'correction' => $d['kind'] === 'payments' ? 'Koreksi pencatatan' : 'Nilai invoice batal', 'net' => 'Nilai bersih', 'service_gross' => 'Jasa terbit', 'part_gross' => 'Spare part terbit', 'service_correction' => 'Jasa batal', 'part_correction' => 'Spare part batal', 'service_net' => 'Jasa bersih', 'part_net' => 'Spare part bersih'];
                    foreach ((array) $d['totals'] as $key => $value) {
                        if ($d['kind'] !== 'payments' || in_array($key, ['received', 'correction', 'net'], true)) {
                            $write([$labels[$key], (string) $value]);
                        }
                    }
                }
                if ($d['daily']->isNotEmpty()) {
                    $write(['Ringkasan harian', 'Metode', 'Penambahan', 'Koreksi', 'Bersih']);
                    foreach ($d['daily'] as $day) {
                        $write([$day->day, $day->method, (string) $day->received, (string) $day->correction, (string) $day->net]);
                    }
                }
                $kind = $d['kind'];
                $headers = match ($kind) {
                    'history' => ['WO', 'Pelat penerimaan', 'Pelanggan WO', 'Mekanik penugasan', 'Status', 'Dibuat', 'Selesai', 'Keluhan'],
                    'receivables' => ['Invoice', 'WO', 'Pelat', 'Pelanggan', 'Terbit', 'Total', 'Pembayaran valid', 'Sisa', 'Umur hari'],
                    default => ['Waktu', 'Jenis', 'Invoice', 'Pelat', 'Pelanggan', 'Metode', 'Penambahan', 'Koreksi', 'Bersih', 'Jasa terbit', 'Part terbit', 'Jasa batal', 'Part batal', 'Pelaku', 'Referensi', 'Catatan'],
                };
                $write($headers);
                foreach ($d['query']->lazy(200) as $r) {
                    $time = fn ($v) => $v ? CarbonImmutable::parse($v, 'UTC')->timezone($f['timezone'])->format('Y-m-d H:i:s') : '';
                    $values = match ($kind) {
                        'history' => [self::text($r->number), self::text($r->plate_number_snapshot ?? $r->vehicle?->plate_number), self::text($r->customer_name_snapshot ?? $r->customer?->name), self::text($r->mechanic_name_snapshot), $r->status->label(), $time($r->created_at), $time($r->completed_at), self::text($r->complaint)],
                        'receivables' => [self::text($r->number), self::text($r->work_order_number), self::text($r->plate), self::text($r->customer), $time($r->issued_at), (string) $r->total, (string) $r->paid, (string) $r->remaining, (string) $r->age_days],
                        default => [$time($r->occurred_at), $r->kind, self::text($r->number), self::text($r->plate), self::text($r->customer), $r->method, (string) $r->received, (string) $r->correction, (string) $r->net, (string) $r->service_gross, (string) $r->part_gross, (string) $r->service_correction, (string) $r->part_correction, self::text($r->actor), self::text($r->reference), self::text($r->note)],
                    };
                    $write($values);
                }
                fclose($out);
            });
        }, 'bengkelos-report.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store, private']);
    }

    public static function text(?string $value): string
    {
        $value ??= '';

        return preg_match('/^[\s\x00-\x1f]*[=+@\-\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
