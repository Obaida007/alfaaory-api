<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Exact decimal arithmetic for money. Amounts travel as numeric strings with 2 decimals; never floats.
 */
final class Money
{
    public static function of(string|int|float|BigDecimal|null $value): BigDecimal
    {
        if ($value instanceof BigDecimal) {
            return $value;
        }

        return BigDecimal::of(is_float($value) ? (string) $value : ($value ?? '0'));
    }

    public static function round(string|int|float|BigDecimal|null $value): string
    {
        return (string) self::of($value)->toScale(2, RoundingMode::HalfUp);
    }

    public static function add(mixed $a, mixed $b): string
    {
        return self::round(self::of($a)->plus(self::of($b)));
    }

    public static function sub(mixed $a, mixed $b): string
    {
        return self::round(self::of($a)->minus(self::of($b)));
    }

    public static function mul(mixed $a, mixed $b): string
    {
        return self::round(self::of($a)->multipliedBy(self::of($b)));
    }

    /** $a * $percent / 100 */
    public static function percent(mixed $a, mixed $percent): string
    {
        return self::round(self::of($a)->multipliedBy(self::of($percent))->dividedBy(100, 6, RoundingMode::HalfUp));
    }

    public static function div(mixed $a, mixed $b): string
    {
        return self::round(self::of($a)->dividedBy(self::of($b), 6, RoundingMode::HalfUp));
    }

    public static function cmp(mixed $a, mixed $b): int
    {
        return self::of($a)->compareTo(self::of($b));
    }

    public static function isPositive(mixed $a): bool
    {
        return self::of($a)->isPositive();
    }
}
