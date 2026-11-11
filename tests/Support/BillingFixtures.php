<?php

namespace Tests\Support;

use App\Actions\WorkOrderItems;
use App\Actions\WorkOrders;
use App\Models\Invoice;
use App\Models\WorkOrder;
use App\Models\WorkshopMembership;
use Illuminate\Support\Str;

trait BillingFixtures
{
    use InventoryFixtures;

    private function readyOrder(WorkshopMembership $owner, int $price = 100000): WorkOrder
    {
        $order = $this->order($owner, true);
        $service = $this->service($owner);
        app(WorkOrderItems::class)->save($owner->workshop, $owner->user, $order->id, true, null, ['version' => $order->version, 'catalog_id' => $service->id, 'quantity' => 1, 'unit_price' => $price]);

        return $order->fresh();
    }

    private function finish(WorkshopMembership $owner, WorkOrder $order): void
    {
        app(WorkOrders::class)->mutate($owner->workshop, $owner->user, $order->id, ['version' => $order->fresh()->version, 'action' => 'complete'], 'transition');
    }

    private function draft(WorkshopMembership $owner, WorkOrder $order): Invoice
    {
        $this->post('/invoices', ['workshop_context' => $owner->workshop_id, 'work_order_id' => $order->id, 'version' => $order->fresh()->version, 'operation_key' => (string) Str::uuid()])->assertRedirect();

        return Invoice::where('work_order_id', $order->id)->latest('id')->firstOrFail();
    }

    private function issued(WorkshopMembership $owner, int $price = 100000): Invoice
    {
        $order = $this->readyOrder($owner, $price);
        $this->finish($owner, $order);
        $invoice = $this->draft($owner, $order);
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice))->assertRedirect();

        return $invoice->fresh();
    }

    private function billData(Invoice $invoice, array $extra = []): array
    {
        return array_replace(['workshop_context' => $invoice->workshop_id, 'version' => $invoice->fresh()->version, 'operation_key' => (string) Str::uuid()], $extra);
    }
}
