<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'group' => 'wallet',
            'instructions' => fake()->sentence(),
            'account_details' => ['رقم الحساب' => fake()->numerify('##########')],
            'currency' => 'USD',
            'exchange_rate' => 1,
            'min_amount' => 1,
            'max_amount' => 10000,
            'fee_percent' => 0,
            'requires_kyc' => false,
            'is_active' => true,
        ];
    }
}
