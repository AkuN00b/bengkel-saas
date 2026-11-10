<?php

namespace App\Actions;

use App\Models\Service;
use App\Models\SparePart;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;
use App\Models\WorkOrderService;
use App\Models\WorkOrderSparePart;
use App\Models\Workshop;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WorkOrderItems
{
    public function save(Workshop $workshop, User $actor, int $orderId, bool $service, ?int $lineId, array $data, bool $cancel = false): void
    {
        DB::transaction(function () use ($workshop, $actor, $orderId, $service, $lineId, $data, $cancel): void {
            Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
            $order = WorkOrder::where('workshop_id', $workshop->id)->whereKey($orderId)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize('view', [$order, $workshop]);
            abort_unless($order->version === (int) $data['version'], 409, 'WO telah berubah. Muat ulang detail WO.');
            abort_unless($order->status->isActive(), 409, 'WO sudah final dan tidak dapat diubah.');
            Gate::forUser($actor)->authorize('update', [$order, $workshop]);
            $class = $service ? WorkOrderService::class : WorkOrderSparePart::class;
            $catalogClass = $service ? Service::class : SparePart::class;
            $foreign = $service ? 'service_id' : 'spare_part_id';
            $query = $class::where('workshop_id', $workshop->id)->where('work_order_id', $order->id);
            $existing = $lineId ? (clone $query)->whereKey($lineId)->firstOrFail() : null;
            $catalog = $catalogClass::where('workshop_id', $workshop->id)->whereKey($existing?->$foreign ?? $data['catalog_id'])->lockForUpdate()->firstOrFail();
            $line = $existing ? (clone $query)->whereKey($lineId)->lockForUpdate()->firstOrFail() : new $class;
            $before = $line->only(['quantity', 'unit_price', 'subtotal', 'status']);
            if ($cancel) {
                abort_unless($line->status === ($service ? 'active' : 'planned'), 409, 'Baris tidak dapat dibatalkan. Gunakan retur untuk barang yang sudah dipakai.');
                $line->status = 'cancelled';
            } else {
                abort_if($line->status === 'cancelled', 409, 'Baris yang dibatalkan tidak dapat diubah.');
                if (! $line->exists) {
                    if (! $catalog->is_active) {
                        throw ValidationException::withMessages(['catalog_id' => 'Katalog sudah nonaktif. Pilih item aktif.']);
                    }
                    $line->forceFill(['workshop_id' => $workshop->id, 'work_order_id' => $order->id, $foreign => $catalog->id,
                        'name_snapshot' => $catalog->name, 'description_snapshot' => $service ? $catalog->description : null,
                        'status' => $service ? 'active' : 'planned']);
                    if (! $service) {
                        $line->forceFill(['sku_snapshot' => $catalog->sku, 'unit_snapshot' => $catalog->unit]);
                    }
                }
                $quantity = (int) ($data['quantity'] ?? $line->quantity);
                if (! $service && $line->status === 'used' && $quantity !== $line->quantity) {
                    throw ValidationException::withMessages(['quantity' => 'Jumlah pemakaian sudah dikunci. Retur dicatat terpisah.']);
                }
                $line->forceFill(['quantity' => $quantity, 'unit_price' => (int) $data['unit_price'], 'subtotal' => Rupiah::subtotal($quantity, (int) $data['unit_price'])]);
            }
            $line->save();
            $order->version++;
            $order->save();
            self::event($order, $actor, $service ? 'service_changed' : 'part_changed', ['line_id' => $line->id, 'name' => $line->name_snapshot,
                'before' => $before, 'after' => $line->only(['quantity', 'unit_price', 'subtotal', 'status'])]);
        });
    }

    public static function event(WorkOrder $order, User $actor, string $type, array $details): void
    {
        (new WorkOrderEvent)->forceFill(['workshop_id' => $order->workshop_id, 'work_order_id' => $order->id, 'actor_id' => $actor->id,
            'type' => $type, 'details' => $details, 'created_at' => now('UTC')])->save();
    }
}
