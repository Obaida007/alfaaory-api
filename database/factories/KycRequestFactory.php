<?php

namespace Database\Factories;

use App\Models\KycRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KycRequest>
 */
class KycRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'full_name' => fake()->name(),
            'national_id' => fake()->numerify('###########'),
            'id_image' => 'kyc/id.jpg',
            'selfie_image' => 'kyc/selfie.jpg',
            'status' => 'pending',
        ];
    }
}
