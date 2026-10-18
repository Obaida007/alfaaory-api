<?php

namespace App\Http\Resources;

use App\Models\KycRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KycRequest */
class KycRequestResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'national_id' => $this->national_id,
            'details' => $this->details,
            'status' => $this->status,
            'status_label' => $this->status->getLabel(),
            'admin_note' => $this->admin_note,
            'reviewed_at' => $this->reviewed_at,
            'created_at' => $this->created_at,
        ];
    }
}
