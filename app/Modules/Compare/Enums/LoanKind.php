<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

/** Loại khoản vay, dùng cho bộ lọc và trang đích của trang so sánh vay. */
enum LoanKind: string implements HasLabel
{
    case Personal = 'personal';
    case Home = 'home';
    case Auto = 'auto';

    /** Giá trị trên URL công khai (tiếng Việt không dấu). */
    public function slug(): string
    {
        return match ($this) {
            self::Personal => 'tieu-dung',
            self::Home => 'mua-nha',
            self::Auto => 'mua-xe',
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
            self::Personal => 'Vay tiêu dùng tín chấp',
            self::Home => 'Vay mua nhà',
            self::Auto => 'Vay mua ô tô',
        };
    }

    /** Có tài sản bảo đảm (nhà, xe) hay không. */
    public function isSecured(): bool
    {
        return $this !== self::Personal;
    }
}
