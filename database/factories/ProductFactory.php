<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->numberBetween(10, 5000).' UC',
            'price' => '10.00',
            'cost' => '9.00',
            'delivery_type' => 'manual',
            'requires_player_id' => true,
            'supports_name_check' => true,
            'is_active' => true,
        ];
    }

    public function auto(): static
    {
        return $this->state(fn () => ['delivery_type' => 'auto', 'provider' => 'fake']);
    }
}
