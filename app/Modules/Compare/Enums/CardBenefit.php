<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

/** Nhóm lợi ích chính, dùng cho bộ lọc "nhu cầu" ở trang so sánh. */
enum CardBenefit: string implements HasLabel
{
    case Cashback = 'cashback';
    case Miles = 'miles';
    case Shopping = 'shopping';
    case Travel = 'travel';
    case Dining = 'dining';
    case Installment = 'installment';

    /** Giá trị trên URL công khai (tiếng Việt không dấu). */
    public function slug(): string
    {
        return match ($this) {
            self::Cashback => 'hoan-tien',
            self::Miles => 'tich-dam',
            self::Shopping => 'mua-sam',
            self::Travel => 'du-lich',
            self::Dining => 'an-uong',
            self::Installment => 'tra-gop',
        };
    }

    public static function fromSlug(?string $slug): ?self
    {
        foreach (self::cases() as $case) {
            if ($case->slug() === $slug) {
                return $case;
            }
        }

        return null;
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Cashback => 'Hoàn tiền',
            self::Miles => 'Tích dặm bay',
            self::Shopping => 'Mua sắm',
            self::Travel => 'Du lịch',
            self::Dining => 'Ăn uống',
            self::Installment => 'Trả góp 0%',
        };
    }
}
