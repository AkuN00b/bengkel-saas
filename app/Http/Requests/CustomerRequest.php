<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Create and update share the same workshop-specific write permission.
        // Updates also authorize the scoped customer in the controller.
        return Gate::allows('create', [Customer::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'not_regex:/\x00/'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/\A(?=.*[0-9])[0-9+(). -]+\z/'],
            'notes' => ['nullable', 'string', 'max:5000', 'not_regex:/\x00/'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama pelanggan', 'phone' => 'nomor telepon', 'notes' => 'catatan'];
    }
}
