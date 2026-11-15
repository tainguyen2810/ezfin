<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

/** Hạng thẻ tín dụng, xếp chung các tên gọi của Visa, Mastercard, JCB và ngân hàng. */
enum CardTier: string implements HasLabel
{
    case Classic = 'classic';
    case Gold = 'gold';
    case Platinum = 'platinum';
    case Premium = 'premium';

    public function getLabel(): string
    {
        return match ($this) {
            self::Classic => 'Phổ thông (Classic, Standard)',
            self::Gold => 'Vàng (Gold)',
            self::Platinum => 'Bạch kim (Platinum, Titanium)',
            self::Premium => 'Cao cấp (Signature, Infinite, World, Ultimate)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::Classic => 'Phổ thông',
            self::Gold => 'Gold',
            self::Platinum => 'Platinum',
            self::Premium => 'Cao cấp',
        };
    }

    public function slug(): string
    {
        return match ($this) {
            self::Classic => 'pho-thong',
            self::Gold => 'gold',
            self::Platinum => 'platinum',
            self::Premium => 'cao-cap',
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

    /** Đoán hạng theo tên thẻ; tên không nêu hạng thì trả null (để người quản trị chọn), không đoán bừa. */
    public static function fromName(string $name): ?self
    {
        $name = ' '.mb_strtolower($name).' ';

        return match (true) {
            (bool) preg_match('/\b(infinite|signature|world|ultimate|reserve|diamond|private|priority|precious|elite)\b/u', $name) => self::Premium,
            (bool) preg_match('/\b(platinum|titanium)\b/u', $name) => self::Platinum,
            (bool) preg_match('/\b(gold|vàng)\b/u', $name) => self::Gold,
            (bool) preg_match('/\b(classic|standard|chuẩn)\b/u', $name) => self::Classic,
            default => null,
        };
    }
}
