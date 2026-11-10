<?php

namespace App\Http\Requests;

use App\Models\Service;
use App\Models\SparePart;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class CatalogRequest extends FormRequest
{
    public function catalogClass(): string
    {
        return $this->routeIs('services.*') ? Service::class : SparePart::class;
    }

    public function authorize(): bool
    {
        return Gate::allows($this->isMethod('GET') ? 'viewAny' : 'create', [$this->catalogClass(), $this->attributes->get('activeWorkshop')]);
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->sku)) {
            $sku = trim($this->sku);
            $this->merge(['sku' => $sku === '' ? null : mb_strtoupper($sku)]);
        }
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return ['q' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'], 'status' => ['nullable', Rule::in(['active', 'inactive', 'all'])],
                'low' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1', 'max:1000000']];
        }
        $rules = ['workshop_context' => ['required', 'integer', Rule::in([$this->attributes->get('activeWorkshop')->id])]];
        if ($this->route('catalog')) {
            $rules['version'] = ['required', 'integer', 'min:1', 'max:2147483646'];
        }
        if ($this->routeIs('*.activation')) {
            return $rules + ['is_active' => ['required', 'boolean']];
        }
        $rules += ['name' => ['required', 'string', 'max:255', 'not_regex:/\x00/']];
        if ($this->catalogClass() === Service::class) {
            return $rules + ['description' => ['nullable', 'string', 'max:5000', 'not_regex:/\x00/'], 'default_price' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX]];
        }

        return $rules + ['sku' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'],
            'unit' => [$this->route('catalog') ? 'sometimes' : 'required', 'string', 'max:20', 'not_regex:/\x00/'],
            'selling_price' => ['required', 'integer', 'min:0', 'max:'.PHP_INT_MAX],
            'minimum_stock' => ['nullable', 'integer', 'min:0', 'max:'.PHP_INT_MAX]];
    }

    protected function failedValidation(Validator $validator): void
    {
        if ($validator->errors()->has('workshop_context')) {
            abort(409, 'Bengkel aktif berubah atau konteks form tidak valid. Buka ulang katalog.');
        }
        parent::failedValidation($validator);
    }
}
