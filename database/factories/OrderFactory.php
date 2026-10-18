<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'player_id' => (string) fake()->numberBetween(100000, 9999999),
            'unit_price' => '10.00',
            'discount' => '0.00',
            'total' => '10.00',
            'status' => 'pending',
            'idempotency_key' => (string) Str::uuid(),
        ];
    }
}
