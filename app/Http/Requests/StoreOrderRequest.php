<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Send a unique `Idempotency-Key` header (e.g. a UUID generated when the checkout opens).
 * Retrying with the same key returns the original order instead of charging twice.
 */
class StoreOrderRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'player_id' => ['nullable', 'string', 'max:64'],
            'player_name' => ['nullable', 'string', 'max:100'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            /** Values for the product's `extra_fields`, keyed by field name. */
            'extra' => ['nullable', 'array'],
            'extra.*' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function idempotencyKey(): ?string
    {
        $key = trim((string) $this->header('Idempotency-Key'));

        return $key !== '' && strlen($key) <= 100 ? $key : null;
    }
}
