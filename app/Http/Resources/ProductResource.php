<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $discount = (string) ($request->user('sanctum')?->level?->discount_percent ?? '0');

        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'price' => $this->price,
            /** Price after the signed-in user's level discount (equals price for guests). */
            'final_price' => Money::sub($this->price, Money::percent($this->price, $discount)),
            'delivery_type' => $this->delivery_type,
            'requires_player_id' => $this->requires_player_id,
            'supports_name_check' => $this->supports_name_check,
            /** Extra input fields the checkout form must show, e.g. [{"name":"server","label":"السيرفر","required":true}]. */
            'extra_fields' => $this->extra_fields ?? [],
            'in_stock' => $this->stock === null || $this->stock > 0,
            'category' => new CategoryResource($this->whenLoaded('category')),
        ];
    }
}
