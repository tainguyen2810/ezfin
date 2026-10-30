<?php

namespace App\Modules\Personal\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Thu nhập',
            self::Expense => 'Chi tiêu',
            self::TransferIn => 'Chuyển đến',
            self::TransferOut => 'Chuyển đi',
        };
    }

    /** Tiền vào ví (+) hay ra khỏi ví (−). */
    public function isInflow(): bool
    {
        return in_array($this, [self::Income, self::TransferIn], true);
    }

    public function isTransfer(): bool
    {
        return in_array($this, [self::TransferIn, self::TransferOut], true);
    }

    /** @return list<string> */
    public static function inflows(): array
    {
        return [self::Income->value, self::TransferIn->value];
    }

    /** @return list<string> */
    public static function outflows(): array
    {
        return [self::Expense->value, self::TransferOut->value];
    }
}
