<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends DateRangeRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('status'))) {
            $this->merge(['status' => array_values(array_filter(explode(',', $this->input('status'))))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + [
            /** One status or several, comma-separated (e.g. `pending,processing`). */
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::enum(OrderStatus::class)],
            /** Search by order number, player ID or product name. */
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }
}
