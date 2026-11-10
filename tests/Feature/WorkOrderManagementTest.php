<?php

namespace Tests\Feature;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    private function login(string $role = 'owner'): WorkshopMembership
    {
        $membership = WorkshopMembership::factory()->create(['role' => $role]);
        $this->actingAs($membership->user)->withSession(['active_workshop_id' => $membership->workshop_id]);

        return $membership;
    }

    private function createOrder(WorkshopMembership $member, string $status = 'open', array $extra = []): WorkOrder
    {
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->post('/work-orders', array_replace(['workshop_context' => $member->workshop_id,
            'vehicle_id' => $vehicle->id, 'intent' => $status, 'complaint' => 'Mesin berisik'], $extra))->assertRedirect();

        return WorkOrder::where('workshop_id', $member->workshop_id)->latest('id')->firstOrFail();
    }

    private function payload(WorkOrder $order, array $extra = []): array
    {
        return array_replace(['workshop_context' => $order->workshop_id, 'version' => $order->fresh()->version], $extra);
    }

    private function assign(WorkOrder $order): WorkshopMembership
    {
        $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $order->workshop_id, 'role' => 'mechanic']);
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => $mechanic->user_id]))->assertRedirect();

        return $mechanic;
    }

    #[DataProvider('writers')]
    public function test_writer_creates_draft_or_received_order_with_server_number_and_safe_fields(string $role): void
    {
        $member = $this->login($role);
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id, 'customer_id' => $customer->id]);
        $this->get('/work-orders/create?vehicle_id='.$vehicle->id)->assertOk()->assertSee($customer->name);
        $this->post('/work-orders', ['workshop_context' => $member->workshop_id, 'vehicle_id' => $vehicle->id, 'intent' => 'open',
            'number' => 'INJECTED', 'status' => 'completed', 'workshop_id' => 999, 'version' => 900, 'assigned_mechanic_id' => $member->user_id])->assertRedirect();
        $order = WorkOrder::sole();
        $this->assertSame('WO-000001', $order->number);
        $this->assertSame(WorkOrderStatus::Open, $order->status);
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertSame($customer->name, $order->customer_name_snapshot);
        $this->assertSame($vehicle->plate_number, $order->plate_number_snapshot);
        $this->assertNull($order->assigned_mechanic_id);
        $this->assertSame(1, $order->version);
        $this->assertDatabaseCount('work_order_events', 2);
        $this->get('/vehicles/'.$vehicle->id)->assertSee('Buka WO aktif')->assertDontSee('>Buat WO<', false);
        $draft = $this->createOrder($member, 'draft', ['customer_id' => null]);
        $this->assertSame('WO-000002', $draft->number);
        $this->assertNull($draft->opened_at);
        $this->get('/work-orders')->assertOk()->assertSee($order->number)->assertSee($draft->number);
        $this->get('/work-orders/'.$order->id)->assertOk()->assertSee('Data penerimaan');
    }

    public static function writers(): array
    {
        return [['owner'], ['cashier']];
    }

    public function test_full_lifecycle_and_history_are_atomic_and_final_records_cannot_be_mutated(): void
    {
        $member = $this->login();
        $order = $this->createOrder($member, 'draft');
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'open']))->assertRedirect();
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'start']))->assertSessionHasErrors('assigned_mechanic_id');
        $this->assertSame(2, $order->fresh()->version);
        $mechanic = $this->assign($order);
        $this->actingAs($mechanic->user);
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'start']))->assertRedirect();
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'complete']))->assertRedirect();
        $order->refresh();
        $this->assertSame(WorkOrderStatus::Completed, $order->status);
        $this->assertNotNull($order->completed_at);
        $this->assertTrue($order->completed_at->greaterThanOrEqualTo($order->started_at));
        $this->assertCount(5, $order->events);
        $this->actingAs($member->user);
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['notes' => 'Forbidden']))->assertStatus(409);
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => $mechanic->user_id]))->assertStatus(409);
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'cancel', 'cancellation_reason' => 'No']))->assertStatus(409);
        $this->delete('/work-orders/'.$order->id)->assertStatus(405);
        $this->assertCount(5, $order->fresh()->events);
        $this->post('/work-orders', ['workshop_context' => $member->workshop_id, 'vehicle_id' => $order->vehicle_id, 'intent' => 'open'])->assertRedirect();
        $this->assertDatabaseCount('work_orders', 2);
        $this->assertSame('WO-000002', WorkOrder::latest('id')->first()->number);
    }

    #[DataProvider('cancellableStates')]
    public function test_only_owner_cancels_active_states_with_reason_and_history(string $state): void
    {
        $owner = $this->login();
        $order = $this->createOrder($owner, $state === 'draft' ? 'draft' : 'open');
        $mechanic = $this->assign($order);
        if ($state === 'in_progress') {
            $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'start']))->assertRedirect();
        }
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        foreach ([$cashier->user, $mechanic->user] as $user) {
            $this->actingAs($user)->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'cancel', 'cancellation_reason' => 'No']))->assertForbidden();
        }
        $this->actingAs($owner->user)->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'cancel', 'cancellation_reason' => '   ']))->assertSessionHasErrors('cancellation_reason');
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'cancel', 'cancellation_reason' => 'Pelanggan membatalkan']))->assertRedirect();
        $this->assertSame(WorkOrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('Pelanggan membatalkan', $order->fresh()->cancellation_reason);
        $this->assertNotNull($order->fresh()->cancelled_at);
    }

    public static function cancellableStates(): array
    {
        return [['draft'], ['open'], ['in_progress']];
    }

    #[DataProvider('invalidTransitions')]
    public function test_invalid_transitions_leave_status_version_and_history_unchanged(string $state, string $action): void
    {
        $member = $this->login();
        $order = $this->createOrder($member, $state === 'draft' ? 'draft' : 'open');
        $this->assign($order);
        if ($state === 'in_progress') {
            $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'start']))->assertRedirect();
        }
        $version = $order->fresh()->version;
        $count = $order->events()->count();
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => $action]))->assertStatus(in_array($state, ['draft'], true) && in_array($action, ['start', 'complete'], true) ? 403 : 409);
        $this->assertSame($version, $order->fresh()->version);
        $this->assertSame($count, $order->events()->count());
    }

    public static function invalidTransitions(): array
    {
        return [['draft', 'start'], ['draft', 'complete'], ['open', 'open'], ['open', 'complete'], ['in_progress', 'open'], ['in_progress', 'start']];
    }

    public function test_mechanic_only_reads_and_processes_own_assignments_and_cannot_escalate(): void
    {
        $owner = $this->login();
        $assigned = $this->createOrder($owner);
        $unassigned = $this->createOrder($owner);
        $mechanic = $this->assign($assigned);
        $mechanic->user->forceFill(['is_super_admin' => true])->save();
        $this->actingAs($mechanic->user);
        $this->get('/work-orders')->assertOk()->assertSee($assigned->number)->assertDontSee($unassigned->number);
        $this->get('/work-orders/'.$assigned->id)->assertOk()->assertDontSee('Ubah detail WO')->assertDontSee('Tugaskan / ganti mekanik')->assertDontSee('Batalkan WO');
        $this->get('/work-orders/'.$unassigned->id)->assertNotFound();
        $this->get('/work-orders/create')->assertForbidden();
        $this->post('/work-orders', ['workshop_context' => $owner->workshop_id, 'vehicle_id' => $unassigned->vehicle_id, 'intent' => 'open'])->assertForbidden();
        $this->put('/work-orders/'.$assigned->id, $this->payload($assigned, ['notes' => 'Escalation']))->assertForbidden();
        $this->put('/work-orders/'.$assigned->id.'/mechanic', $this->payload($assigned, ['assigned_mechanic_id' => $mechanic->user_id]))->assertForbidden();
        $this->post('/work-orders/'.$unassigned->id.'/transition', $this->payload($unassigned, ['action' => 'start']))->assertNotFound();
        $this->post('/work-orders/'.$assigned->id.'/transition', $this->payload($assigned, ['action' => 'start']))->assertRedirect();
        $this->post('/work-orders/'.$assigned->id.'/transition', $this->payload($assigned, ['action' => 'complete']))->assertRedirect();
    }

    public function test_foreign_ids_oversized_ids_stale_workshop_context_and_revoked_membership_are_rejected(): void
    {
        $member = $this->login();
        $order = $this->createOrder($member, 'draft');
        $foreign = Vehicle::factory()->create();
        $foreignCustomer = Customer::factory()->create();
        $other = WorkshopMembership::factory()->create(['role' => 'owner']);
        $this->post('/work-orders', ['workshop_context' => $member->workshop_id, 'vehicle_id' => $foreign->id, 'customer_id' => $foreignCustomer->id, 'intent' => 'open'])->assertSessionHasErrors(['vehicle_id', 'customer_id']);
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['vehicle_id' => $order->vehicle_id, 'customer_id' => $foreignCustomer->id]))->assertSessionHasErrors('customer_id');
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => $other->user_id]))->assertSessionHasErrors('assigned_mechanic_id');
        $this->get('/work-orders/99999999999999999999999999')->assertNotFound();
        $member->user->workshops()->attach($other->workshop_id, ['role' => 'owner']);
        $this->withSession(['active_workshop_id' => $other->workshop_id]);
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['vehicle_id' => $order->vehicle_id]))->assertStatus(409);
        $this->get('/work-orders/'.$order->id)->assertNotFound();
        $this->post('/work-orders', ['workshop_context' => $member->workshop_id, 'vehicle_id' => $foreign->id, 'intent' => 'open'])->assertStatus(409);
        $this->withSession(['active_workshop_id' => $member->workshop_id]);
        $member->delete();
        $this->get('/work-orders/'.$order->id)->assertRedirect(route('workshops.select'));
        $this->assertDatabaseCount('work_orders', 1);
    }

    public function test_stale_version_and_repeated_transition_do_not_duplicate_events(): void
    {
        $member = $this->login();
        $order = $this->createOrder($member, 'draft');
        $payload = $this->payload($order, ['action' => 'open']);
        $this->post('/work-orders/'.$order->id.'/transition', $payload)->assertRedirect();
        $this->post('/work-orders/'.$order->id.'/transition', $payload)->assertStatus(409)->assertSee('WO telah berubah');
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['version' => 1, 'notes' => 'Stale']))->assertStatus(409);
        $this->putJson('/work-orders/'.$order->id, $this->payload($order, ['version' => 1]))->assertStatus(409);
        $this->assertDatabaseCount('work_order_events', 2);
        $this->assertNull($order->fresh()->notes);
    }

    public function test_vehicle_and_customer_can_only_change_in_draft_and_snapshots_survive_master_edits(): void
    {
        $member = $this->login();
        $order = $this->createOrder($member, 'draft');
        $customer = Customer::factory()->create(['workshop_id' => $member->workshop_id]);
        $vehicle = Vehicle::factory()->create(['workshop_id' => $member->workshop_id]);
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['vehicle_id' => $vehicle->id, 'customer_id' => $customer->id, 'notes' => 'Penerimaan']))->assertRedirect();
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'open']))->assertRedirect();
        $mechanic = $this->assign($order);
        $order->refresh();
        $snapshots = $order->only(['plate_number_snapshot', 'customer_name_snapshot', 'customer_phone_snapshot', 'mechanic_name_snapshot']);
        $vehicle->update(['plate_number' => 'ZZ 9999 YY']);
        $customer->update(['name' => 'Changed customer', 'phone' => '08111111']);
        $mechanic->user->update(['name' => 'Changed mechanic']);
        $this->assertSame($snapshots, $order->fresh()->only(array_keys($snapshots)));
        $this->get('/work-orders/'.$order->id)->assertSee($snapshots['plate_number_snapshot'])->assertSee($snapshots['customer_name_snapshot'])->assertSee($snapshots['mechanic_name_snapshot']);
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['vehicle_id' => $vehicle->id, 'customer_id' => null]))->assertSessionHasErrors('vehicle_id');
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['notes' => 'Keluhan tambahan', 'status' => 'completed']))->assertRedirect();
        $this->assertSame(WorkOrderStatus::Open, $order->fresh()->status);
        $this->assertSame('Keluhan tambahan', $order->fresh()->notes);
    }

    public function test_pending_inactive_wrong_role_and_foreign_mechanics_cannot_be_assigned_or_process(): void
    {
        $member = $this->login();
        $order = $this->createOrder($member);
        $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $member->workshop_id, 'role' => 'mechanic']);
        $mechanic->user->forceFill(['needs_password_setup' => true])->save();
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => $mechanic->user_id]))->assertSessionHasErrors('assigned_mechanic_id');
        $mechanic->user->forceFill(['needs_password_setup' => false])->save();
        $mechanic->is_active = false;
        $mechanic->save();
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => $mechanic->user_id]))->assertSessionHasErrors('assigned_mechanic_id');
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => $member->user_id]))->assertSessionHasErrors('assigned_mechanic_id');
        $mechanic->is_active = true;
        $mechanic->save();
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => $mechanic->user_id]))->assertRedirect();
        DB::table('workshop_user')->where('id', $mechanic->id)->update(['role' => 'cashier']);
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'start']))->assertSessionHasErrors('assigned_mechanic_id');
        $this->assertSame(WorkOrderStatus::Open, $order->fresh()->status);
    }

    public function test_deactivation_requires_reassignment_or_cancellation_and_in_progress_cannot_be_unassigned(): void
    {
        $member = $this->login();
        $order = $this->createOrder($member);
        $mechanic = $this->assign($order);
        $toggle = ['workshop_context' => $member->workshop_id, 'version' => 1, 'is_active' => false];
        $this->patch('/mechanics/'.$mechanic->id.'/activation', $toggle)->assertSessionHasErrors('mechanic');
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'start']))->assertRedirect();
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->payload($order, ['assigned_mechanic_id' => null]))->assertSessionHasErrors('assigned_mechanic_id');
        $replacement = $this->assign($order);
        $this->patch('/mechanics/'.$mechanic->id.'/activation', $toggle)->assertRedirect();
        $this->assertSame($replacement->user_id, $order->fresh()->assigned_mechanic_id);
        $this->assertSame(WorkOrderStatus::InProgress, $order->fresh()->status);
        $this->actingAs($mechanic->user)->withSession(['active_workshop_id' => $member->workshop_id])->get('/work-orders/'.$order->id)->assertRedirect(route('workshops.select'));
        $this->actingAs($replacement->user)->withSession(['active_workshop_id' => $member->workshop_id])->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'complete']))->assertRedirect();
    }

    public function test_active_duplicate_number_scopes_validation_search_and_pagination(): void
    {
        $member = $this->login();
        $order = $this->createOrder($member, 'draft');
        $this->post('/work-orders', ['workshop_context' => $member->workshop_id, 'vehicle_id' => $order->vehicle_id, 'intent' => 'open'])->assertSessionHasErrors('vehicle_id');
        $this->assertDatabaseCount('work_orders', 1);
        $this->assertSame(2, $member->workshop->fresh()->next_work_order_number);
        $this->put('/work-orders/'.$order->id, $this->payload($order, ['vehicle_id' => $order->vehicle_id, 'complaint' => str_repeat('x', 5001)]))->assertSessionHasErrors('complaint');
        $this->post('/work-orders/'.$order->id.'/transition', $this->payload($order, ['action' => 'reopen']))->assertSessionHasErrors('action');
        $this->get('/work-orders?status=invalid')->assertSessionHasErrors('status');
        $this->get('/work-orders?q='.$order->number)->assertSee($order->number);
        $this->get('/work-orders?q=%25')->assertDontSee($order->number);
        $this->get('/work-orders?q='.urlencode($order->vehicle->plate_number))->assertSee($order->number);
        $this->get('/work-orders?page[]=1')->assertSessionHasErrors('page');
        $this->get('/work-orders/create?vehicle_id=999999999999999999999999')->assertSessionHasErrors('vehicle_id');
    }

    public function test_guests_and_inactive_members_cannot_access_any_work_order_or_mechanic_endpoint(): void
    {
        foreach (['/work-orders', '/work-orders/create', '/work-orders/1', '/work-orders/1/edit', '/mechanics'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
        foreach (['/work-orders', '/work-orders/1/transition', '/mechanics', '/mechanics/1/resend'] as $uri) {
            $this->post($uri)->assertRedirect(route('login'));
        }
        $member = $this->login();
        $member->is_active = false;
        $member->save();
        $this->post('/work-orders', [])->assertRedirect(route('workshops.select'));
    }
}
