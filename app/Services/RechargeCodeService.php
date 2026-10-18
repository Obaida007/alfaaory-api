<?php

namespace App\Services;

use App\Exceptions\BusinessException;
use App\Models\RechargeCode;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Support\Money;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RechargeCodeService
{
    public function __construct(protected WalletService $wallet) {}

    /**
     * Creates a batch of codes. The plain codes are returned ONLY here (only their hash is stored).
     *
     * @return array{batch: string, codes: array<int, string>}
     */
    public function generate(string|float $amount, int $count, ?string $batch = null, ?DateTimeInterface $expiresAt = null, ?User $createdBy = null): array
    {
        $amount = Money::round($amount);
        if (! Money::isPositive($amount) || $count < 1 || $count > 5000) {
            throw new BusinessException(__('store.invalid_amount'));
        }

        $batch = $batch ?: 'B'.now()->format('ymdHis').Str::upper(Str::random(4));
        $codes = [];

        DB::transaction(function () use ($amount, $count, $batch, $expiresAt, $createdBy, &$codes) {
            while (count($codes) < $count) {
                $plain = implode('-', str_split(Str::upper(Str::random(16)), 4));
                $hash = RechargeCode::hash($plain);
                if (isset($codes[$hash]) || RechargeCode::where('code_hash', $hash)->exists()) {
                    continue;
                }
                RechargeCode::create([
                    'code_hash' => $hash,
                    'code_last4' => substr(RechargeCode::normalize($plain), -4),
                    'amount' => $amount,
                    'batch' => $batch,
                    'expires_at' => $expiresAt,
                    'created_by' => $createdBy?->getKey(),
                ]);
                $codes[$hash] = $plain;
            }
        });

        return ['batch' => $batch, 'codes' => array_values($codes)];
    }

    /** Marks the code as used and credits its amount, exactly once. */
    public function redeem(User $user, string $code): WalletTransaction
    {
        return DB::transaction(function () use ($user, $code) {
            $record = RechargeCode::query()->where('code_hash', RechargeCode::hash($code))->lockForUpdate()->first();

            if (! $record) {
                throw new BusinessException(__('store.code_invalid'), 422, 'code_invalid');
            }
            if ($record->isUsed()) {
                throw new BusinessException(__('store.code_used'), 422, 'code_used');
            }
            if ($record->isExpired()) {
                throw new BusinessException(__('store.code_expired'), 422, 'code_expired');
            }

            $record->update(['used_by' => $user->getKey(), 'used_at' => now()]);

            return $this->wallet->credit($user, $record->amount, $record, __('store.tx.recharge_code', ['last4' => $record->code_last4]));
        });
    }
}
