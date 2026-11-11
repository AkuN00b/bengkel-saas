<?php

namespace App\Queries;

use App\Enums\WorkshopRole;
use App\Models\SparePart;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Workshop;
use Carbon\CarbonImmutable;

class Dashboard
{
    public function read(Workshop $workshop, User $user, string $timezone): array
    {
        $now = CarbonImmutable::now('UTC')->startOfSecond();
        $local = $now->timezone($timezone);
        $f = ['start' => $local->startOfDay()->utc(), 'end' => $local->addDay()->startOfDay()->utc(), 'cutoff' => $now, 'timezone' => $timezone, 'data' => []];
        $jobs = WorkOrder::where('workshop_id', $workshop->id);
        $mechanic = $user->hasWorkshopRole($workshop, WorkshopRole::Mechanic);
        $owner = $user->hasWorkshopRole($workshop, WorkshopRole::Owner);
        if ($mechanic) {
            $jobs->where('assigned_mechanic_id', $user->id);
        }
        $counts = (clone $jobs)->whereIn('status', ['draft', 'open', 'in_progress'])->selectRaw('status,COUNT(*) AS count')->groupBy('status')->pluck('count', 'status');
        $data = ['owner' => $owner, 'mechanic' => $mechanic, 'generated' => $local, 'counts' => $counts,
            'completed' => (clone $jobs)->where('completed_at', '>=', $f['start'])->where('completed_at', '<', $f['end'])->where('completed_at', '<=', $now)->count(),
            'recent' => (clone $jobs)->whereIn('status', ['draft', 'open', 'in_progress'])->with('vehicle:id,plate_number')->orderByDesc('id')->limit(5)->get(),
            'today' => $local->toDateString()];
        if (! $mechanic) {
            $low = SparePart::where('workshop_id', $workshop->id)->where('is_active', true)->whereNotNull('minimum_stock')->whereColumn('current_stock', '<=', 'minimum_stock');
            $report = app(Reporting::class);
            $outstanding = $report->receivables($workshop, $f);
            $data += ['lowCount' => (clone $low)->count(), 'low' => $low->orderBy('current_stock')->orderBy('id')->limit(5)->get(),
                'outstandingCount' => (clone $outstanding)->count()];
            if ($owner) {
                $data += ['payments' => $report->totals($report->payments($workshop, $f)),
                    'invoiceValues' => $report->totals($report->invoiceValues($workshop, $f)),
                    'receivables' => $report->receivableTotal($outstanding),
                    'oldest' => $outstanding->orderBy('issued_at')->orderBy('invoice_id')->limit(5)->get()];
                $f['start'] = $local->subDays(6)->startOfDay()->utc();
                // Sum in PostgreSQL, never add numeric aggregates in PHP.
                $daily = $report->payments($workshop, $f)->selectRaw('date(timezone(?,occurred_at)) AS day,SUM(received)::text AS received,SUM(correction)::text AS correction,SUM(net)::text AS net', [$timezone])->groupBy('day')->get()->keyBy('day');
                $data['trend'] = collect(range(6, 0))->map(fn ($offset) => $daily->get($local->subDays($offset)->toDateString(), (object) ['day' => $local->subDays($offset)->toDateString(), 'received' => '0', 'correction' => '0', 'net' => '0']));
            }
        }

        return $data;
    }
}
