<?php

namespace Tests\Support;

use App\Actions\Inventory;
use App\Actions\WorkOrders;
use App\Models\Service;
use App\Models\SparePart;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkshopMembership;
use Illuminate\Support\Str;

trait InventoryFixtures
{
    private function owner(): WorkshopMembership
    {
        $owner = WorkshopMembership::factory()->create(['role' => 'owner']);
        $this->actingAs($owner->user)->withSession(['active_workshop_id' => $owner->workshop_id]);

        return $owner;
    }

    private function part(WorkshopMembership $owner, array $extra = []): SparePart
    {
        $part = new SparePart;
        $part->forceFill(array_replace(['workshop_id' => $owner->workshop_id, 'name' => 'Oli Botol', 'unit' => 'botol', 'selling_price' => 50000, 'minimum_stock' => 2, 'current_stock' => 0, 'is_active' => true, 'version' => 1], $extra))->save();

        return $part;
    }

    private function service(WorkshopMembership $owner): Service
    {
        $service = new Service;
        $service->forceFill(['workshop_id' => $owner->workshop_id, 'name' => 'Ganti Oli', 'description' => 'Jasa asli', 'default_price' => 15000, 'version' => 1, 'is_active' => true])->save();

        return $service;
    }

    private function order(WorkshopMembership $owner, bool $start = false): WorkOrder
    {
        $vehicle = Vehicle::factory()->create(['workshop_id' => $owner->workshop_id]);
        $action = app(WorkOrders::class);
        $order = $action->create($owner->workshop, $owner->user, ['vehicle_id' => $vehicle->id]);
        if ($start) {
            $mechanic = WorkshopMembership::factory()->create(['workshop_id' => $owner->workshop_id, 'role' => 'mechanic']);
            $order = $action->mutate($owner->workshop, $owner->user, $order->id, ['version' => $order->version, 'assigned_mechanic_id' => $mechanic->user_id], 'assign');
            $order = $action->mutate($owner->workshop, $owner->user, $order->id, ['version' => $order->version, 'action' => 'start'], 'transition');
        }

        return $order;
    }

    private function stockData(SparePart $part, array $extra = []): array
    {
        return array_replace(['workshop_context' => $part->workshop_id, 'version' => $part->fresh()->version, 'operation_key' => (string) Str::uuid(), 'quantity' => 5, 'type' => 'restock'], $extra);
    }

    private function lineData(WorkOrder $order, array $extra = []): array
    {
        return array_replace(['workshop_context' => $order->workshop_id, 'version' => $order->fresh()->version], $extra);
    }

    private function restock(WorkshopMembership $owner, SparePart $part, int $quantity = 5): void
    {
        app(Inventory::class)->move($owner->workshop, $owner->user, $part->id, $this->stockData($part, ['quantity' => $quantity]), 'restock');
    }
}
