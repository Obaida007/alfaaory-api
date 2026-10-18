<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepositRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
            /** Amount in the payment method's currency. */
            'amount' => ['required', 'numeric', 'gt:0', 'max:1000000', 'decimal:0,2'],
            'sender_name' => ['nullable', 'string', 'max:100'],
            'transaction_ref' => ['nullable', 'string', 'max:100'],
            'receipt_image' => ['required', 'image', 'max:5120'],
        ];
    }
}
