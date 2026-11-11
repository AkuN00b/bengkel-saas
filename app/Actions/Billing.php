<?php

namespace App\Actions;

use App\Enums\WorkOrderStatus;
use App\Models\Invoice;
use App\Models\InvoiceEvent;
use App\Models\Payment;
use App\Models\PaymentReversal;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\Workshop;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Billing
{
    public function __construct(private InvoiceSource $source) {}

    private function fingerprint(User $actor, string $operation, int $target, array $data, ?int $child = null): string
    {
        unset($data['operation_key']);
        foreach (['workshop_context', 'work_order_id', 'version', 'unit_price', 'amount'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = (int) $data[$field];
            }
        }
        ksort($data);

        return hash('sha256', json_encode([$actor->id, $operation, $target, $child, $data], JSON_THROW_ON_ERROR));
    }

    private function replay(Workshop $workshop, User $actor, array $data, string $fingerprint): ?Invoice
    {
        $event = InvoiceEvent::where('workshop_id', $workshop->id)->where('operation_key', $data['operation_key'])->first();
        if (! $event) {
            return null;
        }
        abort_unless(hash_equals($event->request_fingerprint, $fingerprint), 409, 'Kunci pengiriman telah dipakai untuk transaksi berbeda. Muat ulang form.');
        $invoice = Invoice::where('workshop_id', $workshop->id)->whereKey($event->invoice_id)->firstOrFail();
        Gate::forUser($actor)->authorize('view', [$invoice, $workshop]);

        return $invoice;
    }

    public function create(Workshop $workshop, User $actor, int $orderId, array $data): Invoice
    {
        return DB::transaction(function () use ($workshop, $actor, $orderId, $data): Invoice {
            $workshop = Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('create', [Invoice::class, $workshop]);
            $order = WorkOrder::where('workshop_id', $workshop->id)->whereKey($orderId)->lockForUpdate()->firstOrFail();
            $fingerprint = $this->fingerprint($actor, 'create', $orderId, $data);
            if ($replay = $this->replay($workshop, $actor, $data, $fingerprint)) {
                return $replay;
            }
            abort_unless((int) $data['version'] === $order->version, 409, 'WO telah berubah. Muat ulang detail WO.');
            abort_unless(in_array($order->status, [WorkOrderStatus::Open, WorkOrderStatus::InProgress, WorkOrderStatus::Completed], true), 409, 'Draft invoice hanya tersedia untuk WO Antre, Dikerjakan, atau Selesai.');
            abort_if(Invoice::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->whereIn('status', ['draft', 'issued'])->exists(), 409, 'WO sudah mempunyai invoice aktif. Buka invoice tersebut.');
            $source = $this->source->capture($workshop, $order);
            $previous = Invoice::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->where('status', 'void')->latest('id')->value('id');
            $invoice = new Invoice;
            $invoice->forceFill($source['header'] + ['workshop_id' => $workshop->id, 'work_order_id' => $order->id, 'replaces_invoice_id' => $previous,
                'status' => 'draft', 'version' => 1, 'total' => 0, 'source_fingerprint' => $source['fingerprint']])->save();
            $this->source->replace($invoice, $source);
            self::event($invoice, $actor, 'created', $data['operation_key'], $fingerprint, ['source' => $source]);

            return $invoice;
        });
    }

    public function mutate(Workshop $workshop, User $actor, int $id, array $data, string $operation, ?int $child = null): Invoice
    {
        return DB::transaction(function () use ($workshop, $actor, $id, $data, $operation, $child): Invoice {
            $workshop = Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
            $reference = Invoice::where('workshop_id', $workshop->id)->whereKey($id)->firstOrFail();
            $order = WorkOrder::where('workshop_id', $workshop->id)->whereKey($reference->work_order_id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::where('workshop_id', $workshop->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize(in_array($operation, ['void', 'reverse'], true) ? 'correct' : 'view', [$invoice, $workshop]);
            $fingerprint = $this->fingerprint($actor, $operation, $id, $data, $child);
            if ($replay = $this->replay($workshop, $actor, $data, $fingerprint)) {
                return $replay;
            }
            abort_unless($invoice->version === (int) $data['version'], 409, 'Invoice telah berubah. Muat ulang detail invoice.');
            abort_if($invoice->status === 'void', 409, 'Invoice sudah dibatalkan dan tidak dapat diubah.');
            $details = [];
            if (in_array($operation, ['refresh', 'notes', 'price', 'issue'], true)) {
                abort_unless($invoice->status === 'draft', 409, 'Snapshot invoice terbit tidak dapat diubah.');
                if ($operation === 'refresh') {
                    abort_unless(in_array($order->status, [WorkOrderStatus::Open, WorkOrderStatus::InProgress, WorkOrderStatus::Completed], true), 409, 'WO tidak dapat ditagihkan.');
                    $details['before'] = $invoice->items()->orderBy('id')->get()->toArray();
                    $source = $this->source->capture($workshop, $order);
                    $this->source->replace($invoice, $source);
                    $details['after'] = $source;
                } elseif ($operation === 'notes') {
                    $details = ['before' => $invoice->notes, 'after' => $data['notes'] ?? null];
                    $invoice->notes = $data['notes'] ?? null;
                } elseif ($operation === 'price') {
                    $item = $invoice->items()->where('workshop_id', $workshop->id)->whereKey($child)->lockForUpdate()->firstOrFail();
                    $reason = trim($data['reason'] ?? '');
                    if ($reason === '') {
                        throw ValidationException::withMessages(['reason' => 'Isi alasan penyesuaian harga.']);
                    }
                    $details = ['item_id' => $item->id, 'name' => $item->name_snapshot, 'before' => $item->unit_price, 'after' => (int) $data['unit_price'], 'reason' => $reason];
                    $item->forceFill(['unit_price' => (int) $data['unit_price'], 'subtotal' => Rupiah::subtotal($item->quantity, (int) $data['unit_price']), 'price_reason' => $reason])->save();
                    $invoice->total = $this->total($invoice);
                } else {
                    abort_unless($order->status === WorkOrderStatus::Completed, 409, 'Invoice hanya dapat diterbitkan setelah WO Selesai.');
                    $source = $this->source->capture($workshop, $order);
                    abort_unless(hash_equals($invoice->source_fingerprint, $source['fingerprint']), 409, 'Data WO atau retur berubah. Perbarui draft dari WO sebelum menerbitkan.');
                    if (! $invoice->items()->exists()) {
                        throw ValidationException::withMessages(['invoice' => 'Invoice harus memiliki setidaknya satu baris tagihan.']);
                    }
                    $invoice->total = $this->total($invoice);
                    $sequence = (int) $workshop->next_invoice_number;
                    if ($sequence < 1 || $sequence >= PHP_INT_MAX) {
                        throw ValidationException::withMessages(['invoice' => 'Nomor invoice melebihi batas penyimpanan.']);
                    }
                    $workshop->next_invoice_number = $sequence + 1;
                    $workshop->save();
                    $invoice->forceFill(['status' => 'issued', 'number' => 'INV-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT), 'issued_at' => now('UTC'), 'issued_by' => $actor->id]);
                    $details = ['number' => $invoice->number, 'total' => $invoice->total];
                }
            } elseif ($operation === 'void') {
                if ($invoice->paidAmount() !== 0) {
                    throw ValidationException::withMessages(['invoice' => 'Invoice dengan pembayaran valid tidak dapat dibatalkan. Koreksi catatan pembayaran bukan fitur refund uang.']);
                }
                $this->void($invoice, $actor, $data['reason']);
                $details = ['reason' => $data['reason']];
            } elseif ($operation === 'pay') {
                abort_unless($invoice->status === 'issued', 409, 'Pembayaran hanya tersedia untuk invoice Terbit.');
                $amount = (int) $data['amount'];
                $remaining = $invoice->total - $invoice->paidAmount();
                if ($amount <= 0 || $amount > $remaining) {
                    throw ValidationException::withMessages(['amount' => 'Nominal harus positif dan tidak melebihi sisa tagihan.']);
                }
                $payment = new Payment;
                $payment->forceFill(['workshop_id' => $workshop->id, 'invoice_id' => $invoice->id, 'amount' => $amount, 'method' => $data['method'],
                    'reference' => $data['reference'] ?? null, 'note' => $data['note'] ?? null, 'created_by' => $actor->id, 'paid_at' => now('UTC')])->save();
                $details = ['payment_id' => $payment->id, 'amount' => $amount, 'method' => $payment->method];
            } elseif ($operation === 'reverse') {
                abort_unless($invoice->status === 'issued', 409, 'Koreksi pembayaran hanya untuk invoice Terbit.');
                $payment = $invoice->payments()->where('workshop_id', $workshop->id)->whereKey($child)->lockForUpdate()->firstOrFail();
                abort_if($payment->reversal()->exists(), 409, 'Catatan pembayaran sudah dibatalkan.');
                (new PaymentReversal)->forceFill(['workshop_id' => $workshop->id, 'invoice_id' => $invoice->id, 'payment_id' => $payment->id,
                    'created_by' => $actor->id, 'reason' => $data['reason'], 'created_at' => now('UTC')])->save();
                $details = ['payment_id' => $payment->id, 'amount' => $payment->amount, 'reason' => $data['reason']];
            } else {
                abort(404);
            }
            $invoice->version++;
            $invoice->save();
            self::event($invoice, $actor, $operation, $data['operation_key'], $fingerprint, $details);

            return $invoice;
        });
    }

    private function total(Invoice $invoice): int
    {
        $total = 0;
        foreach ($invoice->items()->orderBy('id')->lazyById(100) as $item) {
            $total = Rupiah::add($total, Rupiah::subtotal($item->quantity, $item->unit_price));
        }

        return $total;
    }

    private function void(Invoice $invoice, User $actor, string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Isi alasan pembatalan.']);
        }
        $invoice->forceFill(['status' => 'void', 'voided_at' => now('UTC'), 'voided_by' => $actor->id, 'void_reason' => $reason]);
    }

    public function cancelDraftForWorkOrder(WorkOrder $order, User $actor): void
    {
        // Called inside the WO cancellation transaction, after workshop/WO locks and owner authorization.
        $invoice = Invoice::where('workshop_id', $order->workshop_id)->where('work_order_id', $order->id)->where('status', 'draft')->lockForUpdate()->first();
        if (! $invoice) {
            return;
        }
        Gate::forUser($actor)->authorize('correct', [$invoice, Workshop::findOrFail($order->workshop_id)]);
        $reason = 'WO dibatalkan: '.$order->cancellation_reason;
        $this->void($invoice, $actor, $reason);
        $invoice->version++;
        $invoice->save();
        self::event($invoice, $actor, 'wo_cancelled', (string) Str::uuid(), hash('sha256', $reason), ['reason' => $reason]);
    }

    private static function event(Invoice $invoice, User $actor, string $type, string $key, string $fingerprint, array $details): void
    {
        (new InvoiceEvent)->forceFill(['workshop_id' => $invoice->workshop_id, 'invoice_id' => $invoice->id, 'actor_id' => $actor->id,
            'type' => $type, 'operation_key' => $key, 'request_fingerprint' => $fingerprint, 'details' => $details, 'created_at' => now('UTC')])->save();
    }
}
