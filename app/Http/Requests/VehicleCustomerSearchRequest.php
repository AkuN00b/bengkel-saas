<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Gate;

class VehicleCustomerSearchRequest extends CustomerSearchRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [Vehicle::class, $this->attributes->get('activeWorkshop')]);
    }
}
