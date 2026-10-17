<?php

namespace App\Http\Resources;

use App\Models\PaymentMethod;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PaymentMethod */
class PaymentMethodResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'group' => $this->group,
            'image_url' => Media::url($this->image),
            'instructions' => $this->instructions,
            'account_details' => $this->account_details ?? [],
            'currency' => $this->currency,
            /** Units of `currency` per 1 USD. */
            'exchange_rate' => $this->exchange_rate,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'fee_percent' => $this->fee_percent,
            'requires_kyc' => $this->requires_kyc,
            'is_auto' => $this->is_auto,
        ];
    }
}
