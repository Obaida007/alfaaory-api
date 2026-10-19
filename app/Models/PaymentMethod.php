<?php

namespace App\Models;

use App\Enums\PaymentGroup;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'subtitle', 'group', 'image', 'instructions', 'account_details', 'currency', 'exchange_rate',
    'min_amount', 'max_amount', 'fee_percent', 'requires_kyc', 'is_auto', 'is_active', 'sort',
])]
class PaymentMethod extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'group' => PaymentGroup::class,
            'account_details' => 'array',
            'exchange_rate' => 'decimal:4',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'fee_percent' => 'decimal:2',
            'requires_kyc' => 'boolean',
            'is_auto' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Converts an amount in this method's currency to USD after fees. */
    public function toUsd(string $amount): string
    {
        $usd = Money::div($amount, $this->exchange_rate);

        return Money::sub($usd, Money::percent($usd, $this->fee_percent));
    }
}
