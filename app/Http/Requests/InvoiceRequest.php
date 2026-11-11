<?php

namespace App\Http\Requests;

use App\Models\Invoice;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('viewAny', [Invoice::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        $workshop = $this->attributes->get('activeWorkshop');
        $orderRule = ['bail', 'nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX, Rule::exists('work_orders', 'id')->where('workshop_id', $workshop->id)];
        if ($this->isMethod('GET')) {
            return ['workshop_context' => ['nullable', 'integer', Rule::in([$workshop->id])], 'q' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'], 'status' => ['nullable', Rule::in(['all', 'draft', 'issued', 'void'])],
                'payment' => ['nullable', Rule::in(['all', 'unpaid', 'partial', 'paid', 'outstanding'])], 'work_order_id' => $orderRule,
                'page' => ['nullable', 'integer', 'min:1', 'max:1000000'], 'items_page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
                'payments_page' => ['nullable', 'integer', 'min:1', 'max:1000000'], 'events_page' => ['nullable', 'integer', 'min:1', 'max:1000000']];
        }
        $rules = ['workshop_context' => ['required', 'integer', Rule::in([$workshop->id])], 'version' => ['required', 'integer', 'min:1', 'max:2147483646'], 'operation_key' => ['required', 'uuid']];
        if ($this->routeIs('invoices.store')) {
            $orderRule[1] = 'required';

            return $rules + ['work_order_id' => $orderRule];
        }
        if ($this->routeIs('invoices.price')) {
            return $rules + ['unit_price' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX], 'reason' => ['required', 'string', 'max:1000', 'not_regex:/\x00/']];
        }
        if ($this->routeIs('invoices.void', 'invoices.reverse')) {
            return $rules + ['reason' => ['required', 'string', 'max:1000', 'not_regex:/\x00/']];
        }
        if ($this->routeIs('invoices.pay')) {
            return $rules + ['amount' => ['required', 'integer', 'min:1', 'max:'.PHP_INT_MAX], 'method' => ['required', Rule::in(['cash', 'bank_transfer', 'other'])],
                'reference' => ['nullable', 'string', 'max:255', 'not_regex:/\x00/'], 'note' => ['required_if:method,other', 'nullable', 'string', 'max:1000', 'not_regex:/\x00/']];
        }
        if ($this->routeIs('invoices.update')) {
            return $rules + ['notes' => ['nullable', 'string', 'max:5000', 'not_regex:/\x00/']];
        }
        if ($this->routeIs('invoices.refresh')) {
            return $rules + ['confirm_reset' => ['required', 'accepted']];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['reason.required' => 'Alasan wajib diisi.', 'note.required_if' => 'Isi keterangan untuk metode Lainnya.',
            'confirm_reset.accepted' => 'Konfirmasikan penggantian isi draft dan penghapusan penyesuaian harga.'];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('workshop_context')) {
            abort(409, 'Bengkel aktif berubah atau konteks form tidak valid. Buka ulang daftar invoice.');
        }
        parent::failedValidation($validator);
    }
}
