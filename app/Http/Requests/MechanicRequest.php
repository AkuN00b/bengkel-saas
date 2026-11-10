<?php

namespace App\Http\Requests;

use App\Models\WorkshopMembership;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class MechanicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [WorkshopMembership::class, $this->attributes->get('activeWorkshop')]);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) {
            $this->merge(['email' => mb_strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['page' => ['nullable', 'integer', 'min:1', 'max:1000000']];
        }
        $rules = ['workshop_context' => ['required', 'integer', Rule::in([$this->attributes->get('activeWorkshop')->id])]];
        if ($this->routeIs('mechanics.store')) {
            return $rules + ['name' => ['required', 'string', 'max:255', 'not_regex:/\x00/'],
                'email' => ['required', 'string', 'email', 'max:254', Rule::unique('users', 'email')]];
        }

        return $rules + ['version' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'is_active' => [$this->routeIs('mechanics.activation') ? 'required' : 'sometimes', 'boolean']];
    }

    public function messages(): array
    {
        return ['workshop_context.in' => 'Bengkel aktif berubah. Buka ulang halaman mekanik.',
            'email.unique' => 'Email sudah terdaftar. Gunakan email baru untuk akun mekanik.'];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('workshop_context')) {
            abort(409, 'Bengkel aktif berubah atau konteks form tidak valid. Buka ulang halaman mekanik.');
        }
        parent::failedValidation($validator);
    }
}
