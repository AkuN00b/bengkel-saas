<?php

namespace App\Http\Controllers;

use App\Http\Requests\VehicleCustomerSearchRequest;
use App\Http\Requests\VehicleLookupRequest;
use App\Http\Requests\VehiclePrefillRequest;
use App\Http\Requests\VehicleRequest;
use App\Http\Requests\VehicleSearchRequest;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\Workshop;
use App\Support\PlateNumber;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(VehicleSearchRequest $request): View
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $search = $request->validated('q') ?? '';
        $query = $workshop->vehicles()->with('customer:id,workshop_id,name')
            ->orderBy('normalized_plate_number')->orderBy('id');
        if ($search !== '') {
            $query->where('normalized_plate_number', 'like', '%'.PlateNumber::normalize($search).'%');
        }

        return view('vehicles.index', ['activeWorkshop' => $workshop, 'search' => $search,
            'vehicles' => $query->paginate(20)->appends($request->safe()->only('q'))]);
    }

    public function lookup(VehicleLookupRequest $request): View|RedirectResponse
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $plate = PlateNumber::display($request->validated('plate_number'));
        $vehicle = $workshop->vehicles()->where('normalized_plate_number', PlateNumber::normalize($plate))->first();

        return $vehicle ? to_route('vehicles.show', $vehicle)
            : view('vehicles.lookup', ['activeWorkshop' => $workshop, 'plate' => $plate]);
    }

    public function create(VehiclePrefillRequest $request): View
    {
        $vehicle = new Vehicle($request->safe()->only(['plate_number', 'customer_id']));

        return $this->form($request, $vehicle, 'vehicles.create');
    }

    public function store(VehicleRequest $request): RedirectResponse
    {
        $vehicle = $request->attributes->get('activeWorkshop')->vehicles()->make();
        $this->persist($vehicle, $request->safe()->only(['plate_number', 'customer_id', 'brand', 'model', 'year', 'notes']));

        return to_route('vehicles.show', $vehicle)->with('status', 'Kendaraan berhasil ditambahkan.');
    }

    public function show(Request $request, string $vehicle): View
    {
        $record = $this->findVehicle($request, $vehicle);
        $workshop = $request->attributes->get('activeWorkshop');
        Gate::authorize('view', [$record, $workshop]);

        $activeOrder = WorkOrder::where('workshop_id', $workshop->id)->where('vehicle_id', $record->id)
            ->whereIn('status', ['draft', 'open', 'in_progress'])->first();

        return view('vehicles.show', ['activeWorkshop' => $workshop, 'vehicle' => $record->load('customer'), 'activeOrder' => $activeOrder]);
    }

    public function edit(Request $request, string $vehicle): View
    {
        $record = $this->findVehicle($request, $vehicle);
        Gate::authorize('update', [$record, $request->attributes->get('activeWorkshop')]);

        return $this->form($request, $record, 'vehicles.edit');
    }

    public function update(VehicleRequest $request, string $vehicle): RedirectResponse
    {
        $record = $this->findVehicle($request, $vehicle);
        Gate::authorize('update', [$record, $request->attributes->get('activeWorkshop')]);
        $this->persist($record, $request->safe()->only(['plate_number', 'customer_id', 'brand', 'model', 'year', 'notes']));

        return to_route('vehicles.show', $record)->with('status', 'Data kendaraan berhasil diperbarui.');
    }

    public function customers(VehicleCustomerSearchRequest $request): JsonResponse
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $query = $workshop->customers()->select(['id', 'name', 'phone'])->orderBy('name')->orderBy('id');
        $search = $request->validated('q') ?? '';
        if ($search !== '') {
            $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $pattern)->orWhere('phone', 'ilike', $pattern));
        }
        $customers = $query->simplePaginate(20);

        return response()->json(['customers' => $customers->items(),
            'next_page' => $customers->hasMorePages() ? $customers->currentPage() + 1 : null,
            'workshop_id' => $workshop->id]);
    }

    private function findVehicle(Request $request, string $id): Vehicle
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);

        return $request->attributes->get('activeWorkshop')->vehicles()->findOrFail($id);
    }

    private function form(Request $request, Vehicle $vehicle, string $view): View
    {
        /** @var Workshop $workshop */
        $workshop = $request->attributes->get('activeWorkshop');
        $selectedId = $request->old('customer_id', $vehicle->customer_id);
        $selected = filter_var($selectedId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false
            ? $workshop->customers()->whereKey($selectedId)->first(['id', 'name', 'phone']) : null;

        return view($view, ['activeWorkshop' => $workshop, 'vehicle' => $vehicle, 'selectedCustomer' => $selected]);
    }

    private function persist(Vehicle $vehicle, array $data): void
    {
        try {
            $vehicle->fill($data)->save();
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['plate_number' => 'Nomor pelat sudah terdaftar di bengkel ini.']);
        } catch (QueryException $exception) {
            if (! empty($data['customer_id']) && in_array((string) $exception->getCode(), ['23503', '23001'], true)) {
                throw ValidationException::withMessages(['customer_id' => 'Pelanggan tidak tersedia di bengkel ini.']);
            }
            throw $exception;
        }
    }
}
