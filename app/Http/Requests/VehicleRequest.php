<?php

namespace App\Http\Requests;

use App\Models\Vehicle;
use App\Support\PlateNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', [Vehicle::class, $this->attributes->get('activeWorkshop')]);
    }

    public function rules(): array
    {
        return [
            // Detect stale forms; membership and tenant ownership are still checked separately.
            'workshop_context' => ['required', 'integer', Rule::in([$this->attributes->get('activeWorkshop')->id])],
            'plate_number' => ['required', 'string', 'max:20', 'regex:'.PlateNumber::PATTERN],
            'customer_id' => ['bail', 'nullable', 'integer', 'min:1', 'max:'.PHP_INT_MAX,
                Rule::exists('customers', 'id')->where('workshop_id', $this->attributes->get('activeWorkshop')->id)],
            'brand' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'],
            'model' => ['nullable', 'string', 'max:100', 'not_regex:/\x00/'],
            'year' => ['nullable', 'integer', 'min:1900', 'max:'.min(2100, now()->year + 1)],
            'notes' => ['nullable', 'string', 'max:5000', 'not_regex:/\x00/'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('plate_number')) {
                return;
            }
            $query = $this->attributes->get('activeWorkshop')->vehicles()
                ->where('normalized_plate_number', PlateNumber::normalize($this->input('plate_number')));
            $id = $this->route('vehicle');
            if ($id !== null) {
                abort_if(filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false, 404);
                $query->whereKeyNot((int) $id);
            }
            if ($query->exists()) {
                $validator->errors()->add('plate_number', 'Nomor pelat sudah terdaftar di bengkel ini.');
            }
        }];
    }

    public function attributes(): array
    {
        return ['workshop_context' => 'konteks bengkel', 'plate_number' => 'nomor pelat', 'customer_id' => 'pelanggan', 'brand' => 'merek',
            'model' => 'model', 'year' => 'tahun', 'notes' => 'catatan'];
    }

    public function messages(): array
    {
        return ['workshop_context.in' => 'Bengkel aktif berubah. Buka ulang form kendaraan sebelum menyimpan.',
            'plate_number.regex' => 'Nomor pelat harus berisi huruf A–Z dan angka; hanya spasi yang boleh sebagai pemisah.',
            'customer_id.exists' => 'Pelanggan tidak tersedia di bengkel ini.'];
    }
}
