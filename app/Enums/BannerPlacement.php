<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BannerPlacement: string implements HasColor, HasLabel
{
    case HomePopup = 'home_popup';
    case HomeHero = 'home_hero';

    public function getLabel(): string
    {
        return match ($this) {
            self::HomePopup => 'نافذة منبثقة',
            self::HomeHero => 'بانر رئيسي',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::HomePopup => 'warning',
            self::HomeHero => 'primary',
        };
    }
}
