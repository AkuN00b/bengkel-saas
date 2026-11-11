<?php

namespace App\Queries;

use App\Enums\WorkshopRole;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Workshop;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Reporting
{
    public function payments(Workshop $workshop, array $f): Builder
    {
        $base = DB::table('payments as p')->join('invoices as i', fn ($j) => $j->on('i.id', '=', 'p.invoice_id')->on('i.workshop_id', '=', 'p.workshop_id'))->where('p.workshop_id', $workshop->id);
        $paid = (clone $base)->join('users as u', 'u.id', '=', 'p.created_by')->selectRaw("p.id AS event_id,i.id AS invoice_id,i.number,i.plate_number_snapshot AS plate,i.customer_name_snapshot AS customer,u.name AS actor,'payment'::text AS kind,p.method,p.paid_at AS occurred_at,p.reference,p.note,p.amount::numeric AS received,0::numeric AS correction,p.amount::numeric AS net,0::numeric AS service_gross,0::numeric AS part_gross,0::numeric AS service_correction,0::numeric AS part_correction");
        $reversed = (clone $base)->join('payment_reversals as r', fn ($j) => $j->on('r.payment_id', '=', 'p.id')->on('r.workshop_id', '=', 'p.workshop_id'))->join('users as u', 'u.id', '=', 'r.created_by')
            ->selectRaw("r.id AS event_id,i.id AS invoice_id,i.number,i.plate_number_snapshot AS plate,i.customer_name_snapshot AS customer,u.name AS actor,'reversal'::text AS kind,p.method,r.created_at AS occurred_at,p.reference,r.reason AS note,0::numeric AS received,p.amount::numeric AS correction,-p.amount::numeric AS net,0::numeric AS service_gross,0::numeric AS part_gross,0::numeric AS service_correction,0::numeric AS part_correction");
        $query = $this->period(DB::query()->fromSub($paid->unionAll($reversed), 'flow'), $f);
        if (($method = $f['data']['method'] ?? 'all') !== 'all') {
            $query->where('method', $method);
        }

        return $this->search($query, $f, 'flow');
    }

    public function invoiceValues(Workshop $workshop, array $f): Builder
    {
        $s = "(SELECT COALESCE(SUM(x.subtotal),0) FROM invoice_items x WHERE x.invoice_id=i.id AND x.workshop_id=i.workshop_id AND x.item_type='service')";
        $p = "(SELECT COALESCE(SUM(x.subtotal),0) FROM invoice_items x WHERE x.invoice_id=i.id AND x.workshop_id=i.workshop_id AND x.item_type='spare_part')";
        $base = DB::table('invoices as i')->where('i.workshop_id', $workshop->id)->whereNotNull('i.issued_at');
        $issued = (clone $base)->join('users as u', 'u.id', '=', 'i.issued_by')->selectRaw("i.id AS event_id,i.id AS invoice_id,i.number,i.plate_number_snapshot AS plate,i.customer_name_snapshot AS customer,u.name AS actor,'issued'::text AS kind,''::text AS method,i.issued_at AS occurred_at,NULL::text AS reference,NULL::text AS note,i.total::numeric AS received,0::numeric AS correction,i.total::numeric AS net,{$s} AS service_gross,{$p} AS part_gross,0::numeric AS service_correction,0::numeric AS part_correction");
        $void = (clone $base)->whereNotNull('i.voided_at')->join('users as u', 'u.id', '=', 'i.voided_by')->selectRaw("i.id AS event_id,i.id AS invoice_id,i.number,i.plate_number_snapshot AS plate,i.customer_name_snapshot AS customer,u.name AS actor,'void'::text AS kind,''::text AS method,i.voided_at AS occurred_at,NULL::text AS reference,i.void_reason AS note,0::numeric AS received,i.total::numeric AS correction,-i.total::numeric AS net,0::numeric AS service_gross,0::numeric AS part_gross,{$s} AS service_correction,{$p} AS part_correction");

        return $this->search($this->period(DB::query()->fromSub($issued->unionAll($void), 'flow'), $f), $f, 'flow');
    }

    private function period(Builder $query, array $f): Builder
    {
        return $query->where('occurred_at', '>=', $f['start'])->where('occurred_at', '<', $f['end'])->where('occurred_at', '<=', $f['cutoff']);
    }

    public function totals(Builder $query): object
    {
        $columns = ['received', 'correction', 'net', 'service_gross', 'part_gross', 'service_correction', 'part_correction'];

        return (clone $query)->selectRaw(implode(',', array_map(fn ($name) => "COALESCE(SUM({$name}),0)::text AS {$name}", $columns)))
            ->selectRaw('COALESCE(SUM(service_gross-service_correction),0)::text AS service_net,COALESCE(SUM(part_gross-part_correction),0)::text AS part_net')->first();
    }

    public function daily(Builder $query, string $timezone)
    {
        return (clone $query)->selectRaw('date(timezone(?,occurred_at)) AS day,method,COALESCE(SUM(received),0)::text AS received,COALESCE(SUM(correction),0)::text AS correction,COALESCE(SUM(net),0)::text AS net', [$timezone])
            ->groupBy('day', 'method')->orderBy('day')->orderBy('method')->get();
    }

    public function receivables(Workshop $workshop, array $f, bool $outstanding = true): Builder
    {
        $paid = DB::table('payments as p')->selectRaw('p.invoice_id,SUM(p.amount)::numeric AS paid')->where('p.workshop_id', $workshop->id)->where('p.paid_at', '<=', $f['cutoff'])
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('payment_reversals as r')->whereColumn('r.payment_id', 'p.id')->whereColumn('r.workshop_id', 'p.workshop_id')->where('r.created_at', '<=', $f['cutoff']))->groupBy('p.invoice_id');
        $base = DB::table('invoices as i')->leftJoinSub($paid, 'p', 'p.invoice_id', '=', 'i.id')->where('i.workshop_id', $workshop->id)->where('i.issued_at', '<=', $f['cutoff']);
        $base->selectRaw("i.id AS invoice_id,i.number,i.work_order_id,i.work_order_number,i.plate_number_snapshot AS plate,i.customer_name_snapshot AS customer,i.issued_at,i.voided_at,i.total::text AS total,COALESCE(p.paid,0)::text AS paid,
            CASE WHEN i.voided_at IS NOT NULL AND i.voided_at <= ? THEN 'void' ELSE 'issued' END AS document_state,
            CASE WHEN i.voided_at IS NOT NULL AND i.voided_at <= ? THEN 0::numeric ELSE i.total::numeric-COALESCE(p.paid,0) END AS remaining,
            (date(timezone(?,?::timestamptz))-date(timezone(?,i.issued_at))) AS age_days", [$f['cutoff'], $f['cutoff'], $f['timezone'], $f['cutoff'], $f['timezone']]);
        $query = DB::query()->fromSub($base, 'position');
        if ($outstanding) {
            $query->where('remaining', '>', 0);
        }

        return $this->search($query, $f, 'position');
    }

    public function receivableTotal(Builder $query): object
    {
        return (clone $query)->selectRaw('COUNT(*) AS count,COALESCE(SUM(remaining),0)::text AS remaining')->first();
    }

    public function timeline(Workshop $workshop, int $id, array $f): Builder
    {
        $base = DB::table('invoices')->where('workshop_id', $workshop->id)->where('id', $id);
        $issued = (clone $base)->selectRaw("id AS event_id,'issued'::text AS kind,issued_at AS occurred_at,total::numeric AS amount,NULL::text AS note");
        $void = (clone $base)->whereNotNull('voided_at')->selectRaw("id AS event_id,'void'::text AS kind,voided_at AS occurred_at,total::numeric AS amount,void_reason AS note");
        $payments = DB::table('payments')->where('workshop_id', $workshop->id)->where('invoice_id', $id)->selectRaw("id AS event_id,'payment'::text AS kind,paid_at AS occurred_at,amount::numeric, note");
        $reversals = DB::table('payment_reversals as r')->join('payments as p', fn ($j) => $j->on('r.payment_id', '=', 'p.id')->on('r.workshop_id', '=', 'p.workshop_id'))->where('r.workshop_id', $workshop->id)->where('r.invoice_id', $id)
            ->selectRaw("r.id AS event_id,'reversal'::text AS kind,r.created_at AS occurred_at,p.amount::numeric AS amount,r.reason AS note");

        return DB::query()->fromSub($issued->unionAll($payments)->unionAll($reversals)->unionAll($void), 'timeline')->whereNotNull('occurred_at')->where('occurred_at', '<=', $f['cutoff']);
    }

    public function history(Workshop $workshop, User $user, array $f)
    {
        $query = WorkOrder::where('workshop_id', $workshop->id);
        if ($user->hasWorkshopRole($workshop, WorkshopRole::Mechanic)) {
            $query->where('assigned_mechanic_id', $user->id);
        }
        foreach (['vehicle_id', 'customer_id', 'mechanic_id'] as $name) {
            if (! empty($f['data'][$name])) {
                $query->where($name === 'mechanic_id' ? 'assigned_mechanic_id' : $name, $f['data'][$name]);
            }
        }
        $status = $f['data']['status'] ?? 'final';
        if ($status === 'final') {
            $query->whereIn('status', ['completed', 'cancelled']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }
        $date = ($f['data']['date_basis'] ?? 'created') === 'completed' ? 'completed_at' : 'created_at';
        if (! $f['all']) {
            $query->where($date, '>=', $f['start'])->where($date, '<', $f['end']);
        }
        $query->where($date, '<=', $f['cutoff']);
        if ($search = $f['data']['q'] ?? null) {
            $literal = self::literal($search);
            $plate = self::literal(strtoupper(preg_replace('/\s+/', '', $search)));
            $query->where(fn ($q) => $q->where('number', 'ilike', $literal)->orWhere('customer_name_snapshot', 'ilike', $literal)->orWhereRaw("replace(plate_number_snapshot,' ','') ILIKE ?", [$plate]));
        }

        return $query;
    }

    private function search(Builder $query, array $f, string $alias): Builder
    {
        if ($search = $f['data']['q'] ?? null) {
            $literal = self::literal($search);
            $plate = self::literal(strtoupper(preg_replace('/\s+/', '', $search)));
            $query->where(fn ($q) => $q->where('number', 'ilike', $literal)->orWhere('customer', 'ilike', $literal)->orWhereRaw("replace(plate,' ','') ILIKE ?", [$plate])
                ->orWhereExists(fn ($wo) => $wo->selectRaw('1')->from('invoices as match_invoice')->whereColumn('match_invoice.id', $alias.'.invoice_id')->where('match_invoice.work_order_number', 'ilike', $literal)));
        }

        return $query;
    }

    public static function literal(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }
}
