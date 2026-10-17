<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKycRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:150'],
            'national_id' => ['required', 'string', 'max:50'],
            'details' => ['nullable', 'string', 'max:1000'],
            'id_image' => ['required', 'image', 'max:5120'],
            'selfie_image' => ['required', 'image', 'max:5120'],
        ];
    }
}
