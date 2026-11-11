<?php

namespace Tests\Feature;

use App\Actions\Billing;
use App\Actions\Inventory;
use App\Actions\WorkOrderItems;
use App\Models\InventoryMovement;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\WorkOrderSparePart;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\BillingFixtures;
use Tests\TestCase;

class InvoiceManagementTest extends TestCase
{
    use BillingFixtures,RefreshDatabase;

    public function test_draft_and_issued_snapshots_use_net_consumption_and_ignore_untrusted_totals(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $part = $this->part($owner);
        $this->restock($owner, $part);
        app(WorkOrderItems::class)->save($owner->workshop, $owner->user, $order->id, false, null, ['version' => $order->fresh()->version, 'catalog_id' => $part->id, 'quantity' => 3, 'unit_price' => 40000]);
        $line = WorkOrderSparePart::sole();
        $inventory = app(Inventory::class);
        $inventory->move($owner->workshop, $owner->user, $part->id, ['version' => $order->fresh()->version, 'stock_version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid()], 'work_order_usage', $order->id, $line->id);
        $inventory->move($owner->workshop, $owner->user, $part->id, ['version' => $order->fresh()->version, 'stock_version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid(), 'quantity' => 1, 'reason' => 'Kembali layak stok'], 'work_order_return', $order->id, $line->id);
        $this->finish($owner, $order);
        $invoice = $this->draft($owner, $order);
        $this->assertNull($invoice->number);
        $this->assertSame(180000, $invoice->total);
        $this->assertSame(2, InvoiceItem::where('item_type', 'spare_part')->sole()->quantity);
        $this->get('/invoices/'.$invoice->id)->assertOk()->assertSee('Pelanggan umum');
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice, ['number' => 'BAD', 'total' => 1, 'status' => 'void', 'issued_at' => '2000-01-01', 'workshop_id' => 999]))->assertRedirect();
        $invoice->refresh();
        $this->assertSame('INV-000001', $invoice->number);
        $this->assertSame(180000, $invoice->total);
        $before = $invoice->getAttributes();
        $eventCount = $invoice->events()->count();
        $usage = InventoryMovement::where('type', 'work_order_usage')->sole();
        $inventory->move($owner->workshop, $owner->user, $part->id, $this->stockData($part, ['quantity' => 1, 'reason' => 'Retur sesudah invoice terbit']), 'work_order_return', $order->id, $line->id, $usage->id, true);
        $this->assertSame($eventCount, $invoice->events()->count());
        $owner->workshop->update(['name' => 'Changed workshop', 'phone' => '080000000']);
        $part->forceFill(['name' => 'Changed part', 'selling_price' => 1])->save();
        $this->assertSame($before, $invoice->fresh()->getAttributes());
        $this->get('/invoices/'.$invoice->id.'/print')->assertOk()->assertHeader('cache-control', 'no-store, private')->assertSee('Oli Botol')->assertDontSee('Changed workshop')->assertSee('180.000');
        $this->delete('/invoices/'.$invoice->id)->assertStatus(405);
    }

    public function test_source_changes_require_refresh_and_remove_price_overrides_but_keep_wo_immutable(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $invoice = $this->draft($owner, $order);
        $item = $invoice->items()->sole();
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice))->assertStatus(409);
        $this->put('/invoices/'.$invoice->id.'/items/'.$item->id.'/price', $this->billData($invoice, ['unit_price' => 90000, 'reason' => 'Kesepakatan pelanggan']))->assertRedirect();
        $this->assertSame(90000, $invoice->fresh()->total);
        $this->assertSame(100000, $order->fresh()->getConnection()->table('work_order_services')->where('work_order_id', $order->id)->value('unit_price'));
        $this->finish($owner, $order);
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice))->assertStatus(409);
        $this->post('/invoices/'.$invoice->id.'/refresh', $this->billData($invoice))->assertSessionHasErrors('confirm_reset');
        $this->post('/invoices/'.$invoice->id.'/refresh', $this->billData($invoice, ['confirm_reset' => 1]))->assertRedirect();
        $this->assertSame(100000, $invoice->fresh()->total);
        $this->assertNull($invoice->items()->sole()->price_reason);
        $this->put('/invoices/'.$invoice->id.'/items/'.$invoice->items()->sole()->id.'/price', $this->billData($invoice, ['unit_price' => 80000, 'reason' => 'Harga akhir']))->assertRedirect();
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice))->assertRedirect();
        $this->assertSame(80000, $invoice->fresh()->total);
        $this->put('/invoices/'.$invoice->id, $this->billData($invoice, ['notes' => 'Forbidden']))->assertStatus(409);
        $this->post('/invoices/'.$invoice->id.'/refresh', $this->billData($invoice, ['confirm_reset' => 1]))->assertStatus(409);
    }

    public function test_final_return_changes_source_without_changing_wo_version_and_blocks_issue(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $part = $this->part($owner);
        $this->restock($owner, $part);
        app(WorkOrderItems::class)->save($owner->workshop, $owner->user, $order->id, false, null, ['version' => $order->fresh()->version, 'catalog_id' => $part->id, 'quantity' => 1, 'unit_price' => 50000]);
        $line = WorkOrderSparePart::sole();
        $usage = app(Inventory::class)->move($owner->workshop, $owner->user, $part->id,
            ['version' => $order->fresh()->version, 'stock_version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid()], 'work_order_usage', $order->id, $line->id);
        $this->finish($owner, $order);
        $invoice = $this->draft($owner, $order);
        $version = $order->fresh()->version;
        app(Inventory::class)->move($owner->workshop, $owner->user, $part->id, $this->stockData($part, ['quantity' => 1, 'reason' => 'Retur setelah selesai']), 'work_order_return', $order->id, $line->id, $usage->id, true);
        $this->assertSame($version, $order->fresh()->version);
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice))->assertStatus(409);
        $this->post('/invoices/'.$invoice->id.'/refresh', $this->billData($invoice, ['confirm_reset' => 1]))->assertRedirect();
        $this->assertSame(100000, $invoice->fresh()->total);
        $this->assertSame(1, $invoice->items()->count());
    }

    public function test_unique_current_invoice_replays_numbers_void_reissue_and_wo_cancellation(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $payload = ['workshop_context' => $owner->workshop_id, 'work_order_id' => $order->id, 'version' => $order->version, 'operation_key' => (string) Str::uuid()];
        $this->post('/invoices', $payload)->assertRedirect();
        $this->post('/invoices', $payload)->assertRedirect();
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('invoice_events', 1);
        $this->post('/invoices', array_replace($payload, ['operation_key' => (string) Str::uuid()]))->assertStatus(409);
        $invoice = Invoice::sole();
        $this->post('/work-orders/'.$order->id.'/transition', $this->lineData($order, ['action' => 'cancel', 'cancellation_reason' => 'Batal pelanggan']))->assertRedirect();
        $this->assertSame('void', $invoice->fresh()->status);
        $this->assertNull($invoice->fresh()->number);
        $this->post('/invoices', array_replace($payload, ['operation_key' => (string) Str::uuid(), 'version' => $order->fresh()->version]))->assertStatus(409);
        $official = $this->issued($owner);
        $this->assertSame('INV-000001', $official->number);
        $void = $this->billData($official, ['reason' => 'Salah rincian']);
        $this->post('/invoices/'.$official->id.'/void', $void)->assertRedirect();
        $this->post('/invoices/'.$official->id.'/void', $void)->assertRedirect();
        $replacement = $this->draft($owner, $official->order);
        $this->assertSame($official->id, $replacement->replaces_invoice_id);
        $issue = $this->billData($replacement);
        $this->post('/invoices/'.$replacement->id.'/issue', $issue)->assertRedirect();
        $this->post('/invoices/'.$replacement->id.'/issue', $issue)->assertRedirect();
        $this->assertSame('INV-000002', $replacement->fresh()->number);
        $this->assertSame(3, $owner->workshop->fresh()->next_invoice_number);
    }

    public function test_empty_zero_total_overflow_price_reason_and_notes_validation(): void
    {
        $owner = $this->owner();
        $empty = $this->order($owner, true);
        $this->finish($owner, $empty);
        $draft = $this->draft($owner, $empty);
        $this->post('/invoices/'.$draft->id.'/issue', $this->billData($draft))->assertSessionHasErrors('invoice');
        $free = $this->issued($owner, 0);
        $this->assertSame('Lunas—tanpa pembayaran', $free->paymentLabel());
        $this->post('/invoices/'.$free->id.'/pay', $this->billData($free, ['amount' => 1, 'method' => 'cash']))->assertSessionHasErrors('amount');
        $invoice = $this->draft($owner, $this->readyOrder($owner));
        $item = $invoice->items()->sole();
        $this->put('/invoices/'.$invoice->id.'/items/'.$item->id.'/price', $this->billData($invoice, ['unit_price' => 1, 'reason' => '']))->assertSessionHasErrors('reason');
        $this->put('/invoices/'.$invoice->id, $this->billData($invoice, ['notes' => str_repeat('x', 5001)]))->assertSessionHasErrors('notes');
        $this->put('/invoices/'.$invoice->id, $this->billData($invoice, ['notes' => 'Catatan aman', 'total' => 1]))->assertRedirect();
        $this->assertSame(100000, $invoice->fresh()->total);
        $this->assertSame('Catatan aman', $invoice->fresh()->notes);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_roles_tenant_ids_revocation_stale_versions_and_contexts(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $other = WorkshopMembership::factory()->create(['role' => 'owner']);
        $foreign = $this->readyOrder($other);
        $foreignOwnerInvoice = null;
        $this->get('/invoices?work_order_id='.$foreign->id)->assertSessionHasErrors('work_order_id');
        $this->get('/invoices/999999999999999999999999')->assertNotFound();
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['version' => 1, 'amount' => 1, 'method' => 'cash']))->assertStatus(409);
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['workshop_context' => $other->workshop_id, 'amount' => 1, 'method' => 'cash']))->assertStatus(409);
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user)->get('/invoices')->assertOk()->assertSee($invoice->number);
        $this->post('/invoices/'.$invoice->id.'/void', $this->billData($invoice, ['reason' => 'Tidak boleh']))->assertForbidden();
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 10, 'method' => 'cash']))->assertRedirect();
        $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'mechanic']);
        $mechanic->user->forceFill(['is_super_admin' => true])->save();
        $this->actingAs($mechanic->user);
        foreach (['/invoices', '/invoices/create', '/invoices/'.$invoice->id, '/invoices/'.$invoice->id.'/print'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 10, 'method' => 'cash']))->assertForbidden();
        $this->actingAs($other->user)->withSession(['active_workshop_id' => $other->workshop_id]);
        $this->get('/invoices/'.$invoice->id)->assertNotFound();
        $this->get('/invoices/'.$invoice->id.'/print')->assertNotFound();
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['workshop_context' => $other->workshop_id, 'amount' => 10, 'method' => 'cash']))->assertNotFound();
        $this->actingAs($owner->user)->withSession(['active_workshop_id' => $owner->workshop_id]);
        $owner->is_active = false;
        $owner->save();
        $this->get('/invoices')->assertRedirect(route('workshops.select'));
    }

    public function test_invoice_search_pagination_and_creation_overflow_are_safe(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $this->get('/invoices?q='.$invoice->number)->assertSee($invoice->number);
        $this->get('/invoices?q='.str_replace(' ', '', $invoice->plate_number_snapshot))->assertSee($invoice->number);
        $this->get('/invoices?q=%25')->assertDontSee($invoice->number);
        $this->get('/invoices?payment=unpaid')->assertSee($invoice->number);
        $this->get('/invoices?page[]=1')->assertSessionHasErrors('page');
        for ($i = 0; $i < 21; $i++) {
            $order = $this->order($owner);
            app(Billing::class)->create($owner->workshop, $owner->user, $order->id, ['version' => $order->version, 'operation_key' => (string) Str::uuid()]);
        }
        $this->get('/invoices?page=2')->assertOk()->assertSee($invoice->number);
        $overflow = $this->readyOrder($owner, PHP_INT_MAX);
        $service = $this->service($owner);
        app(WorkOrderItems::class)->save($owner->workshop, $owner->user, $overflow->id, true, null, ['version' => $overflow->version, 'catalog_id' => $service->id, 'quantity' => 1, 'unit_price' => 1]);
        $this->post('/invoices', ['workshop_context' => $owner->workshop_id, 'work_order_id' => $overflow->id, 'version' => $overflow->fresh()->version, 'operation_key' => (string) Str::uuid()])->assertSessionHasErrors('invoice');
        $this->assertFalse(Invoice::where('work_order_id', $overflow->id)->exists());
    }
}
