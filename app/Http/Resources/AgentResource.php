<?php

namespace App\Http\Resources;

use App\Models\Agent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Agent */
class AgentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'region' => $this->region,
            'phone' => $this->phone,
            'whatsapp' => $this->whatsapp,
            'whatsapp_url' => $this->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $this->whatsapp) : null,
        ];
    }
}
