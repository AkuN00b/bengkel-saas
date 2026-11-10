<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\WorkshopMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\InventoryFixtures;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use InventoryFixtures, RefreshDatabase;

    public function test_restock_adjustments_atomic_balances_and_ledger_snapshots(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner);
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part))->assertRedirect();
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part, ['type' => 'adjustment_in', 'quantity' => 2, 'reason' => 'Temuan stok fisik']))->assertRedirect();
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part, ['type' => 'adjustment_out', 'quantity' => 1, 'reason' => 'Barang rusak']))->assertRedirect();
        $this->assertSame(6, $part->fresh()->current_stock);
        $this->assertSame([0, 5, 7], InventoryMovement::orderBy('id')->pluck('balance_before')->all());
        $this->assertSame([5, 7, 6], InventoryMovement::orderBy('id')->pluck('balance_after')->all());
        $part->forceFill(['name' => 'Renamed', 'unit' => 'botol'])->save();
        $this->get('/spare-parts/'.$part->id)->assertOk()->assertSee('Oli Botol')->assertSee('Barang rusak');
        $this->assertSame('Oli Botol', InventoryMovement::first()->name_snapshot);
        $net = InventoryMovement::selectRaw("SUM(CASE WHEN type IN ('work_order_usage','adjustment_out') THEN -quantity::bigint ELSE quantity::bigint END) AS net")->value('net');
        $this->assertSame($part->fresh()->current_stock, (int) $net);
    }

    public function test_cashier_restocks_but_cannot_adjust_and_mechanic_cannot_mutate(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner);
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user);
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part))->assertRedirect();
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part, ['type' => 'adjustment_out', 'quantity' => 1, 'reason' => 'No']))->assertForbidden();
        $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'mechanic']);
        $this->actingAs($mechanic->user)->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part))->assertForbidden();
        $this->assertSame(5, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_idempotent_replays_payload_conflicts_stale_versions_and_failed_writes(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner);
        $payload = $this->stockData($part);
        $url = '/spare-parts/'.$part->id.'/movements';
        $this->post($url, $payload)->assertRedirect();
        $this->post($url, $payload)->assertRedirect();
        $this->post($url, array_replace($payload, ['quantity' => 6]))->assertStatus(409);
        $this->post($url, $this->stockData($part, ['version' => 1]))->assertStatus(409);
        $this->post($url, $this->stockData($part, ['type' => 'adjustment_out', 'quantity' => 6, 'reason' => 'Terlalu banyak']))->assertSessionHasErrors('stock');
        $this->post($url, $this->stockData($part, ['type' => 'adjustment_in', 'reason' => '']))->assertSessionHasErrors('reason');
        $this->post($url, $this->stockData($part, ['quantity' => 0]))->assertSessionHasErrors('quantity');
        $this->post($url, $this->stockData($part, ['quantity' => 1.5]))->assertSessionHasErrors('quantity');
        $this->post($url, $this->stockData($part, ['type' => 'work_order_usage']))->assertSessionHasErrors('type');
        $this->assertSame(5, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 1);
        $part->is_active = false;
        $part->save();
        $this->post($url, $this->stockData($part))->assertSessionHasErrors('stock');
        $this->post($url, $this->stockData($part, ['type' => 'adjustment_out', 'quantity' => 1, 'reason' => 'Koreksi barang nonaktif']))->assertRedirect();
    }

    public function test_foreign_ids_actor_change_and_oversized_numbers_cannot_change_stock(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner);
        $foreignOwner = WorkshopMembership::factory()->create(['role' => 'owner']);
        $foreign = $this->part($foreignOwner);
        $this->post('/spare-parts/'.$foreign->id.'/movements', $this->stockData($part))->assertNotFound();
        $this->post('/spare-parts/99999999999999999999999/movements', $this->stockData($part))->assertNotFound();
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part, ['workshop_context' => $foreignOwner->workshop_id]))->assertStatus(409);
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part, ['quantity' => 2147483648]))->assertSessionHasErrors('quantity');
        $payload = $this->stockData($part);
        $this->post('/spare-parts/'.$part->id.'/movements', $payload)->assertRedirect();
        $cashier = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'cashier']);
        $this->actingAs($cashier->user)->post('/spare-parts/'.$part->id.'/movements', $payload)->assertStatus(409);
        $this->assertSame(5, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_stock_balance_overflow_rolls_back_without_a_movement(): void
    {
        $owner = $this->owner();
        $part = $this->part($owner, ['current_stock' => PHP_INT_MAX]);
        $this->post('/spare-parts/'.$part->id.'/movements', $this->stockData($part, ['quantity' => 1]))->assertSessionHasErrors('quantity');
        $this->assertSame(PHP_INT_MAX, $part->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_movements', 0);
    }
}
