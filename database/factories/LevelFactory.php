<?php

namespace Database\Factories;

use App\Models\Level;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Level>
 */
class LevelFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'min_total_purchases' => 0,
            'discount_percent' => 0,
            'color' => fake()->hexColor(),
            'sort' => 0,
        ];
    }
}
