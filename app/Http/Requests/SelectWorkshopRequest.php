<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectWorkshopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['workshop_id' => ['required', 'integer', 'min:1']];
    }
}
