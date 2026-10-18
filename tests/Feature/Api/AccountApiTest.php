<?php

use App\Models\Level;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

it('returns the user with level progress', function () {
    $bronze = Level::factory()->create(['name' => 'البرونزي', 'min_total_purchases' => 0]);
    Level::factory()->create(['name' => 'الفضي', 'min_total_purchases' => 500]);
    $user = buyer('12.50');
    $user->forceFill(['level_id' => $bronze->id])->save();
    Sanctum::actingAs($user);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('data.balance', '12.50')
        ->assertJsonPath('data.level.name', 'البرونزي')
        ->assertJsonPath('data.level_progress.next_level.name', 'الفضي')
        ->assertJsonPath('data.level_progress.remaining', '500.00');
});

it('updates the profile but never the balance', function () {
    $user = buyer('5.00');
    Sanctum::actingAs($user);

    $this->putJson('/api/v1/me/profile', ['first_name' => 'سامر', 'balance' => 1000])->assertOk();

    expect($user->fresh())->first_name->toBe('سامر')->balance->toBe('5.00');
});

it('uploads an avatar', function () {
    Storage::fake('public');
    Sanctum::actingAs($user = User::factory()->create());

    $this->postJson('/api/v1/me/avatar', ['avatar' => UploadedFile::fake()->image('a.jpg')])->assertOk();

    Storage::disk('public')->assertExists($user->fresh()->avatar);
});

it('changes the password with the current one', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/v1/me/password', ['current_password' => 'wrong', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
        ->assertUnprocessable();
});

it('toggles two-factor and creates a telegram link', function () {
    Sanctum::actingAs($user = User::factory()->create());

    $this->putJson('/api/v1/me/two-factor', ['enabled' => true, 'password' => 'password'])
        ->assertOk()->assertJsonPath('data.two_factor_enabled', true);

    $this->postJson('/api/v1/me/telegram/link')->assertOk()->assertJsonStructure(['data' => ['token', 'url']]);
});

it('blocks banned users with a valid token', function () {
    $user = User::factory()->banned()->create();
    $token = $user->createToken('t')->plainTextToken;

    $this->withToken($token)->getJson('/api/v1/me')->assertForbidden();
});
