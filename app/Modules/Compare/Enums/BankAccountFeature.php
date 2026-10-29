<?php

namespace App\Modules\Compare\Enums;

use Filament\Support\Contracts\HasLabel;

/** Tính năng khách quan của tài khoản thanh toán, dùng cho bộ lọc. */
enum BankAccountFeature: string implements HasLabel
{
    case OnlineOpening = 'online_opening';
    case FreeTransfer = 'free_transfer';
    case InternationalDebitCard = 'international_debit_card';
    case VanityNumber = 'vanity_number';
    case AutoSavings = 'auto_savings';
    case ForeignCurrency = 'foreign_currency';

    /** Giá trị trên URL công khai (tiếng Việt không dấu). */
    public function slug(): string
    {
        return match ($this) {
            self::OnlineOpening => 'mo-online',
            self::FreeTransfer => 'mien-phi-chuyen-khoan',
            self::InternationalDebitCard => 'the-ghi-no-quoc-te',
            self::VanityNumber => 'so-tai-khoan-dep',
            self::AutoSavings => 'sinh-loi-tu-dong',
            self::ForeignCurrency => 'ngoai-te',
        };
    }

    public static function fromSlug(?string $slug): ?self
    {
        return collect(self::cases())->first(fn (self $case) => $case->slug() === $slug);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::OnlineOpening => 'Mở online (eKYC)',
            self::FreeTransfer => 'Miễn phí chuyển khoản',
            self::InternationalDebitCard => 'Thẻ ghi nợ quốc tế',
            self::VanityNumber => 'Chọn số tài khoản đẹp',
            self::AutoSavings => 'Sinh lời tự động',
            self::ForeignCurrency => 'Tài khoản ngoại tệ',
        };
    }
}
