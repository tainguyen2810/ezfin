<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

enum ProductType: string implements HasLabel
{
    case CreditCard = 'credit_card';
    case Savings = 'savings';

    public function getLabel(): string
    {
        return match ($this) {
            self::CreditCard => 'Thẻ tín dụng',
            self::Savings => 'Tiết kiệm',
        };
    }
}
