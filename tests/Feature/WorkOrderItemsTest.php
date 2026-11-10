<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\WorkOrderService;
use App\Models\WorkOrderSparePart;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\InventoryFixtures;
use Tests\TestCase;

class WorkOrderItemsTest extends TestCase
{
    use InventoryFixtures, RefreshDatabase;

    private function addPart($order, $part, int $quantity = 3): WorkOrderSparePart
    {
        $this->post('/work-orders/'.$order->id.'/parts', $this->lineData($order, ['catalog_id' => $part->id, 'quantity' => $quantity, 'unit_price' => 40000]))->assertRedirect();

        return WorkOrderSparePart::where('work_order_id', $order->id)->latest('id')->firstOrFail();
    }

    private function usageData($order, $part, array $extra = []): array
    {
        return $this->lineData($order, array_replace(['stock_version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid()], $extra));
    }

    public function test_services_snapshots_price_overrides_overflow_cancellation_and_final_lock(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner, true);
        $service = $this->service($owner);
        $url = '/work-orders/'.$order->id.'/services';
        $this->get($url.'/create')->assertOk()->assertSee('Ganti Oli');
        $this->get($url.'/create?catalog_id='.$service->id)->assertOk()->assertSee('Harga satuan (Rupiah)');
        $this->post($url, $this->lineData($order, ['catalog_id' => $service->id, 'quantity' => 2, 'unit_price' => 12000, 'subtotal' => 1, 'status' => 'cancelled', 'workshop_id' => 999]))->assertRedirect();
        $line = WorkOrderService::sole();
        $this->assertSame(24000, $line->subtotal);
        $this->assertSame('active', $line->status);
        $service->forceFill(['name' => 'New name', 'default_price' => 99999, 'description' => 'New description'])->save();
        $this->assertSame('Ganti Oli', $line->fresh()->name_snapshot);
        $this->assertSame(12000, $line->fresh()->unit_price);
        $this->put($url.'/'.$line->id, $this->lineData($order, ['quantity' => 2, 'unit_price' => PHP_INT_MAX]))->assertSessionHasErrors('unit_price');
        $this->assertSame(24000, $line->fresh()->subtotal);
        $this->put($url.'/'.$line->id, $this->lineData($order, ['quantity' => 3, 'unit_price' => 0]))->assertRedirect();
        $this->assertSame(0, $line->fresh()->subtotal);
        $this->get('/work-orders/'.$order->id)->assertOk()->assertSee('Jasa asli')->assertSee('Ganti Oli');
        $this->post($url.'/'.$line->id.'/cancel', $this->lineData($order))->assertRedirect();
        $this->put($url.'/'.$line->id, $this->lineData($order, ['unit_price' => 2]))->assertStatus(409);
        $this->post('/work-orders/'.$order->id.'/transition', $this->lineData($order, ['action' => 'complete']))->assertRedirect();
        $this->post($url, $this->lineData($order, ['catalog_id' => $service->id, 'quantity' => 1, 'unit_price' => 1]))->assertStatus(409);
        $this->assertSame(1, WorkOrderService::count());
    }

    public function test_planned_parts_no_stock_reservation_active_only_use_requires_in_progress_and_sufficient_stock(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner);
        $part = $this->part($owner);
        $this->restock($owner, $part, 2);
        $line = $this->addPart($order, $part);
        $url = '/work-orders/'.$order->id.'/parts/'.$line->id;
        $this->assertSame(2, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->post($url.'/use', $this->usageData($order, $part))->assertStatus(409);
        $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'mechanic']);
        $this->put('/work-orders/'.$order->id.'/mechanic', $this->lineData($order, ['assigned_mechanic_id' => $mechanic->user_id]))->assertRedirect();
        $this->post('/work-orders/'.$order->id.'/transition', $this->lineData($order, ['action' => 'start']))->assertRedirect();
        $this->post($url.'/use', $this->usageData($order, $part))->assertSessionHasErrors('stock');
        $this->assertSame('planned', $line->fresh()->status);
        $this->put($url, $this->lineData($order, ['quantity' => 2, 'unit_price' => 40000]))->assertRedirect();
        $part->is_active = false;
        $part->save();
        $this->post($url.'/use', $this->usageData($order, $part))->assertSessionHasErrors('stock');
        $part->is_active = true;
        $part->save();
        $data = $this->usageData($order, $part);
        $this->post($url.'/use', $data)->assertRedirect();
        $this->post($url.'/use', $data)->assertRedirect();
        $this->assertSame(0, $part->fresh()->current_stock);
        $this->assertSame('used', $line->fresh()->status);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->post($url.'/use', $this->usageData($order, $part))->assertStatus(409);
        $this->put($url, $this->lineData($order, ['quantity' => 1, 'unit_price' => 40000]))->assertSessionHasErrors('quantity');
        $this->put($url, $this->lineData($order, ['quantity' => 2, 'unit_price' => 35000]))->assertRedirect();
        $this->assertSame(70000, $line->fresh()->subtotal);
        $this->assertSame(0, $part->fresh()->current_stock);
        $this->post($url.'/cancel', $this->lineData($order))->assertStatus(409);
        $this->assertSame(2, InventoryMovement::count());
    }

    public function test_partial_returns_are_bounded_and_do_not_change_price_quantity_or_subtotal(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner, true);
        $part = $this->part($owner);
        $this->restock($owner, $part);
        $line = $this->addPart($order, $part);
        $url = '/work-orders/'.$order->id.'/parts/'.$line->id;
        $this->post($url.'/use', $this->usageData($order, $part))->assertRedirect();
        $part->is_active = false;
        $part->save();
        $data = $this->usageData($order, $part, ['quantity' => 1, 'reason' => 'Sisa barang layak stok']);
        $this->post($url.'/returns', $data)->assertRedirect();
        $this->post($url.'/returns', $data)->assertRedirect();
        $this->post($url.'/returns', $this->usageData($order, $part, ['quantity' => 3, 'reason' => 'Terlalu banyak']))->assertSessionHasErrors('quantity');
        $this->post($url.'/returns', $this->usageData($order, $part, ['quantity' => 2, 'reason' => 'Kembali layak stok']))->assertRedirect();
        $this->post($url.'/returns', $this->usageData($order, $part, ['quantity' => 1, 'reason' => 'Duplikat']))->assertSessionHasErrors('quantity');
        $this->assertSame(5, $part->fresh()->current_stock);
        $this->assertSame([3, 40000, 120000], array_values($line->fresh()->only(['quantity', 'unit_price', 'subtotal'])));
        $this->assertSame(3, (int) InventoryMovement::where('type', 'work_order_return')->sum('quantity'));
        $this->get('/work-orders/'.$order->id)->assertOk()->assertSee('Diretur seluruhnya')->assertSee('120.000');
    }

    #[DataProvider('finalStates')]
    public function test_final_returns_owner_only_preserve_final_wo_versions_events_and_lines(string $action): void
    {
        $owner = $this->owner();
        $order = $this->order($owner, true);
        $part = $this->part($owner);
        $this->restock($owner, $part);
        $line = $this->addPart($order, $part);
        $url = '/work-orders/'.$order->id.'/parts/'.$line->id;
        $this->post($url.'/use', $this->usageData($order, $part))->assertRedirect();
        $usage = InventoryMovement::where('type', 'work_order_usage')->sole();
        $returnUrl = '/spare-parts/'.$part->id.'/usages/'.$usage->id.'/returns';
        $this->post($returnUrl, $this->stockData($part, ['quantity' => 1, 'reason' => 'Too soon']))->assertStatus(409);
        $this->post('/work-orders/'.$order->id.'/transition', $this->lineData($order, ['action' => $action, 'cancellation_reason' => 'Pelanggan batal']))->assertRedirect();
        $before = $order->fresh()->getAttributes();
        $events = $order->events()->count();
        $beforeLine = $line->fresh()->getAttributes();
        $this->post($url.'/returns', $this->usageData($order, $part, ['quantity' => 1, 'reason' => 'Not here']))->assertStatus(409);
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user)->post($returnUrl, $this->stockData($part, ['quantity' => 1, 'reason' => 'No permission']))->assertForbidden();
        $this->actingAs($owner->user);
        $payload = $this->stockData($part, ['quantity' => 1, 'reason' => 'Barang kembali setelah final']);
        $this->post($returnUrl, $payload)->assertRedirect();
        $this->post($returnUrl, $payload)->assertRedirect();
        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame($events, $order->events()->count());
        $this->assertSame($beforeLine, $line->fresh()->getAttributes());
        $this->assertSame(3, $part->fresh()->current_stock);
        $this->get('/spare-parts/'.$part->id)->assertOk()->assertSee('Retur WO final');
    }

    public static function finalStates(): array
    {
        return [['complete'], ['cancel']];
    }

    public function test_completion_requires_resolving_plans_and_cancellation_does_not_restore_used_stock(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner, true);
        $part = $this->part($owner);
        $this->restock($owner, $part);
        $used = $this->addPart($order, $part, 1);
        $this->post('/work-orders/'.$order->id.'/parts/'.$used->id.'/use', $this->usageData($order, $part))->assertRedirect();
        $planned = $this->addPart($order, $part, 2);
        $version = $order->fresh()->version;
        $this->post('/work-orders/'.$order->id.'/transition', $this->lineData($order, ['action' => 'complete']))->assertSessionHasErrors('parts');
        $this->assertSame($version, $order->fresh()->version);
        $this->post('/work-orders/'.$order->id.'/transition', $this->lineData($order, ['action' => 'cancel', 'cancellation_reason' => 'Pelanggan batal']))->assertRedirect();
        $this->assertSame('cancelled', $planned->fresh()->status);
        $this->assertSame('used', $used->fresh()->status);
        $this->assertSame(4, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertSame(1, $order->events()->where('type', 'plans_cancelled')->count());
    }

    public function test_role_matrix_tenant_line_tampering_inactive_master_and_stale_context(): void
    {
        $owner = $this->owner();
        $order = $this->order($owner, true);
        $part = $this->part($owner);
        $service = $this->service($owner);
        $line = $this->addPart($order, $part, 1);
        $base = '/work-orders/'.$order->id;
        $foreignOwner = WorkshopMembership::factory()->create(['role' => 'owner']);
        $foreignPart = $this->part($foreignOwner);
        $foreignOrder = $this->order($foreignOwner);
        $this->post($base.'/parts', $this->lineData($order, ['catalog_id' => $foreignPart->id, 'quantity' => 1, 'unit_price' => 1]))->assertSessionHasErrors('catalog_id');
        $this->get($base.'/parts/create?catalog_id='.$foreignPart->id)->assertOk()->assertDontSee('name="catalog_id"', false);
        $this->put('/work-orders/'.$foreignOrder->id.'/parts/'.$line->id, $this->lineData($order, ['unit_price' => 1]))->assertNotFound();
        $this->put($base.'/parts/999999999999999999999', $this->lineData($order, ['unit_price' => 1]))->assertNotFound();
        $part->is_active = false;
        $part->save();
        $this->post($base.'/parts', $this->lineData($order, ['catalog_id' => $part->id, 'quantity' => 1, 'unit_price' => 1]))->assertSessionHasErrors('catalog_id');
        $this->put($base.'/parts/'.$line->id, $this->lineData($order, ['version' => 1, 'unit_price' => 1]))->assertStatus(409);
        $this->put($base.'/parts/'.$line->id, $this->lineData($order, ['workshop_context' => $foreignOwner->workshop_id, 'unit_price' => 1]))->assertStatus(409);
        $mechanic = WorkshopMembership::where('workshop_id', $owner->workshop_id)->where('role', 'mechanic')->sole();
        $this->actingAs($mechanic->user)->get($base)->assertOk()->assertSee('Oli Botol')->assertDontSee('Tambah jasa WO')->assertDontSee('Tambah spare part WO');
        $this->post($base.'/services', $this->lineData($order, ['catalog_id' => $service->id, 'quantity' => 1, 'unit_price' => 1]))->assertForbidden();
        $this->post($base.'/parts/'.$line->id.'/use', $this->usageData($order, $part))->assertForbidden();
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user)->post($base.'/services', $this->lineData($order, ['catalog_id' => $service->id, 'quantity' => 1, 'unit_price' => 1]))->assertRedirect();
        $cashier->is_active = false;
        $cashier->save();
        $this->post($base.'/services', $this->lineData($order, ['catalog_id' => $service->id, 'quantity' => 1, 'unit_price' => 1]))->assertRedirect(route('workshops.select'));
    }

    public function test_guests_cannot_access_catalog_stock_or_items(): void
    {
        foreach (['/services', '/services/create', '/services/1', '/spare-parts', '/spare-parts/1', '/work-orders/1/parts/create'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        foreach (['/services', '/spare-parts', '/spare-parts/1/movements', '/work-orders/1/parts', '/work-orders/1/parts/1/use', '/work-orders/1/parts/1/returns', '/spare-parts/1/usages/1/returns'] as $url) {
            $this->post($url)->assertRedirect(route('login'));
        }
    }
}
