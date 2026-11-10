<?php

namespace App\Actions;

use App\Enums\WorkOrderStatus;
use App\Models\InventoryMovement;
use App\Models\SparePart;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderSparePart;
use App\Models\Workshop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class Inventory
{
    public function move(Workshop $workshop, User $actor, int $partId, array $data, string $type,
        ?int $orderId = null, ?int $lineId = null, ?int $sourceId = null, bool $finalReturn = false): InventoryMovement
    {
        abort_unless(in_array($type, ['restock', 'adjustment_in', 'adjustment_out', 'work_order_usage', 'work_order_return'], true), 404);

        return DB::transaction(function () use ($workshop, $actor, $partId, $data, $type, $orderId, $lineId, $sourceId, $finalReturn): InventoryMovement {
            Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
            $order = $orderId ? WorkOrder::where('workshop_id', $workshop->id)->whereKey($orderId)->lockForUpdate()->firstOrFail() : null;
            $part = SparePart::where('workshop_id', $workshop->id)->whereKey($partId)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor)->authorize($finalReturn || str_starts_with($type, 'adjustment_') ? 'adjust' : 'stock', [$part, $workshop]);
            if ($order) {
                Gate::forUser($actor)->authorize('view', [$order, $workshop]);
            }
            $line = $lineId ? WorkOrderSparePart::where('workshop_id', $workshop->id)->where('work_order_id', $order?->id)
                ->where('spare_part_id', $part->id)->whereKey($lineId)->lockForUpdate()->firstOrFail() : null;
            if ($type === 'work_order_return' && ! $sourceId) {
                $sourceId = InventoryMovement::where('workshop_id', $workshop->id)->where('work_order_spare_part_id', $line?->id)->where('type', 'work_order_usage')->value('id');
                abort_unless($sourceId, 409, 'Pemakaian asal tidak ditemukan.');
            }
            $fingerprint = hash('sha256', json_encode(['actor' => $actor->id, 'part' => $part->id, 'order' => $orderId, 'line' => $lineId, 'source' => $sourceId,
                'type' => $type, 'final' => $finalReturn, 'quantity' => $type === 'work_order_usage' ? null : (int) $data['quantity'],
                'stock_version' => (int) ($data['stock_version'] ?? $data['version']), 'wo_version' => $order && ! $finalReturn ? (int) $data['version'] : null,
                'reason' => $data['reason'] ?? null], JSON_THROW_ON_ERROR));
            $replay = InventoryMovement::where('workshop_id', $workshop->id)->where('operation_key', $data['operation_key'])->first();
            if ($replay) {
                abort_unless(hash_equals($replay->request_fingerprint, $fingerprint), 409, 'Kunci pengiriman sudah digunakan untuk transaksi berbeda. Muat ulang form.');

                return $replay;
            }
            abort_unless($part->version === (int) ($data['stock_version'] ?? $data['version']), 409, 'Katalog atau stok berubah. Muat ulang halaman.');
            if ($order) {
                if ($finalReturn) {
                    abort_if($order->status->isActive(), 409, 'Retur WO aktif dilakukan melalui detail WO.');
                } else {
                    abort_unless($order->version === (int) $data['version'], 409, 'WO telah berubah. Muat ulang detail WO.');
                    abort_unless($order->status->isActive(), 409, 'WO sudah final. Owner dapat mencatat retur terpisah dari riwayat stok.');
                    Gate::forUser($actor)->authorize('update', [$order, $workshop]);
                }
            }
            if (in_array($type, ['restock', 'work_order_usage'], true) && ! $part->is_active) {
                throw ValidationException::withMessages(['stock' => 'Barang sudah nonaktif.']);
            }
            if ($type === 'work_order_usage') {
                abort_unless($order?->status === WorkOrderStatus::InProgress && $line?->status === 'planned', 409, 'Konfirmasi pemakaian hanya untuk rencana pada WO Dikerjakan.');
                $quantity = $line->quantity;
                $line->status = 'used';
                $line->save();
            } else {
                $quantity = (int) $data['quantity'];
            }
            abort_unless($quantity > 0 && $quantity <= 2147483647, 422, 'Jumlah harus bilangan bulat positif.');
            if (in_array($type, ['adjustment_in', 'adjustment_out', 'work_order_return'], true) && trim($data['reason'] ?? '') === '') {
                throw ValidationException::withMessages(['reason' => 'Alasan wajib diisi.']);
            }
            if ($type === 'work_order_return') {
                $source = InventoryMovement::where('workshop_id', $workshop->id)->where('spare_part_id', $part->id)
                    ->where('work_order_id', $order?->id)->where('work_order_spare_part_id', $line?->id)
                    ->where('type', 'work_order_usage')->whereKey($sourceId)->lockForUpdate()->firstOrFail();
                $returned = (int) InventoryMovement::where('workshop_id', $workshop->id)->where('source_movement_id', $source->id)->sum('quantity');
                if ($quantity > $source->quantity - $returned) {
                    throw ValidationException::withMessages(['quantity' => 'Jumlah retur melebihi sisa pemakaian yang belum dikembalikan.']);
                }
            }
            $out = in_array($type, ['work_order_usage', 'adjustment_out'], true);
            if ($out && $part->current_stock < $quantity) {
                throw ValidationException::withMessages(['stock' => 'Stok tidak cukup. Transaksi tidak disimpan.']);
            }
            if (! $out && $part->current_stock > PHP_INT_MAX - $quantity) {
                throw ValidationException::withMessages(['quantity' => 'Saldo stok melebihi batas penyimpanan.']);
            }
            $before = $part->current_stock;
            $part->current_stock = $out ? $before - $quantity : $before + $quantity;
            $part->version++;
            $part->save();
            $movement = new InventoryMovement;
            $movement->forceFill(['workshop_id' => $workshop->id, 'spare_part_id' => $part->id, 'work_order_id' => $order?->id,
                'work_order_spare_part_id' => $line?->id, 'source_movement_id' => $sourceId, 'created_by' => $actor->id, 'type' => $type, 'quantity' => $quantity,
                'balance_before' => $before, 'balance_after' => $part->current_stock, 'reason' => $data['reason'] ?? null,
                'name_snapshot' => $part->name, 'sku_snapshot' => $part->sku, 'unit_snapshot' => $part->unit, 'operation_key' => $data['operation_key'],
                'request_fingerprint' => $fingerprint, 'created_at' => now('UTC')])->save();
            if ($order && ! $finalReturn) {
                $order->version++;
                $order->save();
                WorkOrderItems::event($order, $actor, $type, ['line_id' => $line->id, 'movement_id' => $movement->id, 'quantity' => $quantity]);
            }

            return $movement;
        });
    }
}
