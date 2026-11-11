<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\BillingFixtures;
use Tests\TestCase;

class PaymentManagementTest extends TestCase
{
    use BillingFixtures,RefreshDatabase;

    public function test_installments_mixed_methods_replay_and_owner_corrections_preserve_original_records(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $url = '/invoices/'.$invoice->id;
        $payload = $this->billData($invoice, ['amount' => 30000, 'method' => 'cash']);
        $this->post($url.'/pay', $payload)->assertRedirect();
        $this->post($url.'/pay', $payload)->assertRedirect();
        $this->assertSame(30000, $invoice->paidAmount());
        $this->assertSame('Dibayar sebagian', $invoice->paymentLabel());
        $first = Payment::sole();
        $original = $first->getAttributes();
        $this->post($url.'/pay', $this->billData($invoice, ['amount' => 70000, 'method' => 'bank_transfer', 'reference' => 'TRX-TEST', 'note' => 'Transfer diterima']))->assertRedirect();
        $this->assertSame('Lunas', $invoice->paymentLabel());
        $this->post($url.'/pay', $this->billData($invoice, ['amount' => 1, 'method' => 'cash']))->assertSessionHasErrors('amount');
        $this->post($url.'/void', $this->billData($invoice, ['reason' => 'Belum boleh']))->assertSessionHasErrors('invoice');
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user)->post($url.'/payments/'.$first->id.'/reverse', $this->billData($invoice, ['reason' => 'Salah input']))->assertForbidden();
        $this->actingAs($owner->user);
        $reverse = $this->billData($invoice, ['reason' => 'Nominal salah dicatat']);
        $this->post($url.'/payments/'.$first->id.'/reverse', $reverse)->assertRedirect();
        $this->post($url.'/payments/'.$first->id.'/reverse', $reverse)->assertRedirect();
        $this->assertSame($original, $first->fresh()->getAttributes());
        $this->assertSame(70000, $invoice->paidAmount());
        $this->assertSame('Dibayar sebagian', $invoice->paymentLabel());
        $this->assertDatabaseCount('payment_reversals', 1);
        $this->post($url.'/payments/'.$first->id.'/reverse', $this->billData($invoice, ['reason' => 'Lagi']))->assertStatus(409);
        $this->post($url.'/pay', $this->billData($invoice, ['amount' => 30000, 'method' => 'other', 'note' => 'Metode lain disepakati']))->assertRedirect();
        $this->assertSame('Lunas', $invoice->paymentLabel());
        $this->assertDatabaseCount('payments', 3);
        $this->get($url)->assertOk()->assertSee('Catatan dibatalkan')->assertSee('Nominal salah dicatat');
        $this->get('/invoices?payment=paid')->assertSee($invoice->number);
        $this->get('/invoices?payment=partial')->assertDontSee($invoice->number);
    }

    #[DataProvider('invalidPayments')]
    public function test_invalid_payments_leave_totals_and_events_unchanged(array $extra, string $field): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $count = $invoice->events()->count();
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, $extra))->assertSessionHasErrors($field);
        $this->assertSame(0, $invoice->paidAmount());
        $this->assertSame($count, $invoice->events()->count());
    }

    public static function invalidPayments(): array
    {
        return [[['amount' => 0, 'method' => 'cash'], 'amount'], [['amount' => -1, 'method' => 'cash'], 'amount'], [['amount' => 1.5, 'method' => 'cash'], 'amount'],
            [['amount' => 100001, 'method' => 'cash'], 'amount'], [['amount' => 1, 'method' => 'unknown'], 'method'], [['amount' => 1, 'method' => 'other', 'note' => ' '], 'note']];
    }

    public function test_draft_void_invoice_payments_and_forged_target_ids_are_rejected(): void
    {
        $owner = $this->owner();
        $draft = $this->draft($owner, $this->readyOrder($owner));
        $this->post('/invoices/'.$draft->id.'/pay', $this->billData($draft, ['amount' => 1, 'method' => 'cash']))->assertStatus(409);
        $invoice = $this->issued($owner);
        $otherInvoice = $this->issued($owner);
        $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 1, 'method' => 'cash']))->assertRedirect();
        $payment = Payment::sole();
        $this->post('/invoices/'.$otherInvoice->id.'/payments/'.$payment->id.'/reverse', $this->billData($otherInvoice, ['reason' => 'Manipulasi ID']))->assertNotFound();
        $this->post('/invoices/'.$otherInvoice->id.'/void', $this->billData($otherInvoice, ['reason' => 'Batal']))->assertRedirect();
        $this->post('/invoices/'.$otherInvoice->id.'/pay', $this->billData($otherInvoice, ['amount' => 1, 'method' => 'cash']))->assertStatus(409);
        $this->assertDatabaseCount('payment_reversals', 0);
    }

    public function test_actor_payload_mismatch_and_revocation_cannot_replay_a_payment(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $payload = $this->billData($invoice, ['amount' => 100, 'method' => 'cash']);
        $url = '/invoices/'.$invoice->id.'/pay';
        $this->post($url, $payload)->assertRedirect();
        $this->post($url, array_replace($payload, ['amount' => 200]))->assertStatus(409);
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user)->post($url, $payload)->assertStatus(409);
        $cashier->is_active = false;
        $cashier->save();
        $this->post($url, $payload)->assertRedirect(route('workshops.select'));
        $this->assertDatabaseCount('payments', 1);
    }
}
