<?php

namespace App\Http\Resources;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Order */
class OrderResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'player_id' => $this->player_id,
            'player_name' => $this->player_name,
            'extra_data' => $this->extra_data,
            'unit_price' => $this->unit_price,
            'discount' => $this->discount,
            'total' => $this->total,
            'status' => $this->status,
            'status_label' => $this->status->getLabel(),
            'admin_note' => $this->admin_note,
            'delivered_code' => $this->when($this->status === OrderStatus::Completed, $this->delivered_code),
            'completed_at' => $this->completed_at,
            'created_at' => $this->created_at,
        ];
    }
}
