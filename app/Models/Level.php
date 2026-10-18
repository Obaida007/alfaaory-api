<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'min_total_purchases', 'discount_percent', 'color', 'sort'])]
class Level extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'min_total_purchases' => 'decimal:2',
            'discount_percent' => 'decimal:2',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** The highest level whose threshold is reached by the given purchases total. */
    public static function forTotal(string|float $total): ?self
    {
        return static::query()
            ->where('min_total_purchases', '<=', $total)
            ->orderByDesc('min_total_purchases')
            ->first();
    }

    public function next(): ?self
    {
        return static::query()
            ->where('min_total_purchases', '>', $this->min_total_purchases)
            ->orderBy('min_total_purchases')
            ->first();
    }
}
