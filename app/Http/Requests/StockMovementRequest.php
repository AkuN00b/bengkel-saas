<?php

namespace App\Http\Requests;

use App\Models\SparePart;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [SparePart::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        return ['workshop_context' => ['required', 'integer', Rule::in([$this->attributes->get('activeWorkshop')->id])],
            'version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'operation_key' => ['required', 'uuid'],
            'type' => [$this->routeIs('spare-parts.returns') ? 'exclude' : 'required', Rule::in(['restock', 'adjustment_in', 'adjustment_out'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:2147483647'],
            'reason' => [$this->routeIs('spare-parts.returns') ? 'required' : 'required_unless:type,restock', 'nullable', 'string', 'max:1000', 'not_regex:/\x00/']];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('workshop_context')) {
            abort(409, 'Bengkel aktif berubah atau konteks form tidak valid. Buka ulang stok.');
        }
        parent::failedValidation($validator);
    }
}
