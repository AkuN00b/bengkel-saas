<?php

namespace App\Http\Controllers;

use App\Actions\Billing;
use App\Actions\InvoiceSource;
use App\Http\Requests\InvoiceRequest;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Support\PlateNumber;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    private function id(string $id): int
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);

        return (int) $id;
    }

    private function find(InvoiceRequest $request, string $id): Invoice
    {
        $invoice = Invoice::where('workshop_id', $request->attributes->get('activeWorkshop')->id)->whereKey($this->id($id))->firstOrFail();
        Gate::authorize('view', [$invoice, $request->attributes->get('activeWorkshop')]);

        return $invoice;
    }

    public function index(InvoiceRequest $request)
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $query = Invoice::where('workshop_id', $workshop->id)->withSum(['payments as paid_amount' => fn ($q) => $q->whereDoesntHave('reversal')], 'amount');
        if (($status = $request->validated('status')) && $status !== 'all') {
            $query->where('status', $status);
        }
        if (($state = $request->validated('payment')) && $state !== 'all') {
            $paid = '(SELECT COALESCE(SUM(p.amount),0) FROM payments p WHERE p.invoice_id = invoices.id AND p.workshop_id = invoices.workshop_id AND NOT EXISTS (SELECT 1 FROM payment_reversals r WHERE r.payment_id = p.id AND r.workshop_id = p.workshop_id))';
            $query->where('status', 'issued');
            if ($state === 'paid') {
                $query->whereRaw($paid.' = invoices.total');
            } elseif ($state === 'unpaid') {
                $query->where('total', '>', 0)->whereRaw($paid.' = 0');
            } else {
                $query->whereRaw($paid.' > 0 AND '.$paid.' < invoices.total');
            }
        }
        if ($search = $request->validated('q')) {
            $literal = $this->literal($search);
            $plate = $this->literal(PlateNumber::normalize($search));
            $query->where(fn ($q) => $q->where('number', 'ilike', $literal)->orWhere('work_order_number', 'ilike', $literal)
                ->orWhere('customer_name_snapshot', 'ilike', $literal)->orWhereRaw("replace(plate_number_snapshot,' ','') ILIKE ?", [$plate]));
        }

        return view('invoices.index', ['activeWorkshop' => $workshop, 'invoices' => $query->orderByDesc('id')->paginate(20)->withQueryString()]);
    }

    private function literal(string $value): string
    {
        return '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value).'%';
    }

    public function create(InvoiceRequest $request)
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $order = null;
        if ($id = $request->validated('work_order_id')) {
            $order = WorkOrder::where('workshop_id', $workshop->id)->whereKey($id)->firstOrFail();
            $existing = Invoice::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->whereIn('status', ['draft', 'issued'])->first();
            if ($existing) {
                return to_route('invoices.show', $existing);
            }
            abort_unless(in_array($order->status->value, ['open', 'in_progress', 'completed'], true), 409, 'WO Draft/Dibatalkan tidak dapat dibuatkan invoice.');
        }
        $query = WorkOrder::where('workshop_id', $workshop->id)->whereIn('status', ['open', 'in_progress', 'completed'])->whereNotExists(function ($q): void {
            $q->selectRaw('1')->from('invoices')->whereColumn('invoices.work_order_id', 'work_orders.id')->whereColumn('invoices.workshop_id', 'work_orders.workshop_id')->whereIn('invoices.status', ['draft', 'issued']);
        });
        if ($search = $request->validated('q')) {
            $literal = $this->literal($search);
            $query->where(fn ($q) => $q->where('number', 'ilike', $literal)->orWhere('plate_number_snapshot', 'ilike', $literal));
        }

        return view('invoices.create', ['activeWorkshop' => $workshop, 'order' => $order, 'orders' => $query->orderByDesc('id')->simplePaginate(20)->withQueryString()]);
    }

    public function store(InvoiceRequest $request, Billing $action)
    {
        $invoice = $action->create($request->attributes->get('activeWorkshop'), $request->user(), (int) $request->validated('work_order_id'), $request->validated());

        return to_route('invoices.show', $invoice)->with('status', 'Draft invoice berhasil dibuat.');
    }

    public function show(InvoiceRequest $request, string $invoice, InvoiceSource $source)
    {
        $record = $this->find($request, $invoice)->load('order');
        $workshop = $request->attributes->get('activeWorkshop');
        $stale = $record->status === 'draft' && ! hash_equals($record->source_fingerprint, $source->capture($workshop, $record->order)['fingerprint']);

        return view('invoices.show', ['activeWorkshop' => $workshop, 'invoice' => $record, 'stale' => $stale, 'paid' => $record->paidAmount(),
            'items' => $record->items()->orderBy('id')->paginate(10, ['*'], 'items_page')->withQueryString(),
            'payments' => $record->payments()->with(['actor:id,name', 'reversal.actor:id,name'])->orderByDesc('id')->paginate(10, ['*'], 'payments_page')->withQueryString(),
            'events' => $record->events()->with('actor:id,name')->orderByDesc('id')->paginate(10, ['*'], 'events_page')->withQueryString()]);
    }

    public function print(InvoiceRequest $request, string $invoice)
    {
        $record = $this->find($request, $invoice);

        return response()->view('invoices.print', ['invoice' => $record, 'activeWorkshop' => $request->attributes->get('activeWorkshop'),
            'items' => $record->items()->orderBy('id')->limit(1000)->get(), 'payments' => $record->payments()->with('reversal')->orderByDesc('id')->limit(100)->get(),
            'paymentCount' => $record->payments()->count(), 'paid' => $record->paidAmount()])->header('Cache-Control', 'no-store, private');
    }

    public function update(InvoiceRequest $request, string $invoice, Billing $action)
    {
        return $this->change($request, $invoice, $action, 'notes');
    }

    public function refresh(InvoiceRequest $request, string $invoice, Billing $action)
    {
        return $this->change($request, $invoice, $action, 'refresh');
    }

    public function issue(InvoiceRequest $request, string $invoice, Billing $action)
    {
        return $this->change($request, $invoice, $action, 'issue');
    }

    public function void(InvoiceRequest $request, string $invoice, Billing $action)
    {
        return $this->change($request, $invoice, $action, 'void');
    }

    public function pay(InvoiceRequest $request, string $invoice, Billing $action)
    {
        return $this->change($request, $invoice, $action, 'pay');
    }

    public function price(InvoiceRequest $request, string $invoice, string $item, Billing $action)
    {
        return $this->change($request, $invoice, $action, 'price', $this->id($item));
    }

    public function reverse(InvoiceRequest $request, string $invoice, string $payment, Billing $action)
    {
        return $this->change($request, $invoice, $action, 'reverse', $this->id($payment));
    }

    private function change(InvoiceRequest $request, string $id, Billing $action, string $operation, ?int $child = null)
    {
        $invoice = $this->find($request, $id);
        $action->mutate($request->attributes->get('activeWorkshop'), $request->user(), $invoice->id, $request->validated(), $operation, $child);

        return to_route('invoices.show', $invoice)->with('status', 'Transaksi invoice berhasil dicatat.');
    }
}
