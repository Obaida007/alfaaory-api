<?php

namespace App\Services;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Exceptions\InsufficientBalanceException;
use App\Jobs\FulfillOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\StatusNotification;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        protected WalletService $wallet,
        protected LevelService $levels,
    ) {}

    /**
     * Buys a product from the wallet balance: lock user → check balance → create order → debit, atomically.
     * Replaying the same idempotency key returns the original order without charging again.
     *
     * @param  array{player_id?: ?string, player_name?: ?string, quantity?: int, extra?: ?array<string, mixed>}  $data
     * @return array{0: Order, 1: bool} the order and whether it was created by this call
     */
    public function place(User $user, Product $product, array $data, string $idempotencyKey): array
    {
        if ($existing = $this->findByKey($user, $idempotencyKey)) {
            return [$existing, false];
        }

        $this->assertPurchasable($product, $data);

        try {
            [$order, $created] = DB::transaction(function () use ($user, $product, $data, $idempotencyKey) {
                $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                // A concurrent request with the same key may have committed while we waited for the lock.
                if ($existing = $this->findByKey($user, $idempotencyKey)) {
                    return [$existing, false];
                }

                $quantity = max(1, (int) ($data['quantity'] ?? 1));

                if ($product->stock !== null) {
                    $stock = Product::query()->whereKey($product->getKey())->lockForUpdate()->value('stock');
                    if ($stock < $quantity) {
                        throw new BusinessException(__('store.out_of_stock'));
                    }
                    Product::query()->whereKey($product->getKey())->decrement('stock', $quantity);
                }

                $subtotal = Money::mul($product->price, $quantity);
                $discount = Money::percent($subtotal, $this->levels->discountPercent($lockedUser->loadMissing('level')));
                $total = Money::sub($subtotal, $discount);

                if (Money::cmp($lockedUser->balance, $total) < 0) {
                    throw new InsufficientBalanceException;
                }

                $order = Order::create([
                    'user_id' => $user->getKey(),
                    'product_id' => $product->getKey(),
                    'quantity' => $quantity,
                    'player_id' => $data['player_id'] ?? null,
                    'player_name' => $data['player_name'] ?? null,
                    'extra_data' => $data['extra'] ?? null,
                    'unit_price' => $product->price,
                    'discount' => $discount,
                    'total' => $total,
                    'status' => OrderStatus::Pending,
                    'idempotency_key' => $idempotencyKey,
                ]);

                $this->wallet->debit($user, $total, $order, __('store.tx.order_purchase', ['id' => $order->id, 'product' => $this->label($product)]));

                return [$order, true];
            });
        } catch (UniqueConstraintViolationException $e) {
            if ($existing = $this->findByKey($user, $idempotencyKey)) {
                return [$existing, false];
            }
            throw $e;
        }

        if ($created && $product->delivery_type === DeliveryType::Auto) {
            FulfillOrder::dispatch($order);
        }

        return [$order->fresh(['product']), $created];
    }

    public function complete(Order $order, ?User $admin = null, ?string $note = null, ?string $deliveredCode = null): Order
    {
        $order = DB::transaction(function () use ($order, $note, $deliveredCode) {
            $locked = $this->lockOpen($order);
            $locked->fill([
                'status' => OrderStatus::Completed,
                'completed_at' => now(),
                'admin_note' => $note ?? $locked->admin_note,
                'delivered_code' => $deliveredCode ?? $locked->delivered_code,
            ])->save();

            return $locked;
        });

        $this->levels->sync($order->user);
        $order->user->notify(StatusNotification::forOrder($order));

        return $order;
    }

    /** Rejects an open order and returns the money to the wallet (final status: refunded). */
    public function reject(Order $order, ?User $admin = null, ?string $note = null): Order
    {
        $order = DB::transaction(function () use ($order, $admin, $note) {
            $locked = $this->lockOpen($order);
            $this->wallet->refund($locked, null, $admin);
            $locked->fill([
                'status' => OrderStatus::Refunded,
                'admin_note' => $note ?? $locked->admin_note,
            ])->save();

            return $locked;
        });

        $order->user->notify(StatusNotification::forOrder($order));

        return $order;
    }

    public function markProcessing(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $locked = $this->lockOpen($order);
            $locked->update(['status' => OrderStatus::Processing]);

            return $locked;
        });
    }

    /**
     * "PUBG GLOBAL AUTO 60 UC" wrapped in Unicode isolates so Latin names keep their order inside Arabic text.
     */
    protected function label(Product $product): string
    {
        return "\u{2068}".trim(($product->category?->name ?? '').' '.$product->name)."\u{2069}";
    }

    protected function lockOpen(Order $order): Order
    {
        $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

        if (! $locked->isOpen()) {
            throw new BusinessException(__('store.order_not_open'));
        }

        return $locked;
    }

    protected function findByKey(User $user, string $key): ?Order
    {
        $order = Order::query()->where('idempotency_key', $key)->first();

        if ($order && $order->user_id !== $user->getKey()) {
            throw new BusinessException(__('store.idempotency_conflict'), 409, 'idempotency_conflict');
        }

        return $order?->load('product');
    }

    /** @param  array<string, mixed>  $data */
    protected function assertPurchasable(Product $product, array $data): void
    {
        if (! $product->is_active || ! $product->category?->is_active) {
            throw new BusinessException(__('store.product_unavailable'));
        }

        if ($product->requires_player_id && blank($data['player_id'] ?? null)) {
            throw new BusinessException(__('store.player_id_required'));
        }
    }
}
