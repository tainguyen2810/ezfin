<?php

namespace App\Modules\Planner\Enums;

/** Cách trả nợ vay và trả góp. */
enum RepaymentMethod: string
{
    /** Gốc chia đều, lãi tính trên dư nợ còn lại: số tiền trả giảm dần. */
    case EqualPrincipal = 'du-no-giam-dan';

    /** Số tiền trả mỗi tháng bằng nhau (gốc + lãi), lãi tính trên dư nợ còn lại. */
    case Annuity = 'tra-deu';

    /** Lãi phẳng: lãi mỗi tháng tính trên số tiền vay ban đầu. */
    case Flat = 'lai-phang';

    public function label(): string
    {
        return match ($this) {
            self::EqualPrincipal => 'Dư nợ giảm dần (gốc đều)',
            self::Annuity => 'Trả đều hằng tháng',
            self::Flat => 'Lãi phẳng (trên số tiền vay ban đầu)',
        };
    }
}
