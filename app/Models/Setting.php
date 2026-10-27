<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /** Keys exposed through GET /settings/public. */
    public const PUBLIC_KEYS = [
        'site_name', 'admin_whatsapp', 'admin_email', 'whatsapp_group_url', 'whatsapp_channel_url',
        'facebook_url', 'instagram_url', 'telegram_url', 'tiktok_url', 'youtube_url',
    ];

    public const PAGE_KEYS = ['about' => 'about_text', 'privacy' => 'privacy_policy'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings.all'));
        static::deleted(fn () => Cache::forget('settings.all'));
    }

    /** @return array<string, string|null> */
    public static function allCached(): array
    {
        return Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::allCached()[$key] ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
