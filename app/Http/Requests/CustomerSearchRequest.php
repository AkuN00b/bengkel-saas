<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CustomerSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [Customer::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'],
            'page' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    public function attributes(): array
    {
        return ['q' => 'pencarian', 'page' => 'halaman'];
    }
}
