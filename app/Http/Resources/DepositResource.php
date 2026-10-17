<?php

namespace App\Http\Resources;

use App\Models\Deposit;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Deposit */
class DepositResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'payment_method' => new PaymentMethodResource($this->whenLoaded('paymentMethod')),
            'amount' => $this->amount,
            'currency' => $this->currency,
            'amount_usd' => $this->amount_usd,
            'sender_name' => $this->sender_name,
            'transaction_ref' => $this->transaction_ref,
            'receipt_image_url' => Media::url($this->receipt_image),
            'status' => $this->status,
            'status_label' => $this->status->getLabel(),
            'admin_note' => $this->admin_note,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
        ];
    }
}
