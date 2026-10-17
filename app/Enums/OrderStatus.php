<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Refunded = 'refunded';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::Processing => 'قيد التنفيذ',
            self::Completed => 'مكتمل',
            self::Rejected => 'مرفوض',
            self::Refunded => 'مسترد',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Rejected => 'danger',
            self::Refunded => 'gray',
        };
    }
}
