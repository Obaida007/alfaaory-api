<?php

use App\Enums\TransactionType;
use App\Exceptions\BusinessException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\WalletTransaction;
use App\Services\WalletService;

it('credits the balance and records a ledger row', function () {
    $user = buyer('5.00');

    $tx = app(WalletService::class)->credit($user, '2.50', null, 'test');

    expect($user->fresh()->balance)->toBe('7.50')
        ->and($tx->type)->toBe(TransactionType::Credit)
        ->and($tx->balance_before)->toBe('5.00')
        ->and($tx->balance_after)->toBe('7.50');
});

it('debits the balance and records a ledger row', function () {
    $user = buyer('10.00');

    app(WalletService::class)->debit($user, '3.33');

    expect($user->fresh()->balance)->toBe('6.67')
        ->and(WalletTransaction::where('user_id', $user->id)->where('type', 'debit')->sum('amount'))->toEqual('3.33');
});

it('refuses to debit more than the balance', function () {
    $user = buyer('1.00');

    expect(fn () => app(WalletService::class)->debit($user, '1.01'))->toThrow(InsufficientBalanceException::class);

    expect($user->fresh()->balance)->toBe('1.00')
        ->and(WalletTransaction::count())->toBe(0);
});

it('rejects zero and negative amounts', function (string $amount) {
    expect(fn () => app(WalletService::class)->credit(buyer(), $amount))->toThrow(BusinessException::class);
})->with(['0', '-5']);

it('requires a reason for manual adjustments and records the admin', function () {
    $user = buyer('10.00');
    $admin = buyer();
    $wallet = app(WalletService::class);

    expect(fn () => $wallet->adjust($user, '5', '', $admin))->toThrow(BusinessException::class);

    $wallet->adjust($user, '-4', 'تصحيح', $admin);
    $tx = $wallet->adjust($user, '1.5', 'مكافأة', $admin);

    expect($user->fresh()->balance)->toBe('7.50')
        ->and($tx->created_by)->toBe($admin->id)
        ->and($tx->source_type)->toBe('admin_adjustment');
});
