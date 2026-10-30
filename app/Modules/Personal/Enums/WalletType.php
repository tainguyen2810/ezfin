<?php

namespace App\Modules\Personal\Enums;

use Filament\Support\Contracts\HasLabel;

enum WalletType: string implements HasLabel
{
    case Cash = 'cash';
    case Bank = 'bank';
    case EWallet = 'ewallet';
    case Savings = 'savings';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cash => 'Tiền mặt',
            self::Bank => 'Tài khoản ngân hàng',
            self::EWallet => 'Ví điện tử',
            self::Savings => 'Sổ tiết kiệm',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Cash => 'banknotes',
            self::Bank => 'building-library',
            self::EWallet => 'wallet',
            self::Savings => 'chart-bar',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $type) => [$type->value => $type->getLabel()])->all();
    }
}
