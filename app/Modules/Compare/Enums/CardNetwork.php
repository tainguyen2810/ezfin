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

    /** Tổ chức thẻ theo tên sản phẩm, null nếu tên không nêu. */
    public static function fromName(string $name): ?self
    {
        $name = mb_strtolower($name);

        return match (true) {
            str_contains($name, 'american express'), str_contains($name, 'amex') => self::Amex,
            str_contains($name, 'mastercard'), str_contains($name, 'master card') => self::Mastercard,
            str_contains($name, 'jcb') => self::Jcb,
            str_contains($name, 'unionpay') => self::UnionPay,
            str_contains($name, 'napas') => self::Napas,
            str_contains($name, 'visa') => self::Visa,
            default => null,
        };
    }
}
