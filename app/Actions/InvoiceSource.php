<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Models\WorkOrderSparePart;
use App\Models\Workshop;
use App\Support\Rupiah;
use Illuminate\Validation\ValidationException;

class InvoiceSource
{
    public function capture(Workshop $workshop, WorkOrder $order): array
    {
        $services = WorkOrderService::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->where('status', 'active')->orderBy('id');
        $parts = WorkOrderSparePart::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->where('status', 'used')->orderBy('id');
        if ((clone $services)->count() + (clone $parts)->count() > 1000) {
            throw ValidationException::withMessages(['invoice' => 'Invoice dibatasi 1.000 baris sumber. Tidak ada baris yang dipotong otomatis.']);
        }
        $rows = [];
        $total = 0;
        foreach ($services->get() as $line) {
            $rows[] = ['item_type' => 'service', 'work_order_service_id' => $line->id, 'work_order_spare_part_id' => null, 'name_snapshot' => $line->name_snapshot,
                'description_snapshot' => $line->description_snapshot, 'unit_snapshot' => null, 'quantity' => $line->quantity, 'unit_price' => $line->unit_price, 'subtotal' => Rupiah::subtotal($line->quantity, $line->unit_price)];
        }
        $partRows = $parts->withSum(['movements as returned' => fn ($q) => $q->where('type', 'work_order_return')], 'quantity')->get();
        $returns = [];
        foreach ($partRows as $line) {
            $returned = (int) $line->returned;
            $quantity = $line->quantity - $returned;
            $returns[] = ['id' => $line->id, 'consumed' => $line->quantity, 'returned' => $returned];
            if ($quantity <= 0) {
                continue;
            }
            $rows[] = ['item_type' => 'spare_part', 'work_order_service_id' => null, 'work_order_spare_part_id' => $line->id, 'name_snapshot' => $line->name_snapshot,
                'description_snapshot' => null, 'unit_snapshot' => $line->unit_snapshot, 'quantity' => $quantity, 'unit_price' => $line->unit_price, 'subtotal' => Rupiah::subtotal($quantity, $line->unit_price)];
        }
        foreach ($rows as $row) {
            $total = Rupiah::add($total, $row['subtotal']);
        }
        $header = ['work_order_number' => $order->number, 'plate_number_snapshot' => $order->plate_number_snapshot,
            'customer_name_snapshot' => $order->customer_name_snapshot, 'customer_phone_snapshot' => $order->customer_phone_snapshot,
            'workshop_name_snapshot' => $workshop->name, 'workshop_phone_snapshot' => $workshop->phone, 'workshop_address_snapshot' => $workshop->address];
        $fingerprint = hash('sha256', json_encode([$order->id, $order->version, $order->status->value, $header, $rows, $returns], JSON_THROW_ON_ERROR));

        return compact('header', 'rows', 'total', 'fingerprint');
    }

    public function replace(Invoice $invoice, array $source): void
    {
        $invoice->items()->delete();
        foreach ($source['rows'] as $row) {
            (new InvoiceItem)->forceFill($row + ['workshop_id' => $invoice->workshop_id, 'work_order_id' => $invoice->work_order_id, 'invoice_id' => $invoice->id])->save();
        }
        $invoice->forceFill($source['header'] + ['source_fingerprint' => $source['fingerprint'], 'total' => $source['total']])->save();
    }
}
