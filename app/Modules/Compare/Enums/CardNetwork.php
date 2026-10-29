<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

enum CardNetwork: string implements HasLabel
{
    case Visa = 'visa';
    case Mastercard = 'mastercard';
    case Jcb = 'jcb';
    case Amex = 'amex';
    case Napas = 'napas';
    case UnionPay = 'unionpay';

    public function getLabel(): string
    {
        return match ($this) {
            self::Visa => 'Visa',
            self::Mastercard => 'Mastercard',
            self::Jcb => 'JCB',
            self::Amex => 'American Express',
            self::Napas => 'Napas',
            self::UnionPay => 'UnionPay',
        };
    }
}
