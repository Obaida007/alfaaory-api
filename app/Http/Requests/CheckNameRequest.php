<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckNameRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['player_id' => ['required', 'string', 'max:64']];
    }
}
