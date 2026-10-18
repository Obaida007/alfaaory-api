<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\BusinessException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The ONLY place allowed to change users.balance.
 *
 * Every mutation runs in a DB transaction, locks the user row (SELECT ... FOR UPDATE) and
 * writes a wallet_transactions ledger row with the balance before/after.
 */
class WalletService
{
    public function credit(User $user, string|int|float $amount, ?Model $source = null, ?string $description = null, ?User $createdBy = null): WalletTransaction
    {
        return $this->apply($user, TransactionType::Credit, $amount, $source, $description, $createdBy);
    }

    /**
     * @throws InsufficientBalanceException
     */
    public function debit(User $user, string|int|float $amount, ?Model $source = null, ?string $description = null, ?User $createdBy = null): WalletTransaction
    {
        return $this->apply($user, TransactionType::Debit, $amount, $source, $description, $createdBy);
    }

    /**
     * Credits back the amount charged for an order. Safe to call once per order: a second call is rejected.
     */
    public function refund(Order $order, ?string $description = null, ?User $createdBy = null): WalletTransaction
    {
        return DB::transaction(function () use ($order, $description, $createdBy) {
            $user = $order->user;
            $this->lockUser($user);

            $alreadyRefunded = $order->walletTransactions()->where('type', TransactionType::Credit)->exists();
            if ($alreadyRefunded) {
                throw new BusinessException(__('store.order_already_refunded'));
            }

            $charged = $order->walletTransactions()->where('type', TransactionType::Debit)->sum('amount');

            return $this->apply(
                $user,
                TransactionType::Credit,
                Money::round($charged ?: $order->total),
                $order,
                $description ?? __('store.tx.order_refund', ['id' => $order->id]),
                $createdBy,
            );
        });
    }

    /**
     * Admin manual adjustment: positive amount credits, negative debits. A reason is mandatory.
     */
    public function adjust(User $user, string|int|float $amount, string $reason, User $admin): WalletTransaction
    {
        if (blank($reason)) {
            throw new BusinessException(__('store.adjust_reason_required'));
        }

        $amount = Money::of($amount);
        $description = __('store.tx.admin_adjustment', ['reason' => $reason]);

        // Source is the admin who made the change, stored under the "admin_adjustment" morph alias.
        return $this->apply(
            $user,
            $amount->isNegative() ? TransactionType::Debit : TransactionType::Credit,
            (string) $amount->abs(),
            $admin,
            $description,
            $admin,
            'admin_adjustment',
        );
    }

    protected function apply(
        User $user,
        TransactionType $type,
        string|int|float $amount,
        ?Model $source,
        ?string $description,
        ?User $createdBy,
        ?string $sourceType = null,
    ): WalletTransaction {
        $amount = Money::round($amount);

        if (! Money::isPositive($amount)) {
            throw new BusinessException(__('store.invalid_amount'));
        }

        return DB::transaction(function () use ($user, $type, $amount, $source, $description, $createdBy, $sourceType) {
            $before = $this->lockUser($user);

            if ($type === TransactionType::Debit && Money::cmp($before, $amount) < 0) {
                throw new InsufficientBalanceException;
            }

            $after = $type === TransactionType::Credit ? Money::add($before, $amount) : Money::sub($before, $amount);

            User::query()->whereKey($user->getKey())->update(['balance' => $after]);

            // Keep the in-memory model in sync without marking it dirty.
            $user->forceFill(['balance' => $after])->syncOriginalAttribute('balance');

            return WalletTransaction::create([
                'user_id' => $user->getKey(),
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $before,
                'balance_after' => $after,
                'source_type' => $source ? ($sourceType ?? $source->getMorphClass()) : null,
                'source_id' => $source?->getKey(),
                'description' => $description,
                'created_by' => $createdBy?->getKey(),
            ]);
        });
    }

    /** Locks the user row for the rest of the transaction and returns the fresh balance. */
    protected function lockUser(User $user): string
    {
        return Money::round(
            User::query()->whereKey($user->getKey())->lockForUpdate()->value('balance')
        );
    }
}
