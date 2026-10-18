<?php

namespace App\Services;

use App\Models\Level;
use App\Models\User;
use App\Support\Money;

class LevelService
{
    /** Moves the user to the level matching their completed purchases total. */
    public function sync(User $user): ?Level
    {
        $level = Level::forTotal($user->totalCompletedPurchases());

        if ($level && $user->level_id !== $level->id) {
            $user->forceFill(['level_id' => $level->id])->save();
            $user->setRelation('level', $level);
        }

        return $level;
    }

    /**
     * @return array{current: ?Level, next: ?Level, total_purchases: string, remaining: string, percent: float}
     */
    public function progress(User $user): array
    {
        $total = $user->totalCompletedPurchases();
        $current = $user->level ?? Level::forTotal($total);
        $next = $current?->next() ?? Level::query()->orderBy('min_total_purchases')->where('min_total_purchases', '>', $total)->first();

        if (! $next) {
            return ['current' => $current, 'next' => null, 'total_purchases' => Money::round($total), 'remaining' => '0.00', 'percent' => 100.0];
        }

        $from = $current?->min_total_purchases ?? '0';
        $span = Money::sub($next->min_total_purchases, $from);
        $done = Money::sub($total, $from);
        $percent = Money::isPositive($span) ? (float) Money::div(Money::mul($done, 100), $span) : 100.0;

        return [
            'current' => $current,
            'next' => $next,
            'total_purchases' => Money::round($total),
            'remaining' => Money::cmp($next->min_total_purchases, $total) > 0 ? Money::sub($next->min_total_purchases, $total) : '0.00',
            'percent' => max(0.0, min(100.0, $percent)),
        ];
    }

    public function discountPercent(User $user): string
    {
        return Money::round($user->level?->discount_percent ?? 0);
    }
}
