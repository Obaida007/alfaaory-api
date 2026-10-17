<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Exceptions\BusinessException;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Notifications\StatusNotification;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class DepositService
{
    public function __construct(protected WalletService $wallet) {}

    /**
     * @param  array{amount: string|float, sender_name?: ?string, transaction_ref?: ?string}  $data
     */
    public function submit(User $user, PaymentMethod $method, array $data, ?UploadedFile $receipt = null): Deposit
    {
        if (! $method->is_active) {
            throw new BusinessException(__('store.payment_method_unavailable'));
        }

        if ($method->requires_kyc && ! $user->isKycApproved()) {
            throw new BusinessException(__('store.kyc_required'), 403, 'kyc_required');
        }

        $amount = Money::round($data['amount']);

        if (Money::cmp($amount, $method->min_amount) < 0
            || ($method->max_amount !== null && Money::cmp($amount, $method->max_amount) > 0)) {
            throw new BusinessException(__('store.deposit_amount_range', [
                'min' => $method->min_amount,
                'max' => $method->max_amount ?? '∞',
                'currency' => $method->currency,
            ]));
        }

        return Deposit::create([
            'user_id' => $user->getKey(),
            'payment_method_id' => $method->getKey(),
            'amount' => $amount,
            'currency' => $method->currency,
            'amount_usd' => $method->toUsd($amount),
            'sender_name' => $data['sender_name'] ?? null,
            'transaction_ref' => $data['transaction_ref'] ?? null,
            'receipt_image' => $receipt?->store('receipts', 'public'),
            'status' => DepositStatus::Pending,
        ]);
    }

    /** Approves a pending deposit and credits amount_usd (optionally corrected by the reviewer). */
    public function approve(Deposit $deposit, User $admin, ?string $amountUsd = null, ?string $note = null): Deposit
    {
        $deposit = DB::transaction(function () use ($deposit, $admin, $amountUsd, $note) {
            $locked = $this->lockPending($deposit);
            $credit = $amountUsd !== null ? Money::round($amountUsd) : $locked->amount_usd;

            $this->wallet->credit($locked->user, $credit, $locked, __('store.tx.deposit', ['id' => $locked->id, 'method' => $locked->paymentMethod->name]), $admin);

            $locked->update([
                'status' => DepositStatus::Approved,
                'amount_usd' => $credit,
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
                'admin_note' => $note,
            ]);

            return $locked;
        });

        $deposit->user->notify(StatusNotification::forDeposit($deposit));

        return $deposit;
    }

    public function reject(Deposit $deposit, User $admin, ?string $note = null): Deposit
    {
        $deposit = DB::transaction(function () use ($deposit, $admin, $note) {
            $locked = $this->lockPending($deposit);
            $locked->update([
                'status' => DepositStatus::Rejected,
                'reviewed_by' => $admin->getKey(),
                'reviewed_at' => now(),
                'admin_note' => $note,
            ]);

            return $locked;
        });

        $deposit->user->notify(StatusNotification::forDeposit($deposit));

        return $deposit;
    }

    protected function lockPending(Deposit $deposit): Deposit
    {
        $locked = Deposit::query()->whereKey($deposit->getKey())->lockForUpdate()->firstOrFail();

        if ($locked->status !== DepositStatus::Pending) {
            throw new BusinessException(__('store.deposit_already_reviewed'));
        }

        return $locked;
    }
}
