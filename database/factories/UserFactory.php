<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'username' => Str::lower(Str::random(4)).fake()->unique()->numberBetween(1000, 999999),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+9639########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Seeds a starting balance directly. For tests/seeders only; application code must use WalletService.
     */
    public function withBalance(string|int|float $balance): static
    {
        return $this->afterCreating(function (User $user) use ($balance) {
            User::query()->whereKey($user->id)->update(['balance' => $balance]);
            $user->forceFill(['balance' => $balance])->syncOriginal();
        });
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function banned(): static
    {
        return $this->state(fn () => ['is_banned' => true]);
    }

    public function kycApproved(): static
    {
        return $this->afterCreating(fn (User $user) => $user->forceFill(['kyc_status' => 'approved'])->save());
    }
}
