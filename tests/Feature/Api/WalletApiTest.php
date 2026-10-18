<?php

use App\Models\Deposit;
use App\Models\KycRequest;
use App\Models\PaymentMethod;
use App\Models\RechargeCode;
use App\Models\User;
use App\Services\DepositService;
use App\Services\WalletService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('summarises the wallet and lists transactions', function () {
    $user = buyer();
    app(WalletService::class)->credit($user, '20.00', null, 'test');
    app(WalletService::class)->debit($user, '5.00', null, 'test');
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/wallet')
        ->assertOk()
        ->assertJsonPath('data.balance', '15.00')
        ->assertJsonPath('data.total_received', '20.00');

    $this->getJson('/api/v1/wallet/transactions')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.type', 'debit');
});

it('submits a deposit with a receipt', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->create());
    $method = PaymentMethod::factory()->create(['currency' => 'EGP', 'exchange_rate' => 50]);

    $this->post('/api/v1/deposits', [
        'payment_method_id' => $method->id,
        'amount' => '500',
        'sender_name' => 'محمد',
        'receipt_image' => UploadedFile::fake()->image('r.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.amount_usd', '10.00')
        ->assertJsonPath('data.status', 'pending');

    Storage::disk('public')->assertExists(Deposit::first()->receipt_image);
});

it('rejects deposits needing KYC from unverified users', function () {
    Storage::fake('public');
    Sanctum::actingAs(User::factory()->create());
    $method = PaymentMethod::factory()->create(['requires_kyc' => true]);

    $this->post('/api/v1/deposits', [
        'payment_method_id' => $method->id, 'amount' => '10', 'receipt_image' => UploadedFile::fake()->image('r.jpg'),
    ], ['Accept' => 'application/json'])
        ->assertForbidden()
        ->assertJsonPath('code', 'kyc_required');
});

it('lists my deposits', function () {
    $user = User::factory()->create();
    Deposit::factory()->for($user)->count(2)->create();
    Deposit::factory()->create();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/deposits')->assertOk()->assertJsonCount(2, 'data');
});

it('redeems a recharge code only once', function () {
    RechargeCode::factory()->plain('AAAA-BBBB-CCCC-DDDD')->create(['amount' => '25.00']);
    Sanctum::actingAs($user = buyer());

    $this->postJson('/api/v1/recharge-codes/redeem', ['code' => 'AAAA-BBBB-CCCC-DDDD'])
        ->assertOk()->assertJsonPath('data.balance', '25.00');
    $this->postJson('/api/v1/recharge-codes/redeem', ['code' => 'AAAA-BBBB-CCCC-DDDD'])
        ->assertUnprocessable()->assertJsonPath('code', 'code_used');

    expect($user->fresh()->balance)->toBe('25.00');
});

it('submits KYC and reports its status', function () {
    Storage::fake('local');
    Sanctum::actingAs($user = User::factory()->create());

    $this->post('/api/v1/kyc', [
        'full_name' => 'محمد أحمد', 'national_id' => '123456789',
        'id_image' => UploadedFile::fake()->image('id.jpg'), 'selfie_image' => UploadedFile::fake()->image('s.jpg'),
    ], ['Accept' => 'application/json'])->assertCreated();

    $this->getJson('/api/v1/kyc')->assertOk()->assertJsonPath('data.status', 'pending');
    expect(KycRequest::count())->toBe(1)->and($user->fresh()->kyc_status->value)->toBe('pending');
});

it('manages favorites', function () {
    Sanctum::actingAs(User::factory()->create());
    $product = product();

    $this->postJson("/api/v1/favorites/{$product->id}")->assertOk();
    $this->postJson("/api/v1/favorites/{$product->id}")->assertOk();
    $this->getJson('/api/v1/favorites')->assertOk()->assertJsonCount(1, 'data');
    $this->deleteJson("/api/v1/favorites/{$product->id}")->assertOk();
    $this->getJson('/api/v1/favorites')->assertJsonCount(0, 'data');
});

it('lists notifications and marks them read', function () {
    $user = User::factory()->create();
    $deposit = Deposit::factory()->for($user)->create();
    app(DepositService::class)->approve($deposit, User::factory()->create());
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.subject', 'deposit')
        ->assertJsonPath('meta.unread_count', 1);

    $this->postJson('/api/v1/notifications/read-all')->assertOk();
    $this->getJson('/api/v1/notifications')->assertJsonPath('meta.unread_count', 0);
});
