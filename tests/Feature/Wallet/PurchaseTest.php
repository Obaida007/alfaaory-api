<?php

use App\Enums\OrderStatus;
use App\Exceptions\BusinessException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Level;
use App\Models\Order;
use App\Models\WalletTransaction;
use App\Services\OrderService;
use Illuminate\Support\Str;

it('buys a product from the wallet balance', function () {
    $user = buyer('20.00');
    $product = product('5.09');

    [$order, $created] = app(OrderService::class)->place($user, $product, ['player_id' => '5123456'], (string) Str::uuid());

    expect($created)->toBeTrue()
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->unit_price)->toBe('5.09')
        ->and($order->total)->toBe('5.09')
        ->and($user->fresh()->balance)->toBe('14.91')
        ->and($order->walletTransactions()->where('type', 'debit')->value('amount'))->toBe('5.09');
});

it('applies the level discount on the server', function () {
    $level = Level::factory()->create(['discount_percent' => '5.00']);
    $user = buyer('100.00');
    $user->forceFill(['level_id' => $level->id])->save();

    [$order] = app(OrderService::class)->place($user, product('46.46'), ['player_id' => '5123456'], (string) Str::uuid());

    expect($order->discount)->toBe('2.32')
        ->and($order->total)->toBe('44.14')
        ->and($user->fresh()->balance)->toBe('55.86');
});

it('does not create an order when the balance is insufficient', function () {
    $user = buyer('1.00');

    expect(fn () => app(OrderService::class)->place($user, product('5.09'), ['player_id' => '5123456'], (string) Str::uuid()))
        ->toThrow(InsufficientBalanceException::class);

    expect(Order::count())->toBe(0)
        ->and($user->fresh()->balance)->toBe('1.00');
});

it('charges only once for the same idempotency key', function () {
    $user = buyer('20.00');
    $product = product('5.00');
    $key = (string) Str::uuid();
    $service = app(OrderService::class);

    [$first, $created1] = $service->place($user, $product, ['player_id' => '5123456'], $key);
    [$second, $created2] = $service->place($user, $product, ['player_id' => '5123456'], $key);

    expect($created1)->toBeTrue()
        ->and($created2)->toBeFalse()
        ->and($second->id)->toBe($first->id)
        ->and(Order::count())->toBe(1)
        ->and(WalletTransaction::count())->toBe(1)
        ->and($user->fresh()->balance)->toBe('15.00');
});

it('refunds the money when an order is rejected', function () {
    $user = buyer('20.00');
    $admin = buyer();
    $service = app(OrderService::class);
    [$order] = $service->place($user, product('8.00'), ['player_id' => '5123456'], (string) Str::uuid());

    $order = $service->reject($order, $admin, 'معرّف خاطئ');

    expect($order->status)->toBe(OrderStatus::Refunded)
        ->and($user->fresh()->balance)->toBe('20.00')
        ->and($order->walletTransactions()->where('type', 'credit')->value('amount'))->toBe('8.00')
        ->and($user->notifications()->count())->toBe(1);

    // A second rejection must not refund twice.
    expect(fn () => $service->reject($order, $admin))->toThrow(BusinessException::class);
    expect($user->fresh()->balance)->toBe('20.00');
});

it('completes auto-delivery orders through the provider', function () {
    $user = buyer('20.00');
    $product = product('1.01', ['delivery_type' => 'auto', 'provider' => 'fake']);

    [$order] = app(OrderService::class)->place($user, $product, ['player_id' => '5123456'], (string) Str::uuid());

    expect($order->fresh()->status)->toBe(OrderStatus::Completed)
        ->and($order->fresh()->completed_at)->not->toBeNull();
});

it('refunds automatically when the provider fails', function () {
    $user = buyer('20.00');
    $product = product('1.01', ['delivery_type' => 'auto', 'provider' => 'fake']);

    [$order] = app(OrderService::class)->place($user, $product, ['player_id' => '5123000'], (string) Str::uuid());

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and($user->fresh()->balance)->toBe('20.00');
});

it('upgrades the user level from completed purchases', function () {
    Level::factory()->create(['name' => 'البرونزي', 'min_total_purchases' => 0]);
    $silver = Level::factory()->create(['name' => 'الفضي', 'min_total_purchases' => 500]);
    $user = buyer('1000.00');
    $service = app(OrderService::class);

    [$order] = $service->place($user, product('500.00'), ['player_id' => '5123456'], (string) Str::uuid());
    expect($user->fresh()->level_id)->toBeNull();

    $service->complete($order);

    expect($user->fresh()->level_id)->toBe($silver->id);
});
