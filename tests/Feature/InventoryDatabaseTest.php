<?php

namespace Tests\Feature;

use App\Actions\WorkOrderItems;
use App\Models\InventoryMovement;
use App\Models\WorkOrderSparePart;
use App\Models\WorkshopMembership;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\Support\InventoryFixtures;
use Tests\TestCase;

class InventoryDatabaseTest extends TestCase
{
    use InventoryFixtures, RefreshDatabase;

    private function rejected(callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Invalid inventory data was accepted.');
        } catch (QueryException $e) {
            $this->assertContains((string) $e->getCode(), ['23503', '23505', '23514', '23001', 'P0001']);
        }
    }

    public function test_constraints_tenant_references_ledger_immutability_and_balance_validation(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner);
        $order = $this->order($owner, true);
        $this->restock($owner, $part);
        $other = WorkshopMembership::factory()->create(['role' => 'owner']);
        $foreign = $this->part($other);
        $this->rejected(fn () => DB::table('spare_parts')->where('id', $part->id)->update(['current_stock' => -1]));
        $this->rejected(fn () => DB::table('spare_parts')->where('id', $part->id)->update(['selling_price' => -1]));
        app(WorkOrderItems::class)->save($owner->workshop, $owner->user, $order->id, false, null, ['version' => $order->version, 'catalog_id' => $part->id, 'quantity' => 1, 'unit_price' => 10000]);
        $line = WorkOrderSparePart::sole();
        $this->rejected(fn () => DB::table('work_order_spare_parts')->where('id', $line->id)->update(['spare_part_id' => $foreign->id]));
        $this->rejected(fn () => DB::table('work_order_spare_parts')->where('id', $line->id)->update(['quantity' => 0, 'subtotal' => 0]));
        $this->rejected(fn () => DB::table('work_order_spare_parts')->where('id', $line->id)->update(['subtotal' => 1]));
        $movement = InventoryMovement::sole();
        $this->rejected(fn () => DB::table('inventory_movements')->where('id', $movement->id)->update(['reason' => 'Rewrite']));
        $this->rejected(fn () => DB::table('inventory_movements')->where('id', $movement->id)->delete());
        $row = $movement->getAttributes();
        unset($row['id']);
        $this->rejected(fn () => DB::table('inventory_movements')->insert($row));
        $row['operation_key'] = (string) Str::uuid();
        $row['balance_after'] = 6;
        $this->rejected(fn () => DB::table('inventory_movements')->insert($row));
        $row['balance_after'] = 5;
        $row['workshop_id'] = $other->workshop_id;
        $this->rejected(fn () => DB::table('inventory_movements')->insert($row));
        $row['workshop_id'] = $owner->workshop_id;
        $row['type'] = 'work_order_usage';
        $row['quantity'] = 1;
        $row['balance_before'] = 5;
        $row['balance_after'] = 4;
        $row['work_order_id'] = $order->id;
        $row['work_order_spare_part_id'] = $line->id;
        DB::table('inventory_movements')->insert($row);
        $row['operation_key'] = (string) Str::uuid();
        $this->rejected(fn () => DB::table('inventory_movements')->insert($row));
        $this->assertSame(2, InventoryMovement::count());
    }

    public function test_inventory_migration_rollback_upgrade_preserves_existing_wo_and_customer_data(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner, true);
        $before = $order->fresh()->getAttributes();
        $migration = require database_path('migrations/2026_11_10_120000_create_services_and_inventory.php');
        $migration->down();
        $this->assertFalse(Schema::hasTable('inventory_movements'));
        $this->assertDatabaseHas('work_orders', ['id' => $order->id, 'number' => $order->number]);
        $migration->up();
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertDatabaseHas('users', ['id' => $owner->user_id]);
        $part = $this->part($owner);
        $this->restock($owner, $part);
        $this->assertSame(5, $part->fresh()->current_stock);
    }
}
