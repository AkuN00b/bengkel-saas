<?php

namespace App\Http\Controllers;

use App\Actions\WorkOrders;
use App\Enums\WorkOrderStatus;
use App\Http\Requests\WorkOrderRequest;
use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Models\WorkOrderSparePart;
use App\Models\WorkshopMembership;
use App\Support\PlateNumber;
use Illuminate\Support\Facades\Gate;

class WorkOrderController extends Controller
{
    public function index(WorkOrderRequest $request)
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $query = $this->visible($request)->with(['vehicle', 'customer']);
        $status = $request->validated('status') ?: 'active';
        if ($status === 'active') {
            $query->whereIn('status', ['draft', 'open', 'in_progress']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($search = $request->validated('q')) {
            $literal = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $plate = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], PlateNumber::normalize($search)).'%';
            $query->where(fn ($q) => $q->where('number', 'ilike', $literal)
                ->orWhereRaw("replace(plate_number_snapshot, ' ', '') ILIKE ?", [$plate])
                ->orWhere(fn ($draft) => $draft->where('status', 'draft')->whereHas('vehicle', fn ($v) => $v->where('normalized_plate_number', 'ilike', $plate))));
        }

        return view('work-orders.index', ['activeWorkshop' => $workshop, 'orders' => $query->orderByDesc('id')->paginate(20)->withQueryString(),
            'status' => $status, 'search' => $request->validated('q') ?? '']);
    }

    public function create(WorkOrderRequest $request)
    {
        Gate::authorize('create', [WorkOrder::class, $request->attributes->get('activeWorkshop')]);

        return $this->form($request, new WorkOrder);
    }

    public function store(WorkOrderRequest $request, WorkOrders $action)
    {
        $order = $action->create($request->attributes->get('activeWorkshop'), $request->user(), $request->validated());

        return to_route('work-orders.show', $order)->with('status', 'WO berhasil disimpan.');
    }

    public function show(WorkOrderRequest $request, string $work_order)
    {
        $order = $this->find($request, $work_order);
        $workshop = $request->attributes->get('activeWorkshop');
        $mechanics = null;
        if (Gate::allows('assign', [$order, $workshop])) {
            $mechanics = WorkshopMembership::where('workshop_id', $workshop->id)->where('role', 'mechanic')
                ->where('is_active', true)->whereHas('user', fn ($q) => $q->where('needs_password_setup', false)
                ->when($request->validated('q'), fn ($query, $search) => $query->where('name', 'ilike', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%')))
                ->with('user')->orderBy('id')->simplePaginate(10)->withQueryString();
        }

        return view('work-orders.show', ['activeWorkshop' => $workshop, 'order' => $order->load(['vehicle', 'customer']),
            'serviceLines' => WorkOrderService::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->orderBy('id')->paginate(10, ['*'], 'services_page')->withQueryString(),
            'partLines' => WorkOrderSparePart::where('workshop_id', $workshop->id)->where('work_order_id', $order->id)->with('catalog')
                ->withSum(['movements as returned_quantity' => fn ($q) => $q->where('type', 'work_order_return')], 'quantity')
                ->orderBy('id')->paginate(10, ['*'], 'parts_page')->withQueryString(),
            'mechanics' => $mechanics, 'events' => $order->events()->with('actor:id,name')->orderByDesc('id')->paginate(10, ['*'], 'events_page')->withQueryString()]);
    }

    public function edit(WorkOrderRequest $request, string $work_order)
    {
        $order = $this->find($request, $work_order);
        Gate::authorize('update', [$order, $request->attributes->get('activeWorkshop')]);

        return $this->form($request, $order);
    }

    public function update(WorkOrderRequest $request, string $work_order, WorkOrders $action)
    {
        return $this->mutate($request, $work_order, $action, 'update');
    }

    public function transition(WorkOrderRequest $request, string $work_order, WorkOrders $action)
    {
        return $this->mutate($request, $work_order, $action, 'transition');
    }

    public function assign(WorkOrderRequest $request, string $work_order, WorkOrders $action)
    {
        return $this->mutate($request, $work_order, $action, 'assign');
    }

    private function mutate(WorkOrderRequest $request, string $id, WorkOrders $action, string $operation)
    {
        $order = $this->find($request, $id);
        $action->mutate($request->attributes->get('activeWorkshop'), $request->user(), $order->id, $request->validated(), $operation);

        return to_route('work-orders.show', $order)->with('status', 'WO berhasil diperbarui.');
    }

    private function visible(WorkOrderRequest $request)
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $query = WorkOrder::where('workshop_id', $workshop->id);
        if (! Gate::allows('create', [WorkOrder::class, $workshop])) {
            $query->where('assigned_mechanic_id', $request->user()->id);
        }

        return $query;
    }

    private function find(WorkOrderRequest $request, string $id): WorkOrder
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);
        $record = $this->visible($request)->whereKey($id)->firstOrFail();
        Gate::authorize('view', [$record, $request->attributes->get('activeWorkshop')]);

        return $record;
    }

    private function form(WorkOrderRequest $request, WorkOrder $order)
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $vehicleId = $request->old('vehicle_id', $request->validated('vehicle_id') ?? $order->vehicle_id);
        if ($order->exists && $order->status !== WorkOrderStatus::Draft) {
            $vehicleId = $order->vehicle_id;
        }
        $vehicle = filter_var($vehicleId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
            ? $workshop->vehicles()->with('customer')->find($vehicleId) : null;
        $customerId = $request->old('customer_id', $order->exists && $order->vehicle_id === $vehicle?->id ? $order->customer_id : $vehicle?->customer_id);
        $selected = filter_var($customerId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
            ? $workshop->customers()->find($customerId, ['id', 'name', 'phone']) : null;
        $vehicles = null;
        if (! $order->exists || $order->status === WorkOrderStatus::Draft) {
            $vehicles = $workshop->vehicles()->when($request->validated('q'), function ($q, $search): void {
                $plate = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], PlateNumber::normalize($search));
                $q->where('normalized_plate_number', 'ilike', '%'.$plate.'%');
            })->orderBy('normalized_plate_number')->orderBy('id')->simplePaginate(10)->withQueryString();
        }

        return view('work-orders.form', ['activeWorkshop' => $workshop, 'order' => $order, 'vehicle' => $vehicle,
            'selectedCustomer' => $selected, 'vehicles' => $vehicles]);
    }
}
