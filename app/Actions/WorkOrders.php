<?php

namespace App\Actions;

use App\Enums\WorkOrderStatus;
use App\Models\Customer;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderEvent;
use App\Models\WorkOrderSparePart;
use App\Models\Workshop;
use App\Models\WorkshopMembership;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WorkOrders
{
    public function create(Workshop $workshop, User $actor, array $data): WorkOrder
    {
        try {
            return DB::transaction(function () use ($workshop, $actor, $data): WorkOrder {
                $locked = Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($actor)->authorize('create', [WorkOrder::class, $locked]);
                $vehicle = $this->vehicle($locked, $data['vehicle_id']);
                $customerId = array_key_exists('customer_id', $data) ? $data['customer_id'] : $vehicle->customer_id;
                $customer = $this->customer($locked, $customerId);
                if (WorkOrder::where('workshop_id', $locked->id)->where('vehicle_id', $vehicle->id)
                    ->whereIn('status', ['draft', 'open', 'in_progress'])->exists()) {
                    throw ValidationException::withMessages(['vehicle_id' => 'Kendaraan masih memiliki WO aktif. Buka pekerjaan tersebut terlebih dahulu.']);
                }
                $number = $locked->next_work_order_number;
                $locked->next_work_order_number = $number + 1;
                $locked->save();
                $order = new WorkOrder;
                $order->forceFill(['workshop_id' => $locked->id, 'vehicle_id' => $vehicle->id,
                    'customer_id' => $customer?->id, 'number' => 'WO-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT),
                    'status' => WorkOrderStatus::Draft, 'complaint' => $data['complaint'] ?? null,
                    'notes' => $data['notes'] ?? null, 'version' => 1])->save();
                $this->event($order, $actor, 'created', ['status' => 'draft']);
                if (($data['intent'] ?? 'open') === 'open') {
                    $this->receive($order, $vehicle, $customer);
                    $order->save();
                    $this->event($order, $actor, 'status_changed', ['from' => 'draft', 'to' => 'open']);
                }

                return $order;
            });
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['vehicle_id' => 'Kendaraan sudah mempunyai WO aktif. Muat ulang halaman.']);
        }
    }

    public function mutate(Workshop $workshop, User $actor, int $id, array $data, string $operation): WorkOrder
    {
        return DB::transaction(function () use ($workshop, $actor, $id, $data, $operation): WorkOrder {
            Workshop::whereKey($workshop->id)->lockForUpdate()->firstOrFail();
            $order = WorkOrder::where('workshop_id', $workshop->id)->whereKey($id)->lockForUpdate()->firstOrFail();
            // Scope mechanics before returning version/status errors, so IDs disclose nothing.
            Gate::forUser($actor)->authorize('view', [$order, $workshop]);
            abort_unless($order->version === (int) $data['version'], 409, 'WO telah berubah. Muat ulang detail WO sebelum menyimpan lagi.');
            abort_unless($order->status->isActive(), 409, 'WO sudah final dan tidak dapat diubah.');
            if ($operation === 'update') {
                Gate::forUser($actor)->authorize('update', [$order, $workshop]);
                $before = $order->only(['vehicle_id', 'customer_id', 'complaint', 'notes']);
                if ($order->status === WorkOrderStatus::Draft) {
                    if (empty($data['vehicle_id'])) {
                        throw ValidationException::withMessages(['vehicle_id' => 'Pilih kendaraan terlebih dahulu.']);
                    }
                    $vehicle = $this->vehicle($workshop, $data['vehicle_id']);
                    $order->vehicle_id = $vehicle->id;
                    $order->customer_id = $this->customer($workshop, $data['customer_id'] ?? null)?->id;
                } else {
                    if ((isset($data['vehicle_id']) && (int) $data['vehicle_id'] !== $order->vehicle_id)
                        || (array_key_exists('customer_id', $data) && ($data['customer_id'] === null ? null : (int) $data['customer_id']) !== $order->customer_id)) {
                        throw ValidationException::withMessages(['vehicle_id' => 'Kendaraan dan pelanggan hanya dapat diganti saat Draft.']);
                    }
                }
                $order->complaint = $data['complaint'] ?? null;
                $order->notes = $data['notes'] ?? null;
                $changed = array_keys(array_diff_assoc($order->only(array_keys($before)), $before));
                $event = ['details_changed', ['fields' => $changed]];
            } elseif ($operation === 'assign') {
                Gate::forUser($actor)->authorize('assign', [$order, $workshop]);
                $mechanic = empty($data['assigned_mechanic_id']) ? null : $this->mechanic($workshop, (int) $data['assigned_mechanic_id']);
                if (! $mechanic && $order->status === WorkOrderStatus::InProgress) {
                    throw ValidationException::withMessages(['assigned_mechanic_id' => 'Pekerjaan Dikerjakan harus tetap memiliki mekanik aktif.']);
                }
                $event = ['mechanic_assigned', ['from' => $order->assigned_mechanic_id, 'to' => $mechanic?->id,
                    'name' => $mechanic?->name]];
                $order->assigned_mechanic_id = $mechanic?->id;
                $order->mechanic_name_snapshot = $mechanic?->name;
            } else {
                $action = $data['action'];
                Gate::forUser($actor)->authorize($action === 'cancel' ? 'cancel' : ($action === 'open' ? 'update' : 'process'), [$order, $workshop]);
                $from = $order->status;
                if ($action === 'cancel') {
                    $planned = WorkOrderSparePart::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->where('status', 'planned');
                    $cancelledPlans = (clone $planned)->count();
                    $planned->update(['status' => 'cancelled', 'updated_at' => now('UTC')]);
                    if ($cancelledPlans > 0) {
                        WorkOrderItems::event($order, $actor, 'plans_cancelled', ['count' => $cancelledPlans]);
                    }
                    $order->status = WorkOrderStatus::Cancelled;
                    $order->cancelled_at = now('UTC');
                    $order->cancellation_reason = $data['cancellation_reason'];
                    app(Billing::class)->cancelDraftForWorkOrder($order, $actor);
                } elseif ($action === 'open' && $from === WorkOrderStatus::Draft) {
                    $this->receive($order, $this->vehicle($workshop, $order->vehicle_id), $this->customer($workshop, $order->customer_id));
                } elseif ($action === 'start' && $from === WorkOrderStatus::Open) {
                    $this->mechanic($workshop, $order->assigned_mechanic_id);
                    $order->status = WorkOrderStatus::InProgress;
                    $order->started_at = now('UTC');
                } elseif ($action === 'complete' && $from === WorkOrderStatus::InProgress) {
                    if (WorkOrderSparePart::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->where('status', 'planned')->exists()) {
                        throw ValidationException::withMessages(['parts' => 'Masih ada spare part Rencana. Minta owner/kasir mengonfirmasi pemakaian atau membatalkan rencana sebelum menyelesaikan WO.']);
                    }
                    $this->mechanic($workshop, $order->assigned_mechanic_id);
                    $order->status = WorkOrderStatus::Completed;
                    $order->completed_at = now('UTC');
                } else {
                    abort(409, 'Perubahan status tidak tersedia. Muat ulang detail WO.');
                }
                $event = ['status_changed', ['from' => $from->value, 'to' => $order->status->value]];
            }
            $order->version++;
            try {
                $order->save();
            } catch (UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['vehicle_id' => 'Kendaraan sudah mempunyai WO aktif.']);
            }
            $this->event($order, $actor, ...$event);

            return $order;
        });
    }

    public function mechanic(Workshop $workshop, ?int $id): User
    {
        $membership = WorkshopMembership::where('workshop_id', $workshop->id)->where('user_id', $id)
            ->where('role', 'mechanic')->where('is_active', true)->lockForUpdate()->first();
        $user = $membership ? User::whereKey($id)->lockForUpdate()->first() : null;
        if (! $user || $user->needs_password_setup) {
            throw ValidationException::withMessages(['assigned_mechanic_id' => 'Pilih mekanik aktif yang sudah membuat sandi sebelum memulai pekerjaan.']);
        }

        return $user;
    }

    private function vehicle(Workshop $workshop, int $id): Vehicle
    {
        return Vehicle::where('workshop_id', $workshop->id)->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function customer(Workshop $workshop, ?int $id): ?Customer
    {
        return $id === null ? null : Customer::where('workshop_id', $workshop->id)->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    private function receive(WorkOrder $order, Vehicle $vehicle, ?Customer $customer): void
    {
        $order->forceFill(['status' => WorkOrderStatus::Open, 'opened_at' => now('UTC'),
            'plate_number_snapshot' => $vehicle->plate_number, 'customer_name_snapshot' => $customer?->name,
            'customer_phone_snapshot' => $customer?->phone]);
    }

    private function event(WorkOrder $order, User $actor, string $type, array $details): void
    {
        (new WorkOrderEvent)->forceFill(['workshop_id' => $order->workshop_id, 'work_order_id' => $order->id,
            'actor_id' => $actor->id, 'type' => $type, 'details' => $details, 'created_at' => now('UTC')])->save();
    }
}
