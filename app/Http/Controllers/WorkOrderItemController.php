<?php

namespace App\Http\Controllers;

use App\Actions\Inventory;
use App\Actions\WorkOrderItems;
use App\Http\Requests\WorkOrderItemRequest;
use App\Models\Service;
use App\Models\SparePart;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Models\WorkOrderSparePart;
use Illuminate\Support\Facades\Gate;

class WorkOrderItemController extends Controller
{
    private function order(WorkOrderItemRequest $request, string $id): WorkOrder
    {
        $this->id($id);
        $order = WorkOrder::where('workshop_id', $request->attributes->get('activeWorkshop')->id)->whereKey($id)->firstOrFail();
        Gate::authorize('view', [$order, $request->attributes->get('activeWorkshop')]);

        return $order;
    }

    private function id(string $id): int
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);

        return (int) $id;
    }

    public function create(WorkOrderItemRequest $request, string $work_order)
    {
        return $this->form($request, $work_order, null);
    }

    public function edit(WorkOrderItemRequest $request, string $work_order, string $line)
    {
        return $this->form($request, $work_order, $this->id($line));
    }

    private function form(WorkOrderItemRequest $request, string $id, ?int $lineId)
    {
        $order = $this->order($request, $id);
        $workshop = $request->attributes->get('activeWorkshop');
        Gate::authorize('update', [$order, $workshop]);
        $service = $request->routeIs('work-orders.services.*');
        $class = $service ? Service::class : SparePart::class;
        $lineClass = $service ? WorkOrderService::class : WorkOrderSparePart::class;
        $line = $lineId ? $lineClass::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->whereKey($lineId)->firstOrFail() : null;
        abort_if($line?->status === 'cancelled', 409, 'Baris sudah dibatalkan.');
        $selected = $line ? null : $class::where('workshop_id', $workshop->id)->where('is_active', true)->whereKey($request->validated('catalog_id'))->first();
        $query = $class::where('workshop_id', $workshop->id)->where('is_active', true);
        if ($search = $request->validated('q')) {
            $literal = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $literal)->when(! $service, fn ($q) => $q->orWhere('sku', 'ilike', $literal)));
        }

        return view('work-order-items.form', ['activeWorkshop' => $workshop, 'order' => $order, 'line' => $line, 'selected' => $selected, 'service' => $service,
            'prefix' => $service ? 'work-orders.services' : 'work-orders.parts', 'options' => $query->orderBy('name')->orderBy('id')->simplePaginate(20)->withQueryString()]);
    }

    public function store(WorkOrderItemRequest $request, string $work_order, WorkOrderItems $action)
    {
        return $this->save($request, $work_order, null, $action, false);
    }

    public function update(WorkOrderItemRequest $request, string $work_order, string $line, WorkOrderItems $action)
    {
        return $this->save($request, $work_order, $this->id($line), $action, false);
    }

    public function cancel(WorkOrderItemRequest $request, string $work_order, string $line, WorkOrderItems $action)
    {
        return $this->save($request, $work_order, $this->id($line), $action, true);
    }

    private function save(WorkOrderItemRequest $request, string $id, ?int $line, WorkOrderItems $action, bool $cancel)
    {
        $order = $this->order($request, $id);
        $action->save($request->attributes->get('activeWorkshop'), $request->user(), $order->id, $request->routeIs('work-orders.services.*'), $line, $request->validated(), $cancel);

        return to_route('work-orders.show', $order)->with('status', 'Item WO berhasil diperbarui.');
    }

    public function usePart(WorkOrderItemRequest $request, string $work_order, string $line, Inventory $action)
    {
        return $this->stock($request, $work_order, $line, $action, false);
    }

    public function returnPart(WorkOrderItemRequest $request, string $work_order, string $line, Inventory $action)
    {
        return $this->stock($request, $work_order, $line, $action, true);
    }

    private function stock(WorkOrderItemRequest $request, string $id, string $lineId, Inventory $action, bool $return)
    {
        $order = $this->order($request, $id);
        $line = WorkOrderSparePart::where('workshop_id', $order->workshop_id)->where('work_order_id', $order->id)->whereKey($this->id($lineId))->firstOrFail();
        $action->move($request->attributes->get('activeWorkshop'), $request->user(), $line->spare_part_id, $request->validated(),
            $return ? 'work_order_return' : 'work_order_usage', $order->id, $line->id);

        return to_route('work-orders.show', $order)->with('status', 'Transaksi stok berhasil dicatat.');
    }
}
