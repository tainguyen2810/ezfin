<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

enum InstitutionType: string implements HasLabel
{
    case Bank = 'bank';
    case FinanceCompany = 'finance_company';

    public function getLabel(): string
    {
        return match ($this) {
            self::Bank => 'Ngân hàng',
            self::FinanceCompany => 'Công ty tài chính',
        };
    }
}
