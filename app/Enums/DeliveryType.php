<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DeliveryType: string implements HasColor, HasLabel
{
    case Auto = 'auto';
    case Manual = 'manual';
    case Code = 'code';

    public function getLabel(): string
    {
        return match ($this) {
            self::Auto => 'تلقائي',
            self::Manual => 'يدوي',
            self::Code => 'كود',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Auto => 'success',
            self::Manual => 'warning',
            self::Code => 'info',
        };
    }
}
