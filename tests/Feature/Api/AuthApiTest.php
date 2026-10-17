<?php

use App\Models\User;
use App\Notifications\TwoFactorCode;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

it('registers and returns a token', function () {
    $this->postJson('/api/v1/auth/register', [
        'first_name' => 'أحمد',
        'last_name' => 'علي',
        'username' => 'ahmad_1',
        'email' => 'ahmad@example.com',
        'password' => 'secret123',
        'password_confirmation' => 'secret123',
    ])->assertCreated()
        ->assertJsonStructure(['data' => ['token', 'user' => ['id', 'balance', 'level_progress']], 'message'])
        ->assertJsonPath('data.user.balance', '0.00');
});

it('does not allow setting the balance on registration', function () {
    $this->postJson('/api/v1/auth/register', [
        'first_name' => 'x', 'last_name' => 'y', 'username' => 'hacker', 'email' => 'h@example.com',
        'password' => 'secret123', 'password_confirmation' => 'secret123', 'balance' => 9999,
    ])->assertCreated();

    expect(User::where('username', 'hacker')->value('balance'))->toBe('0.00');
});

it('returns Arabic validation errors', function () {
    $this->postJson('/api/v1/auth/register', [])
        ->assertUnprocessable()
        ->assertJsonPath('message', __('store.validation_failed'))
        ->assertJsonPath('errors.email.0', 'حقل البريد الإلكتروني مطلوب.');
});

it('logs in with username or email', function (string $field) {
    $user = User::factory()->create(['username' => 'player1', 'email' => 'p1@example.com']);

    $this->postJson('/api/v1/auth/login', ['login' => $user->{$field}, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id);
})->with(['username', 'email']);

it('rejects wrong credentials and banned users', function () {
    $user = User::factory()->create();
    $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'wrong'])->assertUnprocessable();

    $banned = User::factory()->banned()->create();
    $this->postJson('/api/v1/auth/login', ['login' => $banned->email, 'password' => 'password'])->assertForbidden();
});

it('rate limits login attempts', function () {
    $user = User::factory()->create();

    foreach (range(1, config('store.rate_limits.login')) as $_) {
        $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'wrong']);
    }

    $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'wrong'])
        ->assertTooManyRequests()
        ->assertJsonPath('message', __('store.too_many_requests'));
});

it('requires the emailed code when two-factor is enabled', function () {
    Notification::fake();
    $user = User::factory()->create(['two_factor_enabled' => true]);

    $challenge = $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.requires_two_factor', true)
        ->json('data.challenge');

    $code = null;
    Notification::assertSentTo($user, TwoFactorCode::class, function (TwoFactorCode $n) use (&$code) {
        $code = $n->code;

        return true;
    });

    $this->postJson('/api/v1/auth/two-factor', ['challenge' => $challenge, 'code' => '000000'])->assertUnprocessable();
    $this->postJson('/api/v1/auth/two-factor', ['challenge' => $challenge, 'code' => $code])
        ->assertOk()
        ->assertJsonStructure(['data' => ['token']]);
});

it('signs in with a verified Google token', function () {
    config(['services.google.client_id' => 'client-123']);
    Http::fake(['oauth2.googleapis.com/*' => Http::response([
        'aud' => 'client-123', 'sub' => 'g-1', 'email' => 'g@example.com', 'email_verified' => 'true', 'given_name' => 'Ali',
    ])]);

    $this->postJson('/api/v1/auth/google', ['id_token' => 'abc'])->assertOk();

    expect(User::where('google_id', 'g-1')->where('email', 'g@example.com')->exists())->toBeTrue();
});

it('rejects Google tokens for another client', function () {
    config(['services.google.client_id' => 'client-123']);
    Http::fake(['oauth2.googleapis.com/*' => Http::response(['aud' => 'other', 'sub' => 'g', 'email' => 'x@example.com', 'email_verified' => 'true'])]);

    $this->postJson('/api/v1/auth/google', ['id_token' => 'abc'])->assertUnauthorized();
});

it('sends and accepts password reset links', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk();

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $n) use (&$token) {
        $token = $n->token;

        return true;
    });

    $this->postJson('/api/v1/auth/reset-password', [
        'token' => $token, 'email' => $user->email, 'password' => 'newpass123', 'password_confirmation' => 'newpass123',
    ])->assertOk();

    $this->postJson('/api/v1/auth/login', ['login' => $user->email, 'password' => 'newpass123'])->assertOk();
});

it('logs out by revoking the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

it('requires authentication with an Arabic message', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('message', __('store.unauthenticated'));
});
