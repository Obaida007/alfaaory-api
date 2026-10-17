<?php

namespace App\Models;

use App\Enums\DepositStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id', 'full_name', 'national_id', 'details', 'id_image', 'selfie_image', 'status',
    'admin_note', 'reviewed_by', 'reviewed_at',
])]
class KycRequest extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            // pending / approved / rejected — same vocabulary as deposits.
            'status' => DepositStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
