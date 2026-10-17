<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['parent_id', 'name', 'slug', 'image', 'description', 'is_active', 'sort'])]
class Category extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (blank($category->slug)) {
                $base = Str::slug($category->name) ?: Str::lower(Str::random(8));
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->whereKeyNot($category->getKey())->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $category->slug = $slug;
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }

    public function activeChildren(): HasMany
    {
        return $this->children()->where('is_active', true);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class)->orderBy('sort');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
