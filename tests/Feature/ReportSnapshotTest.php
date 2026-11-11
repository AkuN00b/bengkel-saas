<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Queries\Reporting;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\Support\BillingFixtures;
use Tests\TestCase;

class ReportSnapshotTest extends TestCase
{
    use BillingFixtures, DatabaseMigrations;

    public function test_report_keeps_one_read_only_snapshot_without_blocking_payment_or_reversal(): void
    {
        $owner = $this->owner();
        $invoice = $this->issued($owner, 500000);
        foreach (['pay', 'reverse'] as $operation) {
            $input = new InputStream;
            $worker = new Process([PHP_BINARY, base_path('tests/Support/report-snapshot-worker.php'), (string) $owner->workshop_id], base_path(),
                ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'bengkel_saas_testing', 'DB_URL' => '', 'MAIL_MAILER' => 'array']);
            $worker->setInput($input);
            $worker->setTimeout(20);
            $worker->start();
            try {
                $this->assertTrue($worker->waitUntil(fn ($type, $buffer) => str_contains($buffer, 'READY')), 'Snapshot reader did not start.');
                if ($operation === 'pay') {
                    $this->post('/invoices/'.$invoice->id.'/pay', $this->billData($invoice, ['amount' => 200000, 'method' => 'cash']))->assertRedirect();
                } else {
                    $this->post('/invoices/'.$invoice->id.'/payments/'.Payment::sole()->id.'/reverse', $this->billData($invoice, ['reason' => 'Koreksi bersamaan']))->assertRedirect();
                }
                $input->write("continue\n");
                $input->close();
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), 'Report snapshot worker failed.');
                $result = json_decode(explode("\n", trim($worker->getOutput()), 2)[1], true, 512, JSON_THROW_ON_ERROR);
                $this->assertSame('repeatable read', $result['isolation']);
                $this->assertSame('on', $result['readOnly']);
                $this->assertTrue($result['writeBlocked']);
                $this->assertSame($result['before'], $result['after']);
                $this->assertSame($operation === 'pay' ? '500000' : '300000', $result['before']);
                $report = app(Reporting::class);
                $f = ['cutoff' => CarbonImmutable::now('UTC')->addMinute(), 'timezone' => 'Asia/Jakarta', 'data' => []];
                $this->assertSame($operation === 'pay' ? '300000' : '500000', $report->receivableTotal($report->receivables($owner->workshop, $f))->remaining);
            } finally {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
        }
    }
}
