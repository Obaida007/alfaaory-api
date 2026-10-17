<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum KycStatus: string implements HasColor, HasLabel
{
    case None = 'none';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return match ($this) {
            self::None => 'غير محقَّق',
            self::Pending => 'قيد المراجعة',
            self::Approved => 'محقَّق',
            self::Rejected => 'مرفوض',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::None => 'gray',
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
