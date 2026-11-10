<?php

namespace App\Http\Requests;

use App\Models\WorkOrder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WorkOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [WorkOrder::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        $workshop = $this->attributes->get('activeWorkshop');
        if ($this->isMethod('GET')) {
            return ['q' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'], 'page' => ['nullable', 'integer', 'min:1', 'max:1000000'], 'catalog_id' => ['nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX]];
        }
        $rules = ['workshop_context' => ['required', 'integer', Rule::in([$workshop->id])], 'version' => ['required', 'integer', 'min:1', 'max:2147483646']];
        if ($this->routeIs('*.use', '*.return')) {
            $rules += ['stock_version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'operation_key' => ['required', 'uuid']];
            if ($this->routeIs('*.return')) {
                $rules += ['quantity' => ['required', 'integer', 'min:1', 'max:2147483647'], 'reason' => ['required', 'string', 'max:1000', 'not_regex:/\x00/']];
            }

            return $rules;
        }
        if ($this->routeIs('*.cancel')) {
            return $rules;
        }
        $rules += ['quantity' => [$this->routeIs('*.store') ? 'required' : 'sometimes', 'integer', 'min:1', 'max:2147483647'], 'unit_price' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX]];
        if ($this->routeIs('*.store')) {
            $table = $this->routeIs('work-orders.services.*') ? 'services' : 'spare_parts';
            $rules['catalog_id'] = ['bail', 'required', 'integer', 'min:1', 'max:'.PHP_INT_MAX, Rule::exists($table, 'id')->where('workshop_id', $workshop->id)];
        }

        return $rules;
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('workshop_context')) {
            abort(409, 'Bengkel aktif berubah atau konteks form tidak valid. Buka ulang WO.');
        }
        parent::failedValidation($validator);
    }
}
