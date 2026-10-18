<?php

use App\Exceptions\InsufficientBalanceException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\OrderService;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Str;

/**
 * Each closure runs in its own PHP process with its own DB connection, so the requests really race.
 */
function racePurchases(int $userId, int $productId, array $keys): array
{
    // All processes wait for the same instant (after PHP has booted) so their requests overlap.
    $startAt = microtime(true) + 2.0;
    $tasks = [];

    foreach ($keys as $key) {
        $tasks[] = function () use ($userId, $productId, $key, $startAt) {
            $user = User::findOrFail($userId);
            $product = Product::findOrFail($productId);
            usleep((int) max(0, ($startAt - microtime(true)) * 1_000_000));

            try {
                [, $created] = app(OrderService::class)->place($user, $product, ['player_id' => '5123456'], $key);

                return $created ? 'created' : 'replayed';
            } catch (InsufficientBalanceException) {
                return 'insufficient';
            }
        };
    }

    return Concurrency::driver('process')->run($tasks);
}

it('lets only one of two simultaneous orders spend the same balance', function () {
    $user = buyer('10.00');
    $product = product('10.00');

    $results = racePurchases($user->id, $product->id, [(string) Str::uuid(), (string) Str::uuid()]);

    sort($results);
    expect($results)->toBe(['created', 'insufficient'])
        ->and(Order::count())->toBe(1)
        ->and($user->fresh()->balance)->toBe('0.00')
        ->and(WalletTransaction::count())->toBe(1);
});

it('charges once when the same idempotency key is sent concurrently', function () {
    $user = buyer('50.00');
    $product = product('10.00');
    $key = (string) Str::uuid();

    $results = racePurchases($user->id, $product->id, [$key, $key, $key]);

    $counts = array_count_values($results);
    ksort($counts);

    expect($counts)->toBe(['created' => 1, 'replayed' => 2])
        ->and(Order::count())->toBe(1)
        ->and($user->fresh()->balance)->toBe('40.00');
});
