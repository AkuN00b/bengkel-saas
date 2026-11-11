<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\WorkshopMembership;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\BillingFixtures;
use Tests\TestCase;

class BillingDatabaseTest extends TestCase
{
    use BillingFixtures,RefreshDatabase;

    private function rejected(callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Invalid financial mutation was accepted.');
        } catch (QueryException $e) {
            $this->assertContains((string) $e->getCode(), ['23503', '23505', '23514', '23001', 'P0001']);
        }
    }

    public function test_financial_history_and_snapshots_are_protected_by_postgresql(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $item = $invoice->items()->sole();
        $this->rejected(fn () => DB::table('invoices')->where('id', $invoice->id)->update(['total' => 1]));
        $this->rejected(fn () => DB::table('invoices')->where('id', $invoice->id)->delete());
        $this->rejected(fn () => DB::table('invoice_items')->where('id', $item->id)->update(['quantity' => 2, 'subtotal' => 200000]));
        $this->rejected(fn () => DB::table('invoice_items')->where('id', $item->id)->delete());
        $this->rejected(fn () => DB::table('invoice_events')->where('invoice_id', $invoice->id)->update(['type' => 'forged']));
        $this->rejected(fn () => DB::table('invoice_events')->where('invoice_id', $invoice->id)->delete());
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 50000, 'method' => 'cash']))->assertRedirect();
        $payment = Payment::sole();
        $this->rejected(fn () => DB::table('payments')->where('id', $payment->id)->update(['amount' => 1]));
        $this->rejected(fn () => DB::table('payments')->where('id', $payment->id)->delete());
        $row = $payment->getAttributes();
        unset($row['id']);
        $row['amount'] = 50001;
        $this->rejected(fn () => DB::table('payments')->insert($row));
        $this->rejected(fn () => DB::table('invoices')->where('id', $invoice->id)->update(['status' => 'void', 'voided_at' => now(), 'voided_by' => $owner->user_id, 'void_reason' => 'Has payment']));
        $this->post('/invoices/'.$invoice->id.'/payments/'.$payment->id.'/reverse', $this->billData($invoice, ['reason' => 'Salah catat']))->assertRedirect();
        $this->rejected(fn () => DB::table('payment_reversals')->where('payment_id', $payment->id)->delete());
        $this->rejected(fn () => DB::table('payment_reversals')->where('payment_id', $payment->id)->update(['reason' => 'Rewrite']));
        $this->assertSame(0, $invoice->paidAmount());
    }

    public function test_tenant_source_references_current_uniqueness_and_totals_are_constrained(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $draft = $this->draft($owner, $order);
        $item = $draft->items()->sole();
        $other = $this->readyOrder($owner);
        $otherSource = DB::table('work_order_services')->where('work_order_id', $other->id)->value('id');
        $this->rejected(fn () => DB::table('invoice_items')->where('id', $item->id)->update(['work_order_service_id' => $otherSource]));
        $this->rejected(fn () => DB::table('invoice_items')->where('id', $item->id)->update(['subtotal' => 1]));
        $row = $draft->getAttributes();
        unset($row['id']);
        $this->rejected(fn () => DB::table('invoices')->insert($row));
        $foreign = WorkshopMembership::factory()->create(['role' => 'owner']);
        $row['workshop_id'] = $foreign->workshop_id;
        $this->rejected(fn () => DB::table('invoices')->insert($row));
        $this->rejected(fn () => DB::table('invoices')->where('id', $draft->id)->update(['status' => 'issued', 'number' => 'INV-FAKE', 'issued_at' => now(), 'issued_by' => $owner->user_id]));
        $this->finish($owner, $order);
        $this->rejected(fn () => DB::table('invoices')->where('id', $draft->id)->update(['status' => 'issued', 'number' => 'INV-FAKE', 'issued_at' => now(), 'issued_by' => $owner->user_id, 'total' => 1]));
    }

    public function test_billing_migration_round_trip_preserves_prior_data_and_counter_defaults(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $before = $order->fresh()->getAttributes();
        $part = $this->part($owner);
        $this->restock($owner, $part);
        $migration = require database_path('migrations/2026_11_11_000000_create_invoices_and_payments.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('invoices'));
        $this->assertSame($before, $order->fresh()->getAttributes());
        $migration->up();
        $this->assertSame(1, $owner->workshop->fresh()->next_invoice_number);
        $this->assertSame(5, $part->fresh()->current_stock);
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->finish($owner, $order);
        $invoice = $this->draft($owner, $order);
        $this->post('/invoices/'.$invoice->id.'/issue', $this->billData($invoice))->assertRedirect();
        $this->assertSame('INV-000001', $invoice->fresh()->number);
    }
}
