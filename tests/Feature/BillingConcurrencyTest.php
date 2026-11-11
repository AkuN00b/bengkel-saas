<?php

namespace Tests\Feature;

use App\Actions\Billing;
use App\Actions\Inventory;
use App\Actions\WorkOrderItems;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\WorkOrderSparePart;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\BillingFixtures;
use Tests\TestCase;

class BillingConcurrencyTest extends TestCase
{
    use BillingFixtures,DatabaseMigrations;

    private function race($owner, array $inputs): array
    {
        $processes = [];
        foreach ($inputs as $input) {
            $process = new Process([PHP_BINARY, base_path('tests/Support/billing-worker.php'), (string) $owner->workshop_id, (string) $owner->user_id, json_encode($input, JSON_THROW_ON_ERROR)], base_path(),
                ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'bengkel_saas_testing', 'DB_URL' => '', 'MAIL_MAILER' => 'array']);
            $process->setTimeout(90);
            $process->start();
            $processes[] = $process;
        }

        return array_map(function (Process $process) {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), 'Billing concurrency worker failed.');
            $result = json_decode($process->getOutput(), true);
            $this->assertIsArray($result, 'Missing worker result.');

            return $result['result'];
        }, $processes);
    }

    public function test_duplicate_creation_issuance_payment_and_reversal_are_serialized(): void
    {
        $owner = $this->owner();
        $order = $this->readyOrder($owner);
        $this->finish($owner, $order);
        $create = ['op' => 'create', 'order' => $order->id, 'data' => ['version' => $order->fresh()->version, 'operation_key' => (string) Str::uuid()]];
        $results = $this->race($owner, [$create, $create]);
        $this->assertSame(['ok', 'ok'], $results);
        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::sole();
        $issue = ['op' => 'issue', 'invoice' => $invoice->id, 'data' => $this->billData($invoice)];
        $other = $issue;
        $other['data']['operation_key'] = (string) Str::uuid();
        $results = $this->race($owner, [$issue, $other]);
        $this->assertContains('ok', $results);
        $this->assertContains(409, $results);
        $this->assertSame('INV-000001', $invoice->fresh()->number);
        $this->assertSame(2, $owner->workshop->fresh()->next_invoice_number);
        $pay = ['op' => 'pay', 'invoice' => $invoice->id, 'data' => $this->billData($invoice, ['amount' => 100000, 'method' => 'cash'])];
        $results = $this->race($owner, [$pay, $pay]);
        $this->assertSame(['ok', 'ok'], $results);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(100000, $invoice->paidAmount());
        $payment = Payment::sole();
        $reverse = ['op' => 'reverse', 'invoice' => $invoice->id, 'child' => $payment->id, 'data' => $this->billData($invoice, ['reason' => 'Duplikat catatan'])];
        $other = $reverse;
        $other['data']['operation_key'] = (string) Str::uuid();
        $results = $this->race($owner, [$reverse, $other]);
        $this->assertContains('ok', $results);
        $this->assertContains(409, $results);
        $this->assertDatabaseCount('payment_reversals', 1);
        $this->assertSame(0, $invoice->paidAmount());
    }

    public function test_last_balance_payments_and_void_races_cannot_overpay_or_void_paid_documents(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner);
        $pay = ['op' => 'pay', 'invoice' => $invoice->id, 'data' => $this->billData($invoice, ['amount' => 100000, 'method' => 'cash'])];
        $other = $pay;
        $other['data']['operation_key'] = (string) Str::uuid();
        $results = $this->race($owner, [$pay, $other]);
        $this->assertContains('ok', $results);
        $this->assertContains(409, $results);
        $this->assertSame(100000, $invoice->paidAmount());
        $this->assertDatabaseCount('payments', 1);
        $second = $this->issued($owner);
        $pay = ['op' => 'pay', 'invoice' => $second->id, 'data' => $this->billData($second, ['amount' => 10000, 'method' => 'cash'])];
        $void = ['op' => 'void', 'invoice' => $second->id, 'data' => $this->billData($second, ['reason' => 'Batal tagihan'])];
        $results = $this->race($owner, [$pay, $void]);
        $this->assertContains('ok', $results);
        $this->assertContains(409, $results);
        $this->assertTrue(($second->fresh()->status === 'void' && $second->paidAmount() === 0) || ($second->fresh()->status === 'issued' && $second->paidAmount() === 10000));
    }

    public function test_final_stock_return_and_invoice_issue_use_a_consistent_source_snapshot(): void
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
        $issue = ['op' => 'issue', 'invoice' => $invoice->id, 'data' => $this->billData($invoice)];
        $return = ['op' => 'return', 'order' => $order->id, 'part' => $part->id, 'line' => $line->id, 'source' => $usage->id, 'data' => $this->stockData($part, ['quantity' => 1, 'reason' => 'Kembali sebelum/sesudah terbit'])];
        $results = $this->race($owner, [$issue, $return]);
        $this->assertSame('ok', $results[1]);
        $this->assertContains($results[0], ['ok', 409]);
        $this->assertSame(150000, $invoice->fresh()->total);
        $this->assertSame(5, $part->fresh()->current_stock);
        if ($results[0] === 409) {
            $this->assertSame('draft', $invoice->fresh()->status);
            app(Billing::class)->mutate($owner->workshop, $owner->user, $invoice->id, $this->billData($invoice, ['confirm_reset' => 1]), 'refresh');
            $this->assertSame(100000, $invoice->fresh()->total);
        } else {
            $this->assertSame('issued', $invoice->fresh()->status);
        }
    }
}
