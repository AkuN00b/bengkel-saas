<?php

namespace App\Http\Requests;

use App\Support\PlateNumber;

class VehicleLookupRequest extends VehicleSearchRequest
{
    public function rules(): array
    {
        return ['plate_number' => ['required', 'string', 'max:20', 'regex:'.PlateNumber::PATTERN]];
    }

    public function attributes(): array
    {
        return ['plate_number' => 'nomor pelat'];
    }
}
