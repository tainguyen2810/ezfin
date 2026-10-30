<?php

namespace App\Modules\Personal\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Thu nhập',
            self::Expense => 'Chi tiêu',
        };
    }

    /** Giá trị trên URL (?loai=thu). */
    public function slug(): string
    {
        return match ($this) {
            self::Income => 'thu',
            self::Expense => 'chi',
        };
    }
}
