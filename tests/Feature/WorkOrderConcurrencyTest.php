<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class WorkOrderConcurrencyTest extends TestCase
{
    // Committed fixtures are visible to both independent PostgreSQL connections.
    use DatabaseMigrations;

    private function race(WorkshopMembership $owner, string $operation, array $records, int $version = 1): array
    {
        $processes = [];
        foreach ($records as $record) {
            $process = new Process([PHP_BINARY, base_path('tests/Support/work-order-worker.php'),
                (string) $owner->workshop_id, (string) $owner->user_id, $operation, (string) $record, (string) $version], base_path(),
                ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'bengkel_saas_testing', 'DB_URL' => '', 'MAIL_MAILER' => 'array']);
            $process->setTimeout(60);
            $process->start();
            $processes[] = $process;
        }

        return array_map(function (Process $process): array {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), 'Concurrency worker failed.');
            $result = json_decode($process->getOutput(), true);
            $this->assertIsArray($result, 'Concurrency worker did not return a result.');

            return $result;
        }, $processes);
    }

    public function test_concurrent_creations_and_replayed_transition_preserve_numbers_active_uniqueness_and_events(): void
    {
        $owner = WorkshopMembership::factory()->create(['role' => 'owner']);
        $vehicles = Vehicle::factory()->count(3)->create(['workshop_id' => $owner->workshop_id]);
        $numbers = array_column($this->race($owner, 'create', [$vehicles[0]->id, $vehicles[1]->id]), 'number');
        sort($numbers);
        $this->assertSame(['WO-000001', 'WO-000002'], $numbers);
        $results = array_column($this->race($owner, 'create', [$vehicles[2]->id, $vehicles[2]->id]), 'result');
        $this->assertContains('ok', $results);
        $this->assertContains('validation', $results);
        $this->assertDatabaseCount('work_orders', 3);
        $this->assertDatabaseCount('work_order_events', 3);
        $order = WorkOrder::firstOrFail();
        $results = array_column($this->race($owner, 'transition', [$order->id, $order->id]), 'result');
        $this->assertContains('ok', $results);
        $this->assertContains(409, $results);
        $this->assertSame(2, $order->fresh()->version);
        $this->assertDatabaseCount('work_order_events', 4);
        $this->assertSame(4, $owner->workshop->fresh()->next_work_order_number);
    }
}
