<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $id = $this->user()?->id;

        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:60'],
            'last_name' => ['sometimes', 'required', 'string', 'max:60'],
            'username' => ['sometimes', 'required', 'string', 'min:3', 'max:30', 'alpha_dash', Rule::unique('users')->ignore($id)],
            'email' => ['sometimes', 'required', 'email', 'max:190', Rule::unique('users')->ignore($id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
        ];
    }
}
