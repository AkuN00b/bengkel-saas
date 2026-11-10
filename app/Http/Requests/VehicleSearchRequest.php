<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class VehicleSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [Vehicle::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:20', 'regex:/\A[A-Za-z0-9 ]+\z/'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000']];
    }

    public function attributes(): array
    {
        return ['q' => 'pencarian pelat', 'page' => 'halaman'];
    }
}
