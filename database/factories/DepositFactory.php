<?php

namespace Database\Factories;

use App\Models\Deposit;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deposit>
 */
class DepositFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'amount' => '25.00',
            'currency' => 'USD',
            'amount_usd' => '25.00',
            'sender_name' => fake()->name(),
            'transaction_ref' => fake()->numerify('TX########'),
            'status' => 'pending',
        ];
    }
}
