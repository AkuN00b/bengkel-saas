<?php

namespace App\Http\Requests;

use App\Models\WorkOrder;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WorkOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [WorkOrder::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        $workshop = $this->attributes->get('activeWorkshop');
        $reference = fn (string $table) => ['bail', 'nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX,
            Rule::exists($table, 'id')->where('workshop_id', $workshop->id)];
        if ($this->isMethod('GET')) {
            return ['q' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'],
                'status' => ['nullable', Rule::in(['active', 'all', 'draft', 'open', 'in_progress', 'completed', 'cancelled'])],
                'vehicle_id' => $reference('vehicles'), 'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
                'events_page' => ['nullable', 'integer', 'min:1', 'max:1000000']];
        }
        $rules = ['workshop_context' => ['required', 'integer', Rule::in([$workshop->id])]];
        if (! $this->routeIs('work-orders.store')) {
            $rules['version'] = ['required', 'integer', 'min:1', 'max:2147483647'];
        }
        if ($this->routeIs('work-orders.store', 'work-orders.update')) {
            $rules += ['vehicle_id' => $reference('vehicles'), 'customer_id' => $reference('customers'),
                'complaint' => ['nullable', 'string', 'max:5000', 'not_regex:/\x00/'],
                'notes' => ['nullable', 'string', 'max:5000', 'not_regex:/\x00/']];
            if ($this->routeIs('work-orders.store')) {
                $rules['vehicle_id'][1] = 'required';
                $rules['intent'] = ['required', Rule::in(['draft', 'open'])];
            }
        }
        if ($this->routeIs('work-orders.assign')) {
            $rules['assigned_mechanic_id'] = ['nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX];
        }
        if ($this->routeIs('work-orders.transition')) {
            $rules['action'] = ['required', Rule::in(['open', 'start', 'complete', 'cancel'])];
            $rules['cancellation_reason'] = ['required_if:action,cancel', 'nullable', 'string', 'max:1000', 'not_regex:/\x00/'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['workshop_context.in' => 'Bengkel aktif berubah. Buka ulang form WO sebelum menyimpan.',
            'vehicle_id.required' => 'Pilih kendaraan terlebih dahulu.', 'vehicle_id.exists' => 'Kendaraan tidak tersedia di bengkel ini.',
            'customer_id.exists' => 'Pelanggan tidak tersedia di bengkel ini.', 'cancellation_reason.required_if' => 'Isi alasan pembatalan.'];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('workshop_context')) {
            abort(409, 'Bengkel aktif berubah atau konteks form tidak valid. Buka ulang daftar WO sebelum menyimpan.');
        }
        parent::failedValidation($validator);
    }
}
