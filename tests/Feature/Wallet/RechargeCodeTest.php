<?php

use App\Exceptions\BusinessException;
use App\Models\RechargeCode;
use App\Services\RechargeCodeService;

it('redeems a code exactly once', function () {
    RechargeCode::factory()->plain('ABCD-EFGH-1234-5678')->create(['amount' => '15.00']);
    $user = buyer('1.00');
    $service = app(RechargeCodeService::class);

    $service->redeem($user, 'abcd efgh 1234 5678');

    expect($user->fresh()->balance)->toBe('16.00');
    expect(fn () => $service->redeem($user, 'ABCD-EFGH-1234-5678'))->toThrow(BusinessException::class, __('store.code_used'));
    expect(fn () => $service->redeem(buyer(), 'ABCD-EFGH-1234-5678'))->toThrow(BusinessException::class);
    expect($user->fresh()->balance)->toBe('16.00')
        ->and(RechargeCode::first()->used_by)->toBe($user->id);
});

it('rejects expired and unknown codes', function () {
    RechargeCode::factory()->plain('OLD0-OLD0-OLD0-OLD0')->create(['expires_at' => now()->subDay()]);
    $service = app(RechargeCodeService::class);

    expect(fn () => $service->redeem(buyer(), 'OLD0-OLD0-OLD0-OLD0'))->toThrow(BusinessException::class, __('store.code_expired'));
    expect(fn () => $service->redeem(buyer(), 'NOPE'))->toThrow(BusinessException::class, __('store.code_invalid'));
});

it('generates codes that are stored only as hashes', function () {
    $result = app(RechargeCodeService::class)->generate('5', 3);

    expect($result['codes'])->toHaveCount(3)
        ->and(RechargeCode::count())->toBe(3)
        ->and(RechargeCode::where('code_hash', $result['codes'][0])->exists())->toBeFalse()
        ->and(RechargeCode::where('code_hash', RechargeCode::hash($result['codes'][0]))->exists())->toBeTrue();
});
