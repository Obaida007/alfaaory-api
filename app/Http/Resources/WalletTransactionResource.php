<?php

namespace App\Http\Resources;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WalletTransaction */
class WalletTransactionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'amount' => $this->amount,
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            /** order | deposit | recharge_code | admin_adjustment */
            'source_type' => $this->source_type,
            'source_id' => $this->source_type === 'admin_adjustment' ? null : $this->source_id,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
