<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/** @mixin DatabaseNotification */
class NotificationResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            /** order | deposit | kyc */
            'subject' => $this->data['subject'] ?? null,
            'subject_id' => $this->data['subject_id'] ?? null,
            'status' => $this->data['status'] ?? null,
            'title' => $this->data['title'] ?? null,
            'body' => $this->data['body'] ?? null,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
