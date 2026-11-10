<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use App\Support\PlateNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VehiclePrefillRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [Vehicle::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        return ['plate_number' => ['nullable', 'string', 'max:20', 'regex:'.PlateNumber::PATTERN],
            'customer_id' => ['bail', 'nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX,
                Rule::exists('customers', 'id')->where('workshop_id', $this->attributes->get('activeWorkshop')->id)]];
    }

    public function attributes(): array
    {
        return ['plate_number' => 'nomor pelat', 'customer_id' => 'pelanggan'];
    }
}
