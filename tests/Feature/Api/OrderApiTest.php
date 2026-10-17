<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

it('requires an Idempotency-Key header', function () {
    Sanctum::actingAs(buyer('10.00'));

    $this->postJson('/api/v1/orders', ['product_id' => product()->id, 'player_id' => '5123456'])
        ->assertStatus(400)
        ->assertJsonPath('message', __('store.idempotency_required'));
});

it('places an order and replays it for the same key', function () {
    $user = buyer('30.00');
    $product = product('10.12');
    Sanctum::actingAs($user);
    $key = (string) Str::uuid();

    $this->postJson('/api/v1/orders', ['product_id' => $product->id, 'player_id' => '5123456'], ['Idempotency-Key' => $key])
        ->assertCreated()
        ->assertJsonPath('data.total', '10.12')
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('message', __('store.messages.order_created'));

    $this->postJson('/api/v1/orders', ['product_id' => $product->id, 'player_id' => '5123456'], ['Idempotency-Key' => $key])
        ->assertOk()
        ->assertJsonPath('message', __('store.messages.order_replayed'));

    expect(Order::count())->toBe(1)->and($user->fresh()->balance)->toBe('19.88');
});

it('returns an Arabic error when the balance is insufficient', function () {
    Sanctum::actingAs(buyer('1.00'));

    $this->postJson('/api/v1/orders', ['product_id' => product('5.00')->id, 'player_id' => '5123456'], ['Idempotency-Key' => 'k1'])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('store.insufficient_balance'))
        ->assertJsonPath('code', 'insufficient_balance');
});

it('requires the player id when the product needs one', function () {
    Sanctum::actingAs(buyer('10.00'));

    $this->postJson('/api/v1/orders', ['product_id' => product()->id], ['Idempotency-Key' => 'k2'])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('store.player_id_required'));
});

it('lists and filters only my orders', function () {
    $user = buyer('100.00');
    Order::factory()->for($user)->create(['status' => 'completed']);
    Order::factory()->for($user)->create(['status' => 'pending', 'player_id' => '777888']);
    Order::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/orders')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('meta.total', 2);
    $this->getJson('/api/v1/orders?status=completed')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/orders?q=777888')->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/orders?from='.now()->addDay()->toDateString())->assertJsonCount(0, 'data');
});

it('does not show other users orders', function () {
    $order = Order::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/v1/orders/{$order->id}")->assertNotFound();
});

it('checks the player name through the provider', function () {
    Sanctum::actingAs(User::factory()->create());
    $product = product();

    $this->postJson("/api/v1/products/{$product->id}/check-name", ['player_id' => '5123456'])
        ->assertOk()->assertJsonPath('data.player_name', 'Player_3456');

    $this->postJson("/api/v1/products/{$product->id}/check-name", ['player_id' => '5123000'])
        ->assertNotFound()->assertJsonPath('code', 'player_not_found');
});

it('rate limits orders', function () {
    Sanctum::actingAs(buyer('0.00'));
    $product = product();

    foreach (range(1, config('store.rate_limits.orders')) as $i) {
        $this->postJson('/api/v1/orders', ['product_id' => $product->id, 'player_id' => '5123456'], ['Idempotency-Key' => "r{$i}"]);
    }

    $this->postJson('/api/v1/orders', ['product_id' => $product->id, 'player_id' => '5123456'], ['Idempotency-Key' => 'last'])
        ->assertTooManyRequests();
});
