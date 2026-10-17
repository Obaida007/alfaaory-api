<?php

namespace Database\Factories;

use App\Models\RechargeCode;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RechargeCode>
 */
class RechargeCodeFactory extends Factory
{
    public function definition(): array
    {
        $code = Str::upper(Str::random(16));

        return [
            'code_hash' => RechargeCode::hash($code),
            'code_last4' => substr($code, -4),
            'amount' => '10.00',
        ];
    }

    /** Creates the code from a known plain value (so tests can redeem it). */
    public function plain(string $code): static
    {
        return $this->state(fn () => [
            'code_hash' => RechargeCode::hash($code),
            'code_last4' => substr(RechargeCode::normalize($code), -4),
        ]);
    }
}
