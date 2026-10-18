<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use Illuminate\Validation\Rule;

class OrderIndexRequest extends DateRangeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + [
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
            /** Search by order number, player ID or product name. */
            'q' => ['nullable', 'string', 'max:100'],
        ];
    }
}
