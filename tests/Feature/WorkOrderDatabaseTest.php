<?php

namespace Tests\Feature;

use App\Actions\WorkOrders;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkshopMembership;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WorkOrderDatabaseTest extends TestCase
{
    use RefreshDatabase;

    private function expectDatabaseRejection(callable $operation): void
    {
        try {
            DB::transaction($operation);
            $this->fail('Database accepted an invalid record.');
        } catch (QueryException $exception) {
            $this->assertContains((string) $exception->getCode(), ['23505', '23503', '23514', '23001', 'P0001']);
        }
    }

    public function test_database_enforces_tenant_references_unique_numbers_active_vehicle_and_immutable_events(): void
    {
        $member = WorkshopMembership::factory()->create(['role' => 'owner']);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $other = Vehicle::factory()->create();
        $customer = Customer::factory()->create();
        $order = app(WorkOrders::class)->create($member->workshop, $member->user, ['vehicle_id' => $vehicle->id, 'intent' => 'draft']);
        $base = ['workshop_id' => $member->workshop_id, 'vehicle_id' => $vehicle->id, 'number' => 'WO-999999', 'status' => 'draft', 'version' => 1];
        $this->expectDatabaseRejection(fn () => DB::table('work_orders')->insert($base));
        $this->expectDatabaseRejection(fn () => DB::table('work_orders')->where('id', $order->id)->update(['vehicle_id' => $other->id]));
        $this->expectDatabaseRejection(fn () => DB::table('work_orders')->where('id', $order->id)->update(['customer_id' => $customer->id]));
        $this->expectDatabaseRejection(fn () => DB::table('work_orders')->where('id', $order->id)->update(['status' => 'completed']));
        $this->expectDatabaseRejection(fn () => DB::table('work_orders')->where('id', $order->id)->update(['version' => 0]));
        $this->expectDatabaseRejection(fn () => DB::table('work_order_events')->where('work_order_id', $order->id)->update(['type' => 'injected']));
        $this->expectDatabaseRejection(fn () => DB::table('work_order_events')->where('work_order_id', $order->id)->delete());
        $this->expectDatabaseRejection(fn () => DB::table('work_order_events')->insert(['workshop_id' => $other->workshop_id, 'work_order_id' => $order->id,
            'actor_id' => $member->user_id, 'type' => 'created', 'details' => '{}', 'created_at' => now()]));
        $different = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->expectDatabaseRejection(fn () => DB::table('work_orders')->insert(array_replace($base, ['vehicle_id' => $different->id, 'number' => $order->number])));
        $otherMember = WorkshopMembership::factory()->create(['workshop_id' => $other->workshop_id, 'role' => 'owner']);
        $otherOrder = app(WorkOrders::class)->create($otherMember->workshop, $otherMember->user, ['vehicle_id' => $other->id]);
        $this->assertSame($order->number, $otherOrder->number);
        $this->assertDatabaseCount('work_orders', 2);
    }

    public function test_sprint_migrations_roll_back_and_upgrade_preserving_existing_records_and_defaults(): void
    {
        $member = WorkshopMembership::factory()->create(['role' => 'owner']);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $orders = require database_path('migrations/2026_11_10_110000_create_work_orders.php');
        $inventory = require database_path('migrations/2026_11_10_120000_create_services_and_inventory.php');
        $billing = require database_path('migrations/2026_11_11_000000_create_invoices_and_payments.php');
        $billing->down();
        $inventory->down();
        $accounts = require database_path('migrations/2026_11_10_100000_add_mechanic_account_state.php');
        $orders->down();
        $accounts->down();
        $this->assertFalse(Schema::hasTable('work_orders'));
        $this->assertFalse(Schema::hasColumn('users', 'needs_password_setup'));
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id]);
        $accounts->up();
        $orders->up();
        $inventory->up();
        $billing->up();
        $this->assertTrue($member->fresh()->is_active);
        $this->assertSame(1, $member->fresh()->version);
        $this->assertFalse($member->user->fresh()->needs_password_setup);
        $this->assertSame(1, $member->workshop->fresh()->next_work_order_number);
        $this->assertDatabaseHas('vehicles', ['id' => $vehicle->id, 'plate_number' => $vehicle->plate_number]);
        $order = app(WorkOrders::class)->create($member->workshop, $member->user, ['vehicle_id' => $vehicle->id]);
        $this->assertSame('WO-000001', $order->number);
    }
}
