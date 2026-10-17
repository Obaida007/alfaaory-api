<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Codes are stored only as a SHA-256 hash plus the last 4 characters for display.
 */
#[Fillable(['code_hash', 'code_last4', 'amount', 'batch', 'used_by', 'used_at', 'expires_at', 'created_by'])]
class RechargeCode extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public static function normalize(string $code): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));
    }

    public static function hash(string $code): string
    {
        return hash_hmac('sha256', static::normalize($code), (string) config('app.key'));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
