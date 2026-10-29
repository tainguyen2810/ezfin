<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

/** Hình thức lĩnh lãi. Cùng kỳ hạn, lĩnh lãi càng sớm thì lãi suất niêm yết thường càng thấp. */
enum InterestPayment: string implements HasLabel
{
    case EndOfTerm = 'end_of_term';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Upfront = 'upfront';

    /** Giá trị trong file CSV và trên URL công khai. */
    public function slug(): string
    {
        return match ($this) {
            self::EndOfTerm => 'cuoi-ky',
            self::Monthly => 'hang-thang',
            self::Quarterly => 'hang-quy',
            self::Upfront => 'dau-ky',
        };
    }

    public static function fromSlug(?string $slug): ?self
    {
        return collect(self::cases())->first(fn (self $case) => $case->slug() === $slug);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::EndOfTerm => 'Cuối kỳ',
            self::Monthly => 'Hằng tháng',
            self::Quarterly => 'Hằng quý',
            self::Upfront => 'Đầu kỳ',
        };
    }
}
