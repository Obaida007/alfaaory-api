<?php

use App\Enums\DepositStatus;
use App\Exceptions\BusinessException;
use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\DepositService;

it('credits the wallet when a deposit is approved', function () {
    $user = buyer('2.00');
    $deposit = Deposit::factory()->for($user)->create(['amount_usd' => '25.00']);

    $deposit = app(DepositService::class)->approve($deposit, buyer(), note: 'تم');

    expect($deposit->status)->toBe(DepositStatus::Approved)
        ->and($deposit->reviewed_at)->not->toBeNull()
        ->and($user->fresh()->balance)->toBe('27.00')
        ->and($user->notifications()->count())->toBe(1);
});

it('never approves the same deposit twice', function () {
    $user = buyer();
    $deposit = Deposit::factory()->for($user)->create(['amount_usd' => '10.00']);
    $service = app(DepositService::class);

    $service->approve($deposit, buyer());

    expect(fn () => $service->approve($deposit, buyer()))->toThrow(BusinessException::class);
    expect($user->fresh()->balance)->toBe('10.00');
});

it('does not credit a rejected deposit', function () {
    $user = buyer();
    $deposit = Deposit::factory()->for($user)->create();

    app(DepositService::class)->reject($deposit, buyer(), 'إيصال غير واضح');

    expect($deposit->fresh()->status)->toBe(DepositStatus::Rejected)
        ->and($user->fresh()->balance)->toBe('0.00');
});

it('converts the amount to USD with the method rate and fee', function () {
    $method = PaymentMethod::factory()->create(['currency' => 'EGP', 'exchange_rate' => 50, 'fee_percent' => 2]);

    $deposit = app(DepositService::class)->submit(buyer(), $method, ['amount' => '1000']);

    expect($deposit->amount_usd)->toBe('19.60');
});

it('requires KYC for methods that demand it', function () {
    $method = PaymentMethod::factory()->create(['requires_kyc' => true]);

    expect(fn () => app(DepositService::class)->submit(buyer(), $method, ['amount' => '10']))
        ->toThrow(BusinessException::class);

    $verified = User::factory()->kycApproved()->create();
    expect(app(DepositService::class)->submit($verified->fresh(), $method, ['amount' => '10']))->toBeInstanceOf(Deposit::class);
});
