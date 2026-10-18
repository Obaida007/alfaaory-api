<?php

namespace App\Models;

use App\Enums\KycStatus;
use App\Enums\OrderStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * `balance` is intentionally NOT fillable: only App\Services\WalletService may change it.
 */
#[Fillable([
    'first_name', 'last_name', 'username', 'email', 'phone', 'password', 'avatar',
    'google_id', 'telegram_chat_id', 'two_factor_enabled', 'is_banned', 'last_login_at',
    'level_id', 'debit_balance', 'kyc_status',
])]
#[Hidden(['password', 'remember_token', 'telegram_link_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    public const ADMIN_ROLES = ['super_admin', 'admin', 'support'];

    protected $attributes = [
        'balance' => '0.00',
        'debit_balance' => '0.00',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'balance' => 'decimal:2',
            'debit_balance' => 'decimal:2',
            'two_factor_enabled' => 'boolean',
            'is_banned' => 'boolean',
            'kyc_status' => KycStatus::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return ! $this->is_banned && $this->hasAnyRole(self::ADMIN_ROLES);
    }

    public function getFilamentName(): string
    {
        return $this->full_name;
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isKycApproved(): bool
    {
        return $this->kyc_status === KycStatus::Approved;
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function kycRequests(): HasMany
    {
        return $this->hasMany(KycRequest::class);
    }

    public function favoriteProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    public function totalCompletedPurchases(): string
    {
        return (string) $this->orders()->where('status', OrderStatus::Completed)->sum('total');
    }
}
