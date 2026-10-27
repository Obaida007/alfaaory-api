<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentGroup: string implements HasColor, HasLabel
{
    case Wallet = 'wallet';
    case Crypto = 'crypto';
    case Bank = 'bank';
    case AgencySalary = 'agency_salary';
    case Code = 'code';

    public function getLabel(): string
    {
        return match ($this) {
            self::Wallet => 'محافظ وحوالات',
            self::Crypto => 'العملات الرقمية',
            self::Bank => 'البنوك والبطاقات',
            self::AgencySalary => 'رواتب الوكالات',
            self::Code => 'أكواد شحن',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Wallet => 'primary',
            self::Crypto => 'warning',
            self::Bank => 'info',
            self::AgencySalary => 'success',
            self::Code => 'gray',
        };
    }
}
