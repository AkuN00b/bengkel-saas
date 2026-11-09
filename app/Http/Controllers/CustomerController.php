<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerRequest;
use App\Http\Requests\CustomerSearchRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function index(CustomerSearchRequest $request): View
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $search = $request->validated('q') ?? '';
        $query = $workshop->customers()->orderBy('name')->orderBy('id');

        if ($search !== '') {
            // Escape LIKE metacharacters so searches are literal, still bound as parameters.
            $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query->where(function ($query) use ($pattern): void {
                $query->where('name', 'ilike', $pattern)->orWhere('phone', 'ilike', $pattern);
            });
        }

        return view('customers.index', [
            'activeWorkshop' => $workshop, 'search' => $search,
            'customers' => $query->paginate(20)->appends($request->safe()->only('q')),
        ]);
    }

    public function create(Request $request): View
    {
        $workshop = $request->attributes->get('activeWorkshop');
        Gate::authorize('create', [Customer::class, $workshop]);

        return view('customers.create', ['activeWorkshop' => $workshop, 'customer' => new Customer]);
    }

    public function store(CustomerRequest $request): RedirectResponse
    {
        $workshop = $request->attributes->get('activeWorkshop');
        $customer = $workshop->customers()->create($request->safe()->only(['name', 'phone', 'notes']));

        return to_route('customers.show', $customer)->with('status', 'Pelanggan berhasil ditambahkan.');
    }

    public function show(Request $request, string $customer): View
    {
        $record = $this->findCustomer($request, $customer);
        $workshop = $request->attributes->get('activeWorkshop');
        Gate::authorize('view', [$record, $workshop]);

        return view('customers.show', ['activeWorkshop' => $workshop, 'customer' => $record]);
    }

    public function edit(Request $request, string $customer): View
    {
        $record = $this->findCustomer($request, $customer);
        $workshop = $request->attributes->get('activeWorkshop');
        Gate::authorize('update', [$record, $workshop]);

        return view('customers.edit', ['activeWorkshop' => $workshop, 'customer' => $record]);
    }

    public function update(CustomerRequest $request, string $customer): RedirectResponse
    {
        $record = $this->findCustomer($request, $customer);
        Gate::authorize('update', [$record, $request->attributes->get('activeWorkshop')]);
        $record->update($request->safe()->only(['name', 'phone', 'notes']));

        return to_route('customers.show', $record)->with('status', 'Data pelanggan berhasil diperbarui.');
    }

    private function findCustomer(Request $request, string $id): Customer
    {
        abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);

        // Do not use unscoped implicit binding or trust a client-supplied workshop_id.
        return $request->attributes->get('activeWorkshop')->customers()->findOrFail($id);
    }
}
