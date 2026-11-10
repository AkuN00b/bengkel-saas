<?php

namespace App\Http\Controllers;

use App\Actions\Catalogs;
use App\Http\Requests\CatalogRequest;
use App\Models\InventoryMovement;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;

class CatalogController extends Controller
{
    public function index(CatalogRequest $request)
    {
        $class = $request->catalogClass();
        $workshop = $request->attributes->get('activeWorkshop');
        $query = $class::where('workshop_id', $workshop->id);
        $status = $request->validated('status') ?: 'active';
        if ($status !== 'all') {
            $query->where('is_active', $status === 'active');
        }
        if ($search = $request->validated('q')) {
            $literal = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(fn ($q) => $q->where('name', 'ilike', $literal)->when($class !== Service::class, fn ($q) => $q->orWhere('sku', 'ilike', $literal)));
        }
        if ($class !== Service::class && $request->boolean('low')) {
            $query->whereNotNull('minimum_stock')->whereColumn('current_stock', '<=', 'minimum_stock');
        }

        return view('catalogs.index', $this->context($request) + ['records' => $query->orderBy('name')->orderBy('id')->paginate(20)->withQueryString(), 'status' => $status]);
    }

    public function create(CatalogRequest $request)
    {
        Gate::authorize('create', [$request->catalogClass(), $request->attributes->get('activeWorkshop')]);
        $class = $request->catalogClass();

        return view('catalogs.form', $this->context($request) + ['record' => new $class]);
    }

    public function store(CatalogRequest $request, Catalogs $action)
    {
        $record = $action->save($request->attributes->get('activeWorkshop'), $request->user(), $request->catalogClass(), null, $request->validated());

        return to_route($this->context($request)['prefix'].'.show', $record)->with('status', 'Katalog berhasil ditambahkan.');
    }

    public function show(CatalogRequest $request, string $catalog)
    {
        $record = $this->find($request, $catalog);
        $movements = null;
        if ($request->catalogClass() !== Service::class) {
            $movements = InventoryMovement::where('workshop_id', $record->workshop_id)->where('spare_part_id', $record->id)
                ->with(['actor:id,name', 'order:id,number,status'])->withSum('returns as returned_quantity', 'quantity')->orderByDesc('id')->paginate(20);
        }

        return view('catalogs.show', $this->context($request) + ['record' => $record, 'movements' => $movements]);
    }

    public function edit(CatalogRequest $request, string $catalog)
    {
        $record = $this->find($request, $catalog);
        Gate::authorize('update', [$record, $request->attributes->get('activeWorkshop')]);

        return view('catalogs.form', $this->context($request) + ['record' => $record]);
    }

    public function update(CatalogRequest $request, string $catalog, Catalogs $action)
    {
        return $this->change($request, $catalog, $action, false);
    }

    public function activation(CatalogRequest $request, string $catalog, Catalogs $action)
    {
        return $this->change($request, $catalog, $action, true);
    }

    private function change(CatalogRequest $request, string $catalog, Catalogs $action, bool $activation)
    {
        $record = $this->find($request, $catalog);
        $action->save($request->attributes->get('activeWorkshop'), $request->user(), $request->catalogClass(), $record->id, $request->validated(), $activation);

        return to_route($this->context($request)['prefix'].'.show', $record)->with('status', 'Katalog berhasil diperbarui.');
    }

    private function find(CatalogRequest $request, string $id)
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);
        $class = $request->catalogClass();
        $record = $class::where('workshop_id', $request->attributes->get('activeWorkshop')->id)->whereKey($id)->firstOrFail();
        Gate::authorize('view', [$record, $request->attributes->get('activeWorkshop')]);

        return $record;
    }

    private function context(CatalogRequest $request): array
    {
        $service = $request->catalogClass() === Service::class;

        return ['activeWorkshop' => $request->attributes->get('activeWorkshop'), 'service' => $service, 'prefix' => $service ? 'services' : 'spare-parts', 'title' => $service ? 'Jasa' : 'Spare Part'];
    }
}
