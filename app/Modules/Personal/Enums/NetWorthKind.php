<?php

namespace App\Modules\Personal\Enums;

/** Loại tài sản/khoản nợ nhập tay (ví và tài khoản đã có ở màn hình Ví nên không lặp lại ở đây). */
enum NetWorthKind: string
{
    case RealEstate = 'real_estate';
    case Vehicle = 'vehicle';
    case Investment = 'investment';
    case Gold = 'gold';
    case Receivable = 'receivable';
    case OtherAsset = 'other_asset';
    case Mortgage = 'mortgage';
    case CarLoan = 'car_loan';
    case CreditCard = 'credit_card';
    case ConsumerLoan = 'consumer_loan';
    case Borrowed = 'borrowed';
    case OtherDebt = 'other_debt';

    public function isDebt(): bool
    {
        return in_array($this, [self::Mortgage, self::CarLoan, self::CreditCard, self::ConsumerLoan, self::Borrowed, self::OtherDebt], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::RealEstate => 'Bất động sản',
            self::Vehicle => 'Xe cộ',
            self::Investment => 'Chứng khoán, quỹ',
            self::Gold => 'Vàng',
            self::Receivable => 'Cho vay, phải thu',
            self::OtherAsset => 'Tài sản khác',
            self::Mortgage => 'Vay mua nhà',
            self::CarLoan => 'Vay mua xe',
            self::CreditCard => 'Dư nợ thẻ tín dụng',
            self::ConsumerLoan => 'Vay tiêu dùng',
            self::Borrowed => 'Vay người thân',
            self::OtherDebt => 'Khoản nợ khác',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::RealEstate, self::Mortgage => 'home',
            self::Vehicle, self::CarLoan => 'truck',
            self::Investment => 'arrow-trending-up',
            self::Gold => 'sparkles',
            self::Receivable => 'hand-raised',
            self::OtherAsset => 'cube',
            self::CreditCard => 'credit-card',
            self::ConsumerLoan => 'banknotes',
            self::Borrowed => 'users',
            self::OtherDebt => 'document-text',
        };
    }

    /** @return array<string, string> */
    public static function options(bool $debt): array
    {
        return collect(self::cases())->filter(fn (self $kind) => $kind->isDebt() === $debt)
            ->mapWithKeys(fn (self $kind) => [$kind->value => $kind->label()])->all();
    }
}
