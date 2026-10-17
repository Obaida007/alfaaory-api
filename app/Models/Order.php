<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'user_id', 'product_id', 'quantity', 'player_id', 'player_name', 'extra_data', 'unit_price', 'discount',
    'total', 'status', 'admin_note', 'provider_response', 'delivered_code', 'idempotency_key', 'completed_at',
])]
#[Hidden(['provider_response'])]
class Order extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'quantity' => 'integer',
            'status' => OrderStatus::class,
            'extra_data' => 'array',
            'provider_response' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function walletTransactions(): MorphMany
    {
        return $this->morphMany(WalletTransaction::class, 'source');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [OrderStatus::Pending, OrderStatus::Processing], true);
    }
}
