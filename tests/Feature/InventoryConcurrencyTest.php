<?php

namespace Tests\Feature;

use App\Actions\WorkOrderItems;
use App\Models\InventoryMovement;
use App\Models\WorkOrder;
use App\Models\WorkOrderSparePart;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\Support\InventoryFixtures;
use Tests\TestCase;

class InventoryConcurrencyTest extends TestCase
{
    use DatabaseMigrations, InventoryFixtures;

    private function race($owner, array $inputs): array
    {
        $processes = [];
        foreach ($inputs as $input) {
            $process = new Process([PHP_BINARY, base_path('tests/Support/inventory-worker.php'), (string) $owner->workshop_id, (string) $owner->user_id, json_encode($input, JSON_THROW_ON_ERROR)], base_path(),
                ['APP_ENV' => 'testing', 'DB_CONNECTION' => 'pgsql', 'DB_DATABASE' => 'bengkel_saas_testing', 'DB_URL' => '', 'MAIL_MAILER' => 'array']);
            $process->setTimeout(60);
            $process->start();
            $processes[] = $process;
        }

        return array_map(function (Process $process): array {
            $process->wait();
            $this->assertTrue($process->isSuccessful(), 'Inventory worker failed.');
            $result = json_decode($process->getOutput(), true);
            $this->assertIsArray($result, 'Inventory worker returned no result.');

            return $result;
        }, $processes);
    }

    public function test_simultaneous_stock_usage_returns_and_duplicate_submits_are_atomic(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner);
        $restock = ['part' => $part->id, 'type' => 'restock', 'data' => $this->stockData($part, ['quantity' => 1])];
        $results = $this->race($owner, [$restock, $restock]);
        $this->assertSame(['ok', 'ok'], array_column($results, 'result'));
        $this->assertSame($results[0]['id'], $results[1]['id']);
        $this->assertSame(1, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 1);
        $inputs = [];
        foreach ([1, 2] as $unused) {
            $order = $this->order($owner, true);
            app(WorkOrderItems::class)->save($owner->workshop, $owner->user, $order->id, false, null, ['version' => $order->version, 'catalog_id' => $part->id, 'quantity' => 1, 'unit_price' => 10000]);
            $line = WorkOrderSparePart::where('work_order_id', $order->id)->sole();
            $inputs[] = ['part' => $part->id, 'order' => $order->id, 'line' => $line->id, 'type' => 'work_order_usage', 'data' => ['version' => $order->fresh()->version, 'stock_version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid()]];
        }
        $results = array_column($this->race($owner, $inputs), 'result');
        $this->assertContains('ok', $results);
        $this->assertContains(409, $results);
        $this->assertSame(0, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertSame(1, WorkOrderSparePart::where('status', 'used')->count());
        $usage = InventoryMovement::where('type', 'work_order_usage')->sole();
        $order = WorkOrder::findOrFail($usage->work_order_id);
        $return = ['part' => $part->id, 'order' => $order->id, 'line' => $usage->work_order_spare_part_id, 'type' => 'work_order_return', 'data' => [
            'version' => $order->version, 'stock_version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid(), 'quantity' => 1, 'reason' => 'Retur bersamaan']];
        $second = $return;
        $second['data']['operation_key'] = (string) Str::uuid();
        $results = array_column($this->race($owner, [$return, $second]), 'result');
        $this->assertContains('ok', $results);
        $this->assertContains(409, $results);
        $this->assertSame(1, $part->fresh()->current_stock);
        $this->assertSame(1, InventoryMovement::where('type', 'work_order_return')->count());
        $retry = $this->race($owner, [$return]);
        // The first contender may have lost; any stale request still cannot add another return.
        $this->assertContains($retry[0]['result'], ['ok', 409]);
        $this->assertSame(1, $part->fresh()->current_stock);
    }
}
