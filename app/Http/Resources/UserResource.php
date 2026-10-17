<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Services\LevelService;
use App\Support\Media;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $progress = app(LevelService::class)->progress($this->resource);

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar_url' => Media::url($this->avatar),
            'balance' => $this->balance,
            'debit_balance' => $this->debit_balance,
            'kyc_status' => $this->kyc_status,
            'two_factor_enabled' => $this->two_factor_enabled,
            'telegram_linked' => filled($this->telegram_chat_id),
            'has_password' => filled($this->password),
            'level' => $progress['current'] ? new LevelResource($progress['current']) : null,
            'level_progress' => [
                'total_purchases' => $progress['total_purchases'],
                'next_level' => $progress['next'] ? new LevelResource($progress['next']) : null,
                'remaining' => $progress['remaining'],
                /** Percentage (0-100) of the way from the current level to the next. */
                'percent' => round($progress['percent'], 2),
            ],
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
        ];
    }
}
